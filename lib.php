<?php
// Quiz HUD library functionality.
// Written by: Peter Bloomfield
//
// Released as-is, without warranty, under the GNU GPL v3.
//


require_once('config.php');


// Define the version number of the files.
// (This does NOT indicate the installed version... that information is stored in the database).
define('QUIZHUD_VERSION', '1.1');

// Report an error and terminate the script
//  $error = an optional error message
function error($msg='')
{
    // We want to make sure the page has a header and footer, to ensure valid HTML
    @include_once('_page_header.php');
    echo '<p class="error"><strong>ERROR:</strong><br/>',$msg,'</p>';
    @include_once('_page_footer.php');
    exit();
}

// Set a configuration value
//  $name = the name of the configuration setting to set
//  $value = the value of the configuration setting
// (Both parameters will be cast to strings)
// Returns true if successful, or false on failure.
function set_config($name, $value)
{
    $name = (string)$name;
    $value = (string)$value;

    // Execute the query to add or overwrite.
    $result = mysql_query("
        REPLACE INTO qh_config
        SET name = '$name', value = '$value'
    ");

    // Check for errors
    if ($result === false) return false;
    return true;
}

// Get a configuration value
//  $name = the name of the configuration setting to get
// Returns a string containing the value from the database, or false if the setting was not found
function get_config($name)
{
    $name = (string)$name;

    // Execute the query
    $result = mysql_query("
        SELECT *
        FROM qh_config
        WHERE name = '$name'
        LIMIT 1
    ");

    // Check for errors
    if ($result === false || mysql_num_rows($result) == 0) return false;
    // Extract the data
    $row = mysql_fetch_assoc($result);
    return (string)$row['value'];
}

// Get the version number of the installed quizHUD.
// Returns false if the information could not be found in the database.
function get_quizhud_version()
{
    return (float)get_config('version');
}

// Get the ID number of the current quiz.
// Returns false if no 'current' quiz is selected.
function get_current_quiz()
{
    $id = (int)get_config('currentquiz');
    if ($id <= 0) return false; // Check for validity
    return $id;
}

// Set the ID number of the current quiz
function set_current_quiz($id)
{
    return set_config('currentquiz', (int)$id);
}

// Check that a password has been provided in parameter "pwd",
//  and make sure it matches the configuration password.
// Terminates the script if the password was not provided or was invalid.
function require_password()
{
    // Get the request password
    if (!isset($_REQUEST['pwd'])) error('Internal password required.');
    $pwd = $_REQUEST['pwd'];
    
    // Make sure it matches the configuration password
    if (!defined('QUIZHUD_INTERNAL_PWD')) error('No internal password specified in server-side configuration.');
    if ($pwd != QUIZHUD_INTERNAL_PWD) error('Invalid password.');
}

// Check if quizHUD is properly installed (ensures the database tables are present).
// Returns true if so, or false if not.
function is_installed()
{
    // Define a list of tables which should be present
    $tables = array();
    $tables['qh_answer'] = false;
    $tables['qh_attempt'] = false;
    $tables['qh_page'] = false;
    $tables['qh_question'] = false;
    $tables['qh_quiz'] = false;
    $tables['qh_user'] = false;
    
    // Get a list of all tables in the database
    $result = mysql_query('SHOW TABLES');
    if ($result === false) return false;
    if (mysql_num_rows($result) == 0) return false;
    while ($row = mysql_fetch_row($result)) {
        // Attempt to mark the table off the list
        if (array_key_exists($row[0], $tables)) $tables[$row[0]] = true;
    }
    
    // Were any tables missing?
    if (in_array(false, $tables)) return false;
    return true;
}

// Require that the quizHUD is installed. Terminates with error message if not.
function require_installed()
{
    if (!is_installed()) {
        error('The quizHUD does not appear to have been installed.');
        exit();
    }
}

// Begin a quizhud PHP session
function start_quizhud_session()
{
    session_name('quizhudadmin');
    session_start();
}

// Attempt to login the user with the given password.
// (Returns true if successful, or false otherwise).
function quizhud_login($password)
{
    if ($_REQUEST['password'] == QUIZHUD_ADMIN_PWD) {
        $_SESSION['quizhudadminlogin'] = true;
        return true;
    }
    return false;
}

// Attempt to logout the user.
function quizhud_logout()
{
    unset($_SESSION['quizhudadminlogin']);
}

// Is the user currently logged-in?
function is_logged_in()
{
    if (isset($_SESSION['quizhudadminlogin']) && $_SESSION['quizhudadminlogin'] === true) return true;
    return false;
}

// Require the user to login.
// If the user gets re-directed to a login page, then they will be redirected to $nexturl afterwards.
// No re-direction will happen if the user is already logged-in.
function require_login($nexturl = '')
{
    // If the user is already logged-in, then there is nothing to
    if (is_logged_in()) return;
    // Store our next URL
    if (!empty($nexturl)) $_SESSION['quizhudnexturl'] = $nexturl;
    else unset($_SESSION['quizhudnexturl']);

    // Make sure the headers have not already been sent
    if (headers_sent()) {
        error('You must <a href="'.QUIZHUD_WWW_ROOT.'/browser/login.php">login</a> to access this resource.');
        exit();
    }
    
    // Redirect to the login page
    header('Location: '.QUIZHUD_WWW_ROOT.'/browser/login.php');
    exit();
}


// Fetch and clean a named request parameter as a string.
//  $name = the name of the parameter to fetch
//  $default = the default value to return if the parameter was not specified
//  $raw = if true (not default) then HTML entities won't be encoded
function fetch_param_string($name, $default = null, $raw = false)
{
    if (!isset($_REQUEST[$name])) return $default;
    if ($raw) return $_REQUEST[$name];
    return htmlentities($_REQUEST[$name], ENT_QUOTES, false);
}

// Fetch and clean a named request parameter as an integer.
//  $name = the name of the parameter to fetch
//  $default = the default value to return if the parameter was not specified
function fetch_param_integer($name, $default = null)
{
    if (!isset($_REQUEST[$name])) return $default;
    return (integer)($_REQUEST[$name]);
}

// Fetch and clean a named request parameter as a boolean.
//  $name = the name of the parameter to fetch
//  $default = the default value to return if the parameter was not specified
function fetch_param_boolean($name, $default = null)
{
    if (!isset($_REQUEST[$name])) return $default;
    $str = trim($_REQUEST[$name]);
    if (empty($str)) return $default;
    // Anything starting with N or F (e.g. "No" or "False") should be true.
    // Also a plain 0 should be treated as false.
    // Pretty much anything else should be treated as true.
    if ($str[0] == 'F' || $str[0] == 'f' || $str[0] == 'N' || $str[0] == 'n' || $str == '0') return false;
    return true;
}


// A structure representing an information page for the Quiz HUD to display.
class QuizHUDPage
{
    // Constructor
    function QuizHUDPage($id=0, $name='', $title='', $text='', $image='', $layout='imageright')
    {
        $this->id = (integer)$id;
        $this->name = (string)$name;
        $this->title = (string)$title;
        $this->text = (string)$text;
        $this->image = (string)$image;
        $this->layout = (string)$layout;
    }
    
    
    // Renders this page out to HTML.
    // Note: renders content in scalable form, not including header/footer or container.
    // Possible layouts: ('imageright', 'imageleft', 'noimage', 'imageonly')
    function render()
    {
        // If we have an image to display, then figure out its full address
        $imagepath = '';
        if (!empty($this->image)) $imagepath = QUIZHUD_WWW_ROOT.'/'.QUIZHUD_IMG_FOLDER.'/'.$this->image;
        
        // Is this an image-only layout?
        if ($this->layout == 'imageonly') {
            echo '<div style="width:100%; height:100%; padding:0px; margin:0px; text-align:center; vertical-align:middle;">';
            echo '<img src="',$imagepath,'" style="border-width:0px;" alt="image"/>';
            echo '</div>';
        } else {
            // This is a text-based layout, possibly with an image
            
            // Do we have an image to display?
            if (!empty($imagepath) && $this->layout != 'noimage') {
                // Which side should the image be on?
                if ($this->layout == 'imageleft') {
                    // Float left
                    echo '<img src="',$imagepath,'" style="float:left; margin-right:8px; margin-bottom:8px; border:solid 1px black;" alt="image"/>';
                } else {
                    // Just assume float right
                    echo '<img src="',$imagepath,'" style="float:right; margin-left:8px; margin-bottom:8px; border:solid 1px black;" alt="image"/>';
                }
            }
            
            // Display the title
            if (!empty($this->title)) echo "<h1>",stripslashes($this->title),"</h1>\n";
            
            // Display the body text
            if (!empty($this->text)) echo '<div>',stripslashes($this->text),"</div>\n";
        }
    }
    

    // ID of this page (integer)
    var $id = 0;
    // Name of this page (string)
    var $name = '';
    // Title of this page (string)
    var $title = '';
    // Main content text of this page (string, could contain HTML)
    var $text = '';
    // Path of the image file, if applicable, relative to the image folder (string)
    var $image = '';
    // Layout of this page (string: 'imageright', 'imageleft', 'noimage', 'imageonly').
    // Will always default to 'imageright' if no valid format is given.
    var $layout = 'imageright';
}

// Validates the page name.
// Returns true if it is valid, or false otherwise.
// (Page names should consist only of letters, numbers, dashes, and underscores, and must not be empty).
function validate_page_name($name)
{
    // Make sure it's not empty
    if (empty($name)) return false;
    // Go through each character
    $numchars = strlen($name);
    for ($i = 0; $i < $numchars; $i++) {
        if (ctype_alnum($name[$i]) != true && $name[$i] != '-' && $name[$i] != '_') return false;
    }
    return true;
}

// Fetch a page object from the database.
//  $name = the unique name of the page to get
// Returns a QuizHUDPage object if successful, or false otherwise.
function get_page($name)
{
    // Attempt to load the database record
    $result = mysql_query("
        SELECT *
        FROM qh_page
        WHERE name = '$name'
        LIMIT 1
    ");
    
    // Check for errors
    if ($result === false || mysql_num_rows($result) < 1) return false;
    
    // Construct our object
    $row = mysql_fetch_assoc($result);
    return new QuizHUDPage($row['id'], $row['name'], $row['title'], $row['text'], $row['image'], $row['layout']);
}

// Fetch a page object from the database, identified by ID.
//  $id = integer ID of the page to get
// Returns a QuizHUDPage object if successful, or false otherwise.
function get_page_by_id($id)
{
    // Sanitize the input
    $id = (integer)$id;

    // Attempt to load the database record
    $result = mysql_query("
        SELECT *
        FROM qh_page
        WHERE id = $id
        LIMIT 1
    ");
    
    // Check for errors
    if ($result === false || mysql_num_rows($result) < 1) return false;
    
    // Construct our object
    $row = mysql_fetch_assoc($result);
    return new QuizHUDPage($row['id'], $row['name'], $row['title'], $row['text'], $row['image'], $row['layout']);
}

// Fetch a list of all pages in the database, sorted by name
// Returns a numeric array of QuizHUDPage objects if successful, or false otherwise.
function get_pages()
{
    // Execute the query
    $result = mysql_query("
        SELECT *
        FROM qh_page
        WHERE 1
        ORDER BY name
        LIMIT 0, 10000
    ");
    
    // Check for errors
    if ($result === false) return false;
    if (mysql_num_rows($result) == 0) return array();
    // Construct our output array
    $output = array();
    while ($row = mysql_fetch_assoc($result)) {
        $output[] = new QuizHUDPage($row['id'], $row['name'], $row['title'], $row['text'], $row['image'], $row['layout']);
    }
    return $output;
}



// A structure representing a quiz.
class QuizHUDQuiz
{
    // Constructor
    function QuizHUDQuiz($id=0, $name="", $method="")
    {
        $this->id = (integer)$id;
        $this->name = (string)$name;
        $this->method = (string)$method;
    }

    // The ID number of this quiz (integer)
    var $id = 0;
    // The name of this quiz (string)
    var $name = "";
    // Grading method for this quiz (string)
    var $method = "";
}

// A structure representing a question
class QuizHUDQuestion
{
    // Constructor
    function QuizHUDQuestion($id=0, $quizid=0, $text="", $weight=0.0, $type="")
    {
        $this->id = (integer)$id;
        $this->quizid = (integer)$quizid;
        $this->text = (string)$text;
        $this->weight = (float)$weight;
        $this->type = (string)$type;
    }

    // The ID of this question (integer)
    var $id = 0;
    // The ID of the quiz this question belongs to (integer)
    var $quizid = 0;
    // The text of this question (string)
    var $text = "";
    // The assessable weight of this question (float, 0.0 to 1.0)
    var $weight = 1.0;
    // The type of question (e.g. 'multiplechoice' or 'explore')
    var $type = "";
}

// A structure representing an answer
class QuizHUDAnswer
{
    // Constructor
    function QuizHUDAnswer($id=0, $questionid=0, $shortname="", $text="", $value=1.0)
    {
        $this->id = (integer)$id;
        $this->questionid = (integer)$questionid;
        $this->shortname = (string)$shortname;
        $this->text = (string)$text;
        $this->value = (float)$value;
    }

    // The ID of this answer (integer)
    var $id = 0;
    // The ID of the question this answer belongs to (integer)
    var $questionid = 0;
    // The shortname for this answer (string)
    var $shortname = "";
    // The full text for this answer (string)
    var $text = "";
    // The value of this answer (i.e. score)
    var $value = 1.0;
}

// A structure representing a single user
class QuizHUDUser
{
    // Constructor
    function QuizHUDUser($id=0, $uuid='', $name='')
    {
        $this->id = (integer)$id;
        $this->uuid = (string)$uuid;
        $this->name = (string)$name;
    }

    // The ID of this user (integer)
    var $id = 0;
    // The UUID of the avatar (string)
    var $uuid = '';
    // The name of the avatar (string)
    var $name = '';
}

// A structure representing an attempt at a quiz question
class QuizHUDAttempt
{
    // Constructor
    function QuizHUDAttempt($id=0, $questionid=0, $userid=0, $answer='', $timestamp=0)
    {
        $this->id = (integer)$id;
        $this->questionid = (integer)$questionid;
        $this->userid = (integer)$userid;
        $this->answer = (string)$answer;
        $this->timestamp = (integer)$timestamp;
    }

    // The ID of this attempt (integer)
    var $id = 0;
    // The ID of the question this attempt relates to (integer)
    var $questionid = 0;
    // The ID of the user who made this attempt (integer)
    var $userid = 0;
    // The shortname of the answer given in this attempt (string)
    var $answer = '';
    // The time at which this attempt was made
    var $timestamp = 0;
}


// Get the numbered quiz
//  $id = the integer ID of the quiz to fetch
// Returns a QuizHUDQuiz object if successful, or false is not
function get_quiz($id)
{
    // Sanitize the input
    $id = (integer)$id;

    // Execute the query
    $result = mysql_query("
        SELECT *
        FROM qh_quiz
        WHERE id = $id
        LIMIT 1
    ");

    // Check for errors
    if ($result === false || mysql_num_rows($result) < 1) return false;
    // Make our output structure
    $row = mysql_fetch_assoc($result);
    return new QuizHUDQuiz($row['id'], $row['name'], $row['method']);
}

// Get all available quizzes
// Returns an associative array of IDs to QuizHUDQuiz objects if successful, or false if not
function get_quizzes()
{
    // Execute the query
    $result = mysql_query("
        SELECT *
        FROM qh_quiz
        WHERE 1
        LIMIT 0,10000
    ");
    
    // Check for errors
    if ($result === false) return false;
    if (mysql_num_rows($result) < 1) return array();
    // Make our output array
    $output = array();
    while ($row = mysql_fetch_assoc($result)) {
        $output[$row['id']] = new QuizHUDQuiz($row['id'], $row['name'], $row['method']);
    }
    return $output;
}

// Get all questions in the specified quiz.
//  $id = the integer ID of the quiz to fetch
//  $skipunassessed = if true, then unassessed questions (i.e. weight = 0.0) will be omitted from the results
//  $skipnoanswers = if true, excludes multiple choice questions which have no answers, or assessed explore questions with no answers.
// Returns an associative array of questions (IDs QuizHUDQuestion objects) if successful.
// Returns false on failure.
function get_questions($id, $skipunassessed = false, $skipnoanswers = true)
{
    // Sanitize the input
    $id = (integer)$id;

    // Execute the query
    $result = mysql_query("
        SELECT *
        FROM qh_question
        WHERE quizid = $id
        ORDER BY id
        LIMIT 0,10000
    ");

    // Check for errors
    if ($result === false) return false;
    if (mysql_num_rows($result) < 1) return array();

    // Go through each returned row and build our output
    $output = array();
    while ($row = mysql_fetch_assoc($result)) {
        // We may want to skip unassessed questions
        if ($skipunassessed && $row['weight'] == 0.0) continue;
        // We may want to skip questions without answers
        if ($skipnoanswers) {
		    $answers = get_answers((int)$row['id']);
		    if ($answers === false || count($answers) == 0) {
		    	// No answers - skip any multiple choice question, or assessed explore questions
		    	if ($row['type'] == 'multiplechoice') continue;
		    	else if ($row['weight'] != 0.0) continue;
		    }
        }
        
        // Store it
        $output[$row['id']] = new QuizHUDQuestion($row['id'], $row['quizid'], $row['text'], $row['weight'], $row['type']);
    }
    return $output;
}

// Return the specified question.
//  $quizid = the integer ID of the quiz to fetch a question from
//  $questionid = the integer ID of a question to fetch
// Returns a QuizHUDQuestion object if successful, or false otherwise
function get_question($quizid, $questionid)
{
    // Sanitize the input
    $quizid = (integer)$quizid;
    $questionid = (integer)$questionid;

    // Execute the query
    $result = mysql_query("
        SELECT *
        FROM qh_question
        WHERE quizid = $quizid AND id = $questionid
        LIMIT 1
    ");

    // Check for errors
    if ($result === false || mysql_num_rows($result) < 1) return false;
    // Build and return our object
    $row = mysql_fetch_assoc($result);
    return new QuizHUDQuestion($row['id'], $row['quizid'], $row['text'], $row['weight'], $row['type']);
}

// Fetch all the answers to a given question, sorted by short name.
//  $questionid = integer ID of the question we are fetching answers from
// Returns an associative array of answer IDs to QuizHUDAnswer objects, if successful.
// Returns false otherwise.
function get_answers($questionid)
{
    // Sanitize the input
    $questionid = (integer)$questionid;

    // Execute the query
    $result = mysql_query("
        SELECT *
        FROM qh_answer
        WHERE questionid = $questionid
        ORDER BY shortname
        LIMIT 0,10000
    ");

    // Check for errors
    if ($result === false) return false;
    if (mysql_num_rows($result) < 1) return array();

    // Go through each returned row and build our output array
    $output = array();
    while ($row = mysql_fetch_assoc($result)) {
        $output[$row['id']] = new QuizHUDAnswer($row['id'], $row['questionid'], $row['shortname'], $row['text'], $row['value']);
    }
    return $output;
}

// Fetch a specific answer.
//  $answerid = integer ID of the answer we are fetching
// Returns a QuizHUDAnswer object, if successful.
// Returns false otherwise.
function get_answer($answerid)
{
    // Sanitize the input
    $answerid = (integer)$answerid;

    // Execute the query
    $result = mysql_query("
        SELECT *
        FROM qh_answer
        WHERE id = $answerid
        LIMIT 1
    ");

    // Check for errors
    if ($result === false || mysql_num_rows($result) < 1) return false;

    // Build our output object
    $row = mysql_fetch_assoc($result);
    return new QuizHUDAnswer($row['id'], $row['questionid'], $row['shortname'], $row['text'], $row['value']);
}

// Attempt to locate an answer given its shortname
//  $questionid = the integer ID of the question we are querying
//  $shortname = the shortname of the answer we are trying to find
// Returns a QuizHUDAnswer object if successful, or false otherwise.
// Note that in explore questions, failure to find an answer simply means they answer is wrong.
// In multiple choice mode, failure to find an answer indicates an invalid selection.
function find_answer($questionid, $shortname)
{
    // Sanitize the input
    $questionid = (integer)$questionid;
    $shortname = addslashes($shortname);

    // Execute the query
    $result = mysql_query("
        SELECT *
        FROM qh_answer
        WHERE questionid = $questionid AND shortname = '$shortname'
        LIMIT 1
    ");

    // Check for errors
    if ($result === false || mysql_num_rows($result) < 1) return false;
    // Build and return our output object
    $row = mysql_fetch_assoc($result);
    return new QuizHUDAnswer($row['id'], $row['questionid'], $row['shortname'], $row['text'], $row['value']);
}

// Get the numbered user
//  $userid = integer of the user to fetch
// Returns a QuizHUDUser structure if successful, or false otherwise
function get_user($userid)
{
    // Sanitize the input
    $userid = (integer)$userid;

    // Execute the query
    $result = mysql_query("
        SELECT *
        FROM qh_user
        WHERE id = $userid
        LIMIT 1
    ");

    // Check for errors
    if ($result === false || mysql_num_rows($result) < 1) return false;
    // Build and return our object
    $row = mysql_fetch_assoc($result);
    return new QuizHUDUser($row['id'], $row['uuid'], $row['name']);
}

// Get all users.
// Returns a numeric array QuizHUDUser structures if successful, or false otherwise.
// Results are sorted by avatar name, then by UUID, then by ID
function get_users()
{
    // Execute the query
    $result = mysql_query("
        SELECT *
        FROM qh_user
        WHERE 1
        ORDER BY name, uuid, id
        LIMIT 0,100000
    ");

    // Check for errors
    if ($result === false) return false;
    if (mysql_num_rows($result) == 0) return array();
    // Create our output array
    $output = array();
    while ($row = mysql_fetch_assoc($result)) {
        $output[] = new QuizHUDUser($row['id'], $row['uuid'], $row['name']);
    }
    return $output;
}

// Load the specified user.
// Will add the details to database if they are not already present.
// Returns a QuizHUDUser structure if successful, or false otherwise.
function load_user($uuid, $name)
{
    // Attempt to find the specified user (by UUID only -- name is purely for informational purposes)
    $result = mysql_query("
        SELECT *
        FROM qh_user
        WHERE uuid = '$uuid'
        LIMIT 1
    ");
    
    // Is this a new avatar?
    if ($result === false || mysql_num_rows($result) < 1) {
        // Yes - insert a new record
        $insert = mysql_query("
             INSERT INTO qh_user (uuid, name)
             VALUES ('$uuid', '$name')
        ");
        
        if ($insert === false) return false;
        return new QuizHUDUser(mysql_insert_id(), $uuid, $name);
    }
    
    // Existing avatar data
    
    // Create our output structure
    $row = mysql_fetch_assoc($result);
    $output = new QuizHUDUser($row['id'], $row['uuid'], $row['name']);
    
    // Do we have a new avatar name to insert?
    if (!empty($name) && $output->name != $name) {
        // Yes - attempt to update the record
        $update = mysql_query("
            UPDATE qh_user
            SET name = '$name'
            WHERE id = {$output->id}
            LIMIT 1
        ");
        // If it was successful, then store the new name in our output structure too
        if ($update) $output->name = $name;
    }
    
    return $output;
}


// Obtain all attempts the specified user has made at the given question.
//  $questionid = integer ID of a question
//  $userid = integer ID of a user
// Returns a numeric array of QuizHUDAttempt objects if successful, or false otherwise.
// Results sorted by timestamp.
function get_attempts($questionid, $userid)
{
    // Sanitize the inputs
    $questionid = (integer)$questionid;
    $userid = (integer)$userid;
    
    // Execute the query
    $result = mysql_query("
        SELECT *
        FROM qh_attempt
        WHERE questionid = $questionid AND userid = $userid
        ORDER BY timestamp
        LIMIT 0, 10000
    ");
    
    // Check for errors
    if ($result === false) return false;
    if (mysql_num_rows($result) == 0) return array();
    
    // Prepare our output array
    $output = array();
    while ($row = mysql_fetch_assoc($result)) {
        $output[] = new QuizHUDAttempt($row['id'], $row['questionid'], $row['userid'], $row['answer'], $row['timestamp']);
    }
    return $output;
}

// Get a specific attempt from the database.
//  $questionid = integer ID of the question the attempt was on
//  $userid = integer ID of the user who made the attempt
//  $attemptid = itneger ID of the attempt we want to get
// Returns a QuizHUDAttempt object if successful, or false otherwise.
function get_attempt($questionid, $userid, $attemptid)
{
     // Sanitize the input
     $questionid = (integer)$questionid;
     $userid = (integer)$userid;
     $attemptid = (integer)$attemptid;
     
     // Execute the query
     $result = mysql_query("
        SELECT *
        FROM qh_attempt
        WHERE questionid = $questionid AND userid = $userid AND id = $attemptid
        LIMIT 1
     ");
     
     // Check for errors
     if ($result === false || mysql_num_rows($result) < 1) return false;
     // Build our return object
     $row = mysql_fetch_assoc($result);
     return new QuizHUDAttempt($row['id'], $row['questionid'], $row['userid'], $row['answer'], $row['timestamp']);
}


// Calculate the specified user's current score on the quiz.
// Note: unassessed questions are not counted.
//  $quiz = a loaded QuizHUDQuiz object (you can override the grading method before passing this object in)
//  $user = a loaded QuizHUDUser object
// Returns a floating point percentage value if successful, or false otherwise.
// Also returns false if the user hasn't attempted the quiz yet, or if there are no questions.
function calculate_score($quiz, $user)
{
    // Make sure the quiz and user data is loaded
    if (empty($quiz->id) || empty($user->id)) return false;
    // Get all the questions for this quiz, and remove any unassessed or unanswerable ones
    $questions = get_questions($quiz->id, true, true);
    if (!$questions || count($questions) == 0) return false;
    
    // We want to calculate the maximum possible score, and the actual score
    $maxscore = 0.0;
    $actualscore = 0.0;
    
    // Calculate the total number of attempts by this user
    $totalnumattempts = 0;
    
    // Go through each remaining question
    foreach ($questions as $question) {
        
        // Get all the answers for the current question
        $answers = get_answers($question->id);
        if (!$answers || count($answers) == 0) continue;
        // Go through each answer to find the maximum possible weighted score for this question, then add it to our total
        $max = 0.0;
        foreach ($answers as $answer) {
            $weightedvalue = $question->weight * $answer->value;
            if ($weightedvalue > $max) $max = $weightedvalue;
        }
        $maxscore += $max;
        
        // Get all the user's attempts at this question
        $attempts = get_attempts($question->id, $user->id);
        if (!$attempts || count($attempts) == 0) continue;
        
        // Go through each one, and calculate the score according to the system
        $curscore = 0.0; // Used to store the worst or best so far, or the total for averaging
        $timestamp = 0; // Used to store the earliest or latest timestamp so far
        $numattempts = 0; // Stores the number of attempts processed so far
        foreach ($attempts as $attempt) {
            $totalnumattempts++;
            // Find the answer which matches this attempt, and get the value (value is 0.0 if answer is not found)
            $attemptanswer = find_answer($question->id, $attempt->answer);
            $value = 0.0;
            if ($attemptanswer) $value = $attemptanswer->value;
            $numattempts++;
            
            // Process the value depending on our grading system
            switch ($quiz->method) {
            case 'first':
                // If this is the earliest time so far, then store the score
                if ($numattempts == 1 || $attempt->timestamp <= $timestamp) {
                    $timestamp = $attempt->timestamp;
                    $curscore = $value;
                }
                break;
                
            case 'last':
                // If this is the latest time so far, then store the score
                if ($numattempts == 1 || $attempt->timestamp > $timestamp) {
                    $timestamp = $attempt->timestamp;
                    $curscore = $value;
                }
                break;
            
            case 'worst':
                // If this is the worst score so far, then store it
                if ($numattempts == 1 || $value < $curscore) $curscore = $value;
                break;
                
            case 'best':
                // If this is the best score so far, then store it
                if ($numattempts == 1 || $value > $curscore) $curscore = $value;
                break;
                
            default:
                // Just assume this is 'average' grading method.
                // Total up the score
                $curscore += $value;
                break;
            }
        }
        
        // If we are using average grading, then take the mean of all attempts in this question.
        if ($quiz->method == 'mean') {
            if ($numattempts > 0) $curscore = ($curscore / (float)$numattempts);
        }
        
        // Weight this outcome, and add it to our total score so far
        $actualscore += ($question->weight * $curscore);
    }
    
    // Were there no attempts made at all?
    if ($totalnumattempts == 0) return false;
    
    // Return a percentage of the best possible score
    if ($maxscore == 0.0) return 0.0;
    return (($actualscore / $maxscore) * 100.0);
}

// Add an attempt to the database.
//  $questionid = integer ID of the question being attempted
//  $userid = integer ID of the user making the attempt
//  $answer = text of the answer given (shortname)
// Will return the ID of the attempt if successful, or false otherwise.
function add_attempt($questionid, $userid, $answer)
{
    // Sanitize our input
    $questionid = (integer)$questionid;
    $userid = (integer)$userid;
    // Store our current timestamp
    $timestamp = time();
    
    // Execute the query
    $result = mysql_query("
        INSERT INTO qh_attempt (`questionid`, `userid`, `answer`, `timestamp`)
        VALUES ($questionid, $userid, '$answer', $timestamp)
    ");
    
    // Return the result
    if ($result === false) return false;
    return mysql_insert_id();
}


// Gets a numeric array of images from the images folder
// (sorted alphabetically)
function get_images()
{
    // Open the directory
    $dir = QUIZHUD_DIR_ROOT.'/'.QUIZHUD_IMG_FOLDER;
    if (!is_dir($dir)) return array();
    if (!$dh = opendir($dir)) return array();
    
    // Go through each item in the directory
    $images = array();
    while (($file = readdir($dh)) !== false) {
        // Ignore anything starting with a . and anything which isn't a file
        if (strpos($file, '.') == 0) continue;
        $filetype = @filetype($dir.'/'.$file);
        if (empty($filetype) || $filetype != 'file') continue;
        
        // Store it
        $images[] = $file;
    }
    closedir($dh);
    natcasesort($images);
    return $images;
}

// Deletes the given quiz, along with all associated answers and attempts.
//  $id = the ID of the quiz to delete
function delete_quiz($id)
{
    // Attempt to load the given quiz, and its questions
    $quiz = get_quiz($id);
    if (!$quiz) return false;
    $questions = get_questions($id, false, false);
    
    // If we have some questions go through each one
    if ($questions) {
        foreach ($questions as $question) {
            // Delete all the answers for this question
            mysql_query("
                DELETE FROM qh_answer
                WHERE questionid = {$question->id}
            ");
            
            // Delete all the attempts at this question
            mysql_query("
                DELETE FROM qh_attempt
                WHERE questionid = {$question->id}
            ");
            
            // Delete the question itself from the database
            mysql_query("
                DELETE FROM qh_question
                WHERE id = {$question->id}
                LIMIT 1
            ");
        }
    }
    
    // Finally, delete the quiz
    $result = mysql_query("
        DELETE FROM qh_quiz
        WHERE id = {$quiz->id}
    ");
    return (bool)$result;
}

// Deletes the specified question, along with all associated answers and attempts
//  $id = the ID of the question to delete
function delete_question($id)
{
    // Sanitize the input
    $id = (int)$id;

	// Delete all answers and attempts which pertain to this question
	mysql_query("
		DELETE FROM qh_answer
		WHERE questionid = {$id}
	");
	mysql_query("
		DELETE FROM qh_attempt
		WHERE questionid = {$id}
	");
	
	// Finally, delete the question itself
	$result = mysql_query("
        DELETE FROM qh_question
        WHERE id = {$id}
        LIMIT 1
    ");
    
    return (bool)$result;
}

// Deletes the specified user, along with all associated question attempts.
//  $id = the ID of the user to delete
function delete_user($id)
{
    // Sanitize the input
    $id = (int)$id;

	// Delete all attempts by this user
	mysql_query("
		DELETE FROM qh_attempt
		WHERE userid = {$id}
	");
	
	// Delete the user entry
	$result = mysql_query("
        DELETE FROM qh_user
        WHERE id = {$id}
        LIMIT 1
    ");
    
    return (bool)$result;
}

// Structures the answers of a multiple-choice question to ensure valid lettering (a, b, c, d),
//  optionally move an answer up or down.
// (The letters are put into the shortname field).
// Ensures that the letters are sequential, without duplicates.
// Sorts by ID number in the event of an existing duplicate.
// Caveat: if you have >26 answers, then answers >= 26 will all be 'z'.
// Does nothing on an explore question.
//  $quizid = the integer ID of the quiz being accessed
//  $questionid = the integer ID of the question to alter
//  $moveanswerid = the integer ID of an answer to move (optional)
//  $moveup = if true, then answer $moveanswerid is moved up. Otherwise, down. (optional, default up)
// Returns true if successful or false otherwise.
function structure_answers($quizid, $questionid, $moveanswerid = null, $moveup = true)
{
    // Load the quiz/question
    if (!$quiz = get_quiz($quizid)) return false;
    if (!$question = get_question($quizid, $questionid)) return false;
    
    // Execute the query
    $result = mysql_query("
        SELECT id, shortname
        FROM qh_answer
        WHERE questionid = $questionid
        ORDER BY shortname, id
        LIMIT 0,10000
    ");

    // Check for errors
    if ($result === false) return false;
    // Nothing to do if there are no answers at all
    $numanswers = mysql_num_rows($result);
    if ($numanswers == 0) return true;
    
    // Define the ASCII codes we can use for shortnames
    $mincode = 97; // 'a'
    $maxcode = 122; // 'z'

    // We need to find the answer which needs moved
    $moveanswernum = null;
    // Go through each returned row and build our re-structured array of answers
    $structuredanswers = array();
    $answernum = 0;
    while ($row = mysql_fetch_assoc($result)) {
        $row['shortname'] = chr(min($mincode + $answernum, $maxcode));
        $structuredanswers[] = $row;
        
        // Is this row to be moved?
        if ((int)$row['id'] == $moveanswerid) $moveanswernum = $answernum;
        
        $answernum++;
    }
    
    // Do we have an answer to be moved?
    if ($moveanswernum !== null) {
        // Yes - only continue if we actually can move it (e.g. we're not trying to move the first answer upwards)
        if ($numanswers > 1 && (($moveanswernum > 0 && $moveup == true) || ($moveanswernum < ($numanswers - 1) && $moveup == false))) {
            // Moving up or down?
            if ($moveup) {
                // Swap the shortnames of the moving answer with the one before it
                $newshortname = $structuredanswers[$moveanswernum - 1]['shortname'];
                $structuredanswers[$moveanswernum - 1]['shortname'] = $structuredanswers[$moveanswernum]['shortname'];
                $structuredanswers[$moveanswernum]['shortname'] = $newshortname;
                
            } else {
                // Swap the shortnames of the moving answer with the one after it
                $newshortname = $structuredanswers[$moveanswernum + 1]['shortname'];
                $structuredanswers[$moveanswernum + 1]['shortname'] = $structuredanswers[$moveanswernum]['shortname'];
                $structuredanswers[$moveanswernum]['shortname'] = $newshortname;
            }
        }
    }
    
    // Finally, write it all back to the database.
    $result = true;
    foreach ($structuredanswers as $sa) {
        // Execute the query
        $result = $result && mysql_query("
            UPDATE qh_answer
            SET shortname = '{$sa['shortname']}'
            WHERE id = {$sa['id']}
            LIMIT 1
        ");
        if (!$result) break;
    }
    
    return $result;
}

















?>

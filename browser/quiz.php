<?php

//////////
// QuizHUD quiz administration.
// Author: Peter R. Bloomfield

// GPL:
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// 
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//////////

require_once('../config.php');
require_once('../lib.php');

start_quizhud_session();
require_login(QUIZHUD_WWW_ROOT.'/browser/quiz.php');


// We may need to display a confirmation box
$confirmmessage = '';
// Has a page deletion been requested?
if (isset($_GET['deletequiz'])) {
    // Yes - has it been confirmed yet?
    if (isset($_GET['confirmdelete'])) {
    
        // Yes - do the deletion
        if (delete_quiz($_GET['deletequiz'])) {
            $_SESSION['message'] = 'Deletion successful.';
        } else {
            $_SESSION['message'] = 'Deletion failed.';
        }
        
        // Redirect back to this page again
        header('Location: '.QUIZHUD_WWW_ROOT.'/browser/quiz.php');
        exit($_SESSION['message']);
    
    } else {
        // Attempt to fetch the quiz
        $deletequiz = get_quiz($_GET['deletequiz']);
        if ($deletequiz != false) {
            // Display a confirmation form
            $confirmmessage = <<<XXXEODXXX
            
     <p class="error">
      <strong>DELETE?</strong><br/>
      Are you sure you want to delete quiz "{$deletequiz->name}"?<br/><i>(This will remove all associated questions, answers, and attempts by users. This action cannot be undone.)</i><br/><br/>
      <a class="button" href="quiz.php?deletequiz={$_GET['deletequiz']}&amp;confirmdelete=true" title="Click here to confirm the deletion."><span class="yes">Yes</span></a>
      &nbsp;
      <a class="button" href="quiz.php" title="Click here to cancel the deletion."><span class="no">No</span></a>
     </p>
        
XXXEODXXX;
        }
    }
}


// Has a current quiz toggle been requested?
if (isset($_GET['togglecurrent'])) {
    // Yes - does it match the existing current quiz?
    $togglecurrent = (int)$_GET['togglecurrent'];
    if ($togglecurrent == get_current_quiz()) {
        // Yes - toggle it off (select no current quiz)
        set_current_quiz(0);
        //$_SESSION['message'] = 'Unselected current quiz';
    } else {
        // No - select the new one as current
        //$_SESSION['message'] = 'Selected new current quiz';
        set_current_quiz($togglecurrent);
    }
    
    // Redirect back here (removes the request parameter to prevent re-toggling by accident)
    header('Location: '.QUIZHUD_WWW_ROOT.'/browser/quiz.php');
    exit();
}


$sectionname = 'quiz';
require_once('_page_header.php');

// Get any message which has been provided by another page
$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

?>


<h3>Quizzes</h3>
<p>
 This page lists all of the quizzes in your quizHUD installation.
 Use the icons on the right of each quiz to manipulate it.
 You can view the results of each quiz; toggle the 'current' quiz (that is, select the quiz which will be presented to users by default);
 edit each quiz, including its questions; and you can delete each quiz.
</p>


<?php
// Display the confirmation message, if we have one
if (!empty($confirmmessage)) echo $confirmmessage, "<br/>\n";

// Display any message that was provided
if (!empty($message)) {
     echo '<p class="info">',$message,'</p>';
}
?>


<div style="text-align:center;">
 <a class="button" href="<?php echo QUIZHUD_WWW_ROOT.'/browser/quiz_edit.php?quizid=new'; ?>" title="Click here to add a new quiz"><span class="newquiz">Add New Quiz</span></a><br/><br/>
</div>

<?php

// Define the basic URLs for performing actions
$actionresults = QUIZHUD_WWW_ROOT.'/browser/quiz_results.php';
$actiontogglecurrent = QUIZHUD_WWW_ROOT.'/browser/quiz.php';
$actionedit = QUIZHUD_WWW_ROOT.'/browser/quiz_edit.php';
$actiondelete = QUIZHUD_WWW_ROOT.'/browser/quiz.php';

// Check to see if there is a currently selected quiz.
$curquizid = get_current_quiz();
if ($curquizid === false) $curquizid = 0;
// Fetch a list of all the quizzes in the system.
$quizzes = get_quizzes();
if ($quizzes === false) error('Failed to query database for quizzes. Perhaps you have not <a href="install.php" title="Click here to visit the installation page">installed quizHUD</a> yet?');
if (count($quizzes) == 0) {
    echo '<p class="info">No quizzes to display</p>';
} else {    

    // Display a table of all quizzes
    echo "<table id=\"quizlist\" class=\"data\">\n";
    echo "<tr class=\"header\"><th>ID</th><th>Name</th><th>Grading Method</th><th>&nbsp;</th></tr>\n";
    
    // Go through each quiz
    $lightrow = true;
    foreach ($quizzes as $q) {

        // Check to see if this is the current quiz, or the quiz to be deleted
        if (!empty($deletequiz) && $deletequiz->id == $q->id) {
            echo "<tr class=\"delete\">\n";
            $lightrow = !$lightrow;
            
        } else if ($q->id == $curquizid) {
            echo "<tr class=\"current\">\n";
            $lightrow = !$lightrow;
            
        } else {
            // Alternate between light and dark
            if ($lightrow) {
                echo "<tr class=\"light\">\n";
                $lightrow = false;
            } else {
                echo "<tr class=\"dark\">\n";
                $lightrow = true;
            }
        }
        
        echo "<td class=\"id\">{$q->id}</td>";
        echo "<td class=\"name\">{$q->name}</td>";
        echo "<td class=\"method\">{$q->method}</td>";

        echo "<td class=\"action\">";

        // Action: view results
        echo "<a href=\"{$actionresults}?quizid={$q->id}\" title=\"Click here to view the results of this quiz.\"><img src=\"img/book_open.png\" alt=\"results\"/></a>\n";
        
        // Action: toggle current
        if ($q->id == $curquizid) {
            echo "<a href=\"{$actiontogglecurrent}?togglecurrent={$q->id}\" title=\"Click to toggle the current quiz.\"><img src=\"img/lightbulb.png\" alt=\"toggle current\"/></a>\n";
        } else {
            echo "<a href=\"{$actiontogglecurrent}?togglecurrent={$q->id}\" title=\"Click to toggle the current quiz.\"><img src=\"img/lightbulb_off.png\" alt=\"toggle current\"/></a>\n";
        }

        // Action: edit quiz
        echo "<a href=\"{$actionedit}?quizid={$q->id}\" title=\"Click here to edit this quiz.\"><img src=\"img/book_edit.png\" alt=\"edit\"/></a>\n";

        // Action: delete quiz
        echo "<a href=\"{$actiondelete}?deletequiz={$q->id}\" title=\"Click here to delete this quiz.\"><img src=\"img/delete.png\" alt=\"delete\"/></a>\n";
     

        echo "</td>";
        
        echo "</tr>\n";
    }
    
    echo "</table>\n";
}

?>

<?php
require_once('_page_footer.php');
?>

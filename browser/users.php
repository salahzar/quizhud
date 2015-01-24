<?php

//////////
// QuizHUD user administration.
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
require_login(QUIZHUD_WWW_ROOT.'/browser/users.php');


// We may need to display a confirmation box
$confirmmessage = '';
// Has a user deletion been requested?
if (isset($_GET['deleteuserid'])) {
    // Yes - has it been confirmed yet?
    if (isset($_GET['confirmdelete'])) {
    
        // Yes - do the deletion
        if (delete_user($_GET['deleteuserid'])) {
            $_SESSION['message'] = 'Deletion successful.';
        } else {
            $_SESSION['errormessage'] = 'Deletion failed.';
        }
        
        // Redirect back to this page again
        header('Location: '.QUIZHUD_WWW_ROOT.'/browser/users.php');
        exit();
    
    } else {
        // Attempt to fetch the user
        $deleteuser = get_user($_GET['deleteuserid']);
        if ($deleteuser != false) {
            // Get their name, or their UUID if the name is unknown
            $deleteusername = $deleteuser->name;
            if (empty($deleteusername)) $deleteusername = $deleteuser->uuid;
        
            // Display a confirmation form
            $confirmmessage = <<<XXXEODXXX
            
     <p class="error">
      <strong>DELETE?</strong><br/>
      Are you sure you want to delete user "{$deleteusername}"?<br/><i>(This will permanently delete this user's quiz attempts and scores. This action cannot be undone.)</i><br/><br/>
      <a class="button" href="users.php?deleteuserid={$_GET['deleteuserid']}&amp;confirmdelete=true" title="Click here to confirm the deletion."><span class="yes">Yes</span></a>
      &nbsp;
      <a class="button" href="users.php" title="Click here to cancel the deletion."><span class="no">No</span></a>
     </p>
        
XXXEODXXX;
        }
    }
}

$sectionname = 'users';
require_once('_page_header.php');

// Get any message which has been provided in session data
$message = '';
$errormessage = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
if (isset($_SESSION['errormessage'])) {
    $errormessage = $_SESSION['errormessage'];
    unset($_SESSION['errormessage']);
}

?>


<h3>Users</h3>
<p>
 This section lists all of the users of your quizHUD system. You can see their UUID and avatar names (if known).
</p>

<?php

// Display the confirmation message, if we have one
if (!empty($confirmmessage)) echo $confirmmessage, "<br/>\n";

// Display any messages that were provided
if (!empty($message)) {
     echo '<p class="info">',$message,'</p>';
}
if (!empty($errormessage)) {
     echo '<p class="error">',$errormessage,'</p>';
}
?>


<?php

// Define the URLs for the actions
$actionscores = QUIZHUD_WWW_ROOT.'/browser/user_scores.php';
$actiondelete = QUIZHUD_WWW_ROOT.'/browser/users.php';

// Fetch a list of all the users in the system.
$users = get_users();
if ($users === false) error('Failed to query database for users. Perhaps you have not <a href="install.php" title="Click here to visit the installation page">installed quizHUD</a> yet?');
if (count($users) == 0) {
    echo '<p class="info">No users to display</p>';
} else {

    // Display a table of all users
    echo "<table id=\"userlist\" class=\"data\">\n";
    echo "<tr class=\"header\"><th>ID</th><th>UUID</th><th>Avatar Name</th><th>&nbsp;</th></tr>\n";
    
    // Go through each user
    $lightrow = true;
    foreach ($users as $u) {
        if (!empty($deleteuser) && $deleteuser->id == $u->id) {
            echo "<tr class=\"delete\">\n";
            $lightrow = !$lightrow;
        } else if ($lightrow) {
            echo "<tr class=\"light\">\n";
            $lightrow = false;
        } else {
            echo "<tr class=\"dark\">\n";
            $lightrow = true;
        }
        
        echo "<td class=\"id\">{$u->id}</td>";
        echo "<td class=\"uuid\">{$u->uuid}</td>";
        echo "<td class=\"name\">{$u->name}</td>";
        
        echo '<td class="action">';
        // Action: quiz scores
        echo "<a href=\"{$actionscores}?userid={$u->id}\" title=\"Click here to view this user's quiz scores.\"><img src=\"img/book_go.png\" alt=\"quiz scores\"/></a>\n";
        
        // Action: delete user
        echo "<a href=\"{$actiondelete}?deleteuserid={$u->id}\" title=\"Delete this user.\"><img src=\"img/delete.png\" alt=\"delete\"/></a>\n";
        
        echo '</td>';
        
        echo "</tr>\n";
    }
    
    echo "</table>\n";
}

?>

<?php
require_once('_page_footer.php');
?>
<?php

//////////
// QuizHUD user scores.
// Displays a user's scores on all quizzes.
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


$sectionname = 'users';
require_once('_page_header.php');

// We need to load a user
$userid = fetch_param_integer('userid', 0);
if ($userid == 0) error("Expected parameter 'userid'.");
$user = get_user($userid);
if (!$userid) error("Failed to load user $userid.");
// Extract some user data
$username = '';
if (!empty($user->name)) $username = $user->name;
else $username = '('.$user->uuid.')';

?>


<h3>User Scores</h3>
<p>
 The table below shows the score which this user achieved on each quiz.
 You can click the icons beside each quiz to see more detailed information about the user's attempts,
  or to view more general information about all users' scores on that quiz.
 (Note: this page can take time to display if there are lots of quizzes).
</p>

<?php

// Define the basic URLs for performing actions
$actiondetails = QUIZHUD_WWW_ROOT.'/browser/quiz_attempts.php';
$actiongeneral = QUIZHUD_WWW_ROOT.'/browser/quiz_results.php';

// Load all quizzes
$quizzes = get_quizzes();
if ($quizzes === false) error("Failed to load list of quizzes.");
if (count($quizzes) == 0) {
    echo '<p class="info">No quizzes in the system.</p>';
} else {    

    echo '<p style="font-size:120%; text-align:center;">User: <b>',$username,'</b></p>';
    
    // Display a table of all quizzes
    echo "<table id=\"resultslist\" class=\"data\">\n";
    echo "<tr class=\"header\"><th>Quiz</th><th>Score</th><th>&nbsp;</th></tr>\n";
    
    // Go through each quiz
    $lightrow = true;
    foreach ($quizzes as $q) {

        // Alternate between light and dark rows
        if ($lightrow) {
            echo "<tr class=\"light\">\n";
            $lightrow = false;
        } else {
            echo "<tr class=\"dark\">\n";
            $lightrow = true;
        }
        
        // Output the quiz name
        echo "<td class=\"name\">{$q->name}</td>";
        
        // Display the user's score
        $score = calculate_score($q, $user);
        if ($score === false) $score = '-';
        else $score .= '%';
        echo "<td class=\"score\">{$score}</td>";

        echo "<td class=\"action\">";

        // Action: more details
        echo "<a href=\"{$actiondetails}?quizid={$q->id}&amp;userid={$user->id}\" title=\"Click here to view the details of this user's attempts at this quiz.\"><img src=\"img/magnifier.png\" alt=\"details\"/></a>\n";
        // Action: general details
        echo "<a href=\"{$actiongeneral}?quizid={$q->id}\" title=\"Click here to view all users' scores for this quiz.\"><img src=\"img/book_open.png\" alt=\"scores\"/></a>\n";

        echo "</td>";
        
        echo "</tr>\n";
    }
    
    echo "</table>\n";
}

?>

<?php
require_once('_page_footer.php');
?>

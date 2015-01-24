<?php

//////////
// QuizHUD quiz results.
// Displays the scores for all users on a given course.
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


$sectionname = 'quiz';
require_once('_page_header.php');


// Find out which quiz we should be viewing
$quizid = fetch_param_integer('quizid', 0);
$quiz = get_quiz($quizid);
if (!$quiz) error("Failed to load results for quiz $quizid.");


?>


<h3>Quiz Results</h3>
<p>
 Below are the current scores for all users on the selected quiz -- the table will show avatar names if possible,
  or UUIDs otherwise.
 Click the icon to the right of each entry to get a detailed view of the user's attempt(s).
 (Note: displaying this page can take time if there is a large number of users).
</p>

<!--<div style="text-align:center;">
 <a class="button" href="<?php echo QUIZHUD_WWW_ROOT.'/browser/quiz.php'; ?>" title="Click here to go back to the viewing all quizzes."><span class="quiz">&lt;&lt; Back to Quizzes</span></a><br/><br/>
</div>-->

<?php

// Define the basic URLs for performing actions
$actiondetails = QUIZHUD_WWW_ROOT.'/browser/quiz_attempts.php';

// Fetch a list of all the users in the system.
$users = get_users();
if ($users === false) error('Failed to query database for users.');
if (count($users) == 0) {
    echo '<p class="info">No results in the system.</p>';
} else {    

    echo '<p style="font-size:120%; text-align:center;">Quiz: <b>',$quiz->name,'</b><br/>Grading method: <b>',$quiz->method,'</b></p>';
    
    // Display a table of all users
    echo "<table id=\"resultslist\" class=\"data\">\n";
    echo "<tr class=\"header\"><th>Avatar</th><th>Score</th><th>&nbsp;</th></tr>\n";
    
    // Go through each user
    $lightrow = true;
    foreach ($users as $u) {

        // Alternate between light and dark rows
        if ($lightrow) {
            echo "<tr class=\"light\">\n";
            $lightrow = false;
        } else {
            echo "<tr class=\"dark\">\n";
            $lightrow = true;
        }
        
        // If we have an avatar name, then output it
        if (!empty($u->name)) echo "<td class=\"name\">{$u->name}</td>";
        else echo "<td class=\"name\">{$u->uuid}</td>";
        
        // Display the user's score
        $score = calculate_score($quiz, $u);
        if ($score === false) $score = '-';
        else $score .= '%';
        echo "<td class=\"score\">{$score}</td>";

        echo "<td class=\"action\">";

        // Action: more details
        echo "<a href=\"{$actiondetails}?quizid={$quizid}&amp;userid={$u->id}\" title=\"Click here to view the details of this user's attempts at this quiz.\"><img src=\"img/magnifier.png\" alt=\"details\"/></a>\n";
     

        echo "</td>";
        
        echo "</tr>\n";
    }
    
    echo "</table>\n";
}

?>

<?php
require_once('_page_footer.php');
?>

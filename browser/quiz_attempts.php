<?php

//////////
// QuizHUD quiz attempts.
// Displays detailed information about a user's attempts at each question.
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
if ($quizid == 0) error("Expected parameter 'quizid'.");
$quiz = get_quiz($quizid);
if (!$quiz) error("Failed to load quiz $quizid.");

// Get a list of all questions in this quiz (but don't skip any)
$questions = get_questions($quizid, false, false);
if ($questions === false) error("Failed to load questions for quiz $quizid.");

// We need to load a user
$userid = fetch_param_integer('userid', 0);
if ($userid == 0) error("Expected parameter 'userid'.");
$user = get_user($userid);
if (!$userid) error("Failed to load user $userid.");
// Extract some user data
$userurl = QUIZHUD_WWW_ROOT.'/browser/user_scores.php?userid='.$user->id;
$username = '';
if (!empty($user->name)) $username = $user->name;
else $username = '('.$user->uuid.')';
$userlink = '<a class="button" href="'.$userurl.'" title="Click here to view this user\'s scores on all quizzes."><span class="user">'.$username.'</span></a>';


?>


<h3>Quiz Attempts</h3>
<p>
 The tables below show every attempt made at each question by the selected user.
 Note: displaying this page can take some time if there are lots of questions and/or lots of attempts.
</p>

<!--<div style="text-align:center;">
 <a class="button" href="<?php echo QUIZHUD_WWW_ROOT.'/browser/quiz.php'; ?>" title="Click here to go back to the viewing all quizzes."><span class="quiz">&lt;&lt; Back to Quizzes</span></a>&nbsp;
 <a class="button" href="<?php echo QUIZHUD_WWW_ROOT.'/browser/quiz_results.php?quizid=',$quizid; ?>" title="Click here to go back to viewing the general results for this quiz."><span class="results">&lt;&lt; Back to General Results</span></a>
 <br/><br/>
</div>-->

<?php

// Create a link back to the general quiz information
$quizlink = '<a class="button" href="'.QUIZHUD_WWW_ROOT.'/browser/quiz_results.php?quizid='.$quizid.'" title="Click here to go back to viewing the general results for this quiz."><span class="quiz">'.$quiz->name.'</span></a>';

// Display quiz information
$score = calculate_score($quiz, $user);
if ($score) $score .= '%';
else $score = '(unknown)';
echo '<p style="font-size:120%; text-align:center;">Quiz: <b>',$quizlink,'</b><br/>Grading method: <b>',$quiz->method,'</b><br/><br/>';
echo 'User: <b>',$userlink,'</b><br/>Score: <b>',$score,'</b></p>';

// Go through each question
$qnum = 1;
foreach ($questions as $question) {
    // Display the header info about this question
    echo '<div class="questionsection" id="question',$question->id,'">';
    
    echo '<p class="questionheader">';
    echo "{$qnum}. {$question->text}";
    echo "</p>\n";
    
    echo '<p class="questionsummary">';
    echo "Weight: {$question->weight}<br/>";
    echo "Type: {$question->type}";
    echo "</p>\n";
    
    
    // Get all attempts at this question by the user
    $attempts = get_attempts($question->id, $user->id);
    if ($attempts === false) {
        echo "<p class=\"error\">Failed to fetch user attempts for this question.</p>\n";
    } else if (count($attempts) == 0) {
        echo "<p class=\"info\">No attempts made on this question.</p>\n";
    } else {
    
        // Display a table of attempts
        echo '<table class="attemptinfo data">';
        echo '<tr><th>ID</th><th>Answer</th><th>Score</th><th>Time</th></tr>',"\n";
        $lightrow = true;
        foreach ($attempts as $attempt) {
            // Alternate between light and dark rows
            if ($lightrow) {
                echo "<tr class=\"light\">\n";
                $lightrow = false;
            } else {
                echo "<tr class=\"dark\">\n";
                $lightrow = true;
            }
            
            // Get the answer to which this attempt relates, if any
            $attemptanswer = find_answer($question->id, $attempt->answer);
            $attemptscore = 0.0;
            $attempttext = $attempt->answer;
            if ($attemptanswer) {
                $attemptscore = $question->weight * $attemptanswer->value;
                $attempttext = $attemptanswer->text.' <i style="font-size:80%;">('.$attempt->answer.')</i>';
            }
            
            
            // Output the items of data
            echo '<td class="id">',$attempt->id,'</td>';
            echo '<td class="answer">',$attempttext,'</td>';
            
            // If this was a survey question, then no score needs displayed
            if ($question->weight == 0.0) {
                echo '<td class="score"><i><acronym title="Not Applicable -- this is an unassessed question.">n/a</acronym></i></td>';
            } else {
                echo '<td class="score">',$attemptscore,'</td>';
            }
            
            echo '<td class="time">',date('Y-m-s H:i:s', (int)$attempt->timestamp),'</td>';
            
            echo "</tr>\n";
        }
        echo '</table>';
    }
    
    echo "</div>\n\n";
    
    $qnum++;
}

?>

<?php
require_once('_page_footer.php');
?>

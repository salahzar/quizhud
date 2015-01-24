<?php
//////////
// Renders quiz information in HTML.
//
// Note: the "quiz_data.php" can be requested to obtain quiz data, or to log a question attempt.
//
// Note: in any instance, if parameter 'quizid' is omitted or 0, then the 'current' quiz will be used where possible.
//
// Information about a specific quiz can be obtained by provided parameter "quizid" with an integer ID of a quiz.
//  (User-specific information, such as score on a quiz, can be obtained by also provided "uuid" parameter with the avatar's UUID)
//
// A specific question can be rendered by providing "questionid" with the integer ID of a question (in addition to the "quizid").
//
// The result of a specific attempt can be displayed by also passing in "attemptid", giving the integer ID of an attempt.
//  (Must include "quizid", "questionid", and "uuid", as all these are checked before displaying any results).
//
// Author: Peter R. Bloomfield (SL: Pedro McMillan)
//
//
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
//
//////////

require_once('_page_header.php');
echo '<div style="font-size:11pt;">';

// Obtain our parameters
$uuid = fetch_param_string('uuid');
$avname = fetch_param_string('avname', '');
$quizid = fetch_param_integer('quizid', 0);
$questionid = fetch_param_integer('questionid', 0);
$attemptid = fetch_param_string('attemptid');

// If not quiz ID was given, then use the current quiz ID
if ($quizid <= 0) {
    $curquizid = get_current_quiz();
    if ($curquizid) $quizid = $curquizid;
}

// Fetch data about the requested items
$quiz = get_quiz($quizid);
if (!$quiz) error("Failed to load quiz.");

$questions = get_questions($quizid);
$question = false;
if (isset($questions[$questionid])) $question = $questions[$questionid];
$answers = get_answers($questionid);

// If user data was provided, then load it
$user = false;
if ($uuid !== null) $user = load_user($uuid, $avname);
// Load attempt information about the quiz
if ($user && $quiz) {
    $score = calculate_score($quiz, $user);
}

// Try to get hold of the user's quiz attempt
$attempt = false;
if ($user) $attempt = get_attempt($questionid, $user->id, $attemptid);



// Check what we need to display
if ($questionid === null || $questionid < 1) {
///// DISPLAY QUIZ /////

    // Find out if there are any assessed questions in the quiz.
    // (Determine if it's just a survey).
    $assessedquestions = get_questions($quizid, true);
    $issurvey = false;
    if (!$assessedquestions || count($assessedquestions) == 0) $issurvey = true;

    // Display the header information (name of the quiz/survey)
    if ($issurvey) echo "<h2>Survey: {$quiz->name}</h2>\n";
    else echo "<h2>Quiz: {$quiz->name}</h2>\n";
    
    // Show how many questions there are in total
    echo '<p style="font-style:italic;">Number of questions: ';
    if (is_array($questions)) echo count($questions);
    else echo '(unknown)';
    echo "</p>\n";
    
	// Display the score, if appliccable
    if (!$issurvey && isset($score)) {
        if ($score === false) echo '<p style="font-weight:bold;">You current score: (not attempted)',"</p>\n";
        else echo '<p style="font-weight:bold;">Your current score: ',round($score,1),"%</p>\n";
    }
    echo "<p style=\"font-size:150%; color:#0000ff; text-decoration:underline; text-align:center;\">Click here to start a new attempt.</p>\n";
    
    
} else if ($attemptid === null) {
///// DISPLAY QUESTION /////

    // Make sure we have a quiz, question, and answers loaded
    if (!$quiz) error('Failed to load quiz.');
    if (!$question) error('Failed to load question.');
    if ($answers === false) error('Failed to load answers.');
    
    // Display the question itself
    echo "<h3>Question: {$question->text}</h3>\n";
    // Indicate if it is not assessed
    if ($question->weight == 0.0) echo '<p style="font-style:italic;">(This question is not assessed)</p>';
    
    // What kind of question is it?
    switch ($question->type) {
    case 'multiplechoice':
        // Go through each possible answer
        foreach ($answers as $ans) {
            echo " {$ans->shortname}. {$ans->text}<br/>\n";
        }
        break;
        
    case 'explore':
        // Give user instructions
        echo "<b>Answer by clicking a feature in your environment.</b>\n";
        break;
        
    default:
        error("Unknown question type, \"{$question->type}\".");
        break;
    }
    

} else {
///// DISPLAY ATTEMPT SUMMARY /////

    // Make sure a user, quiz, question and attempt are loaded
    if (!$user) error('Failed to load user.');
    if (!$quiz) error('Failed to load quiz.');
    if (!$question) error('Failed to load question.');
    if (!$attempt) error('Failed to load attempt.');
    
    // Start by displaying the question information
    echo "<h3>Question: {$question->text}</h3>\n";
    // Indicate if it is not assessed
    if ($question->weight == 0.0) echo '<p style="font-style:italic;">(This question is not assessed)</p>';
    
    // Try to load the answer which the attempt relates to
    $attemptanswer = find_answer($questionid, $attempt->answer);
    
    // Check what type of question it is
    switch ($question->type) {
    case 'multiplechoice':
        // Was the answer invalid?
        if (!$attemptanswer) {
            echo '<h1 style="color:red; text-align:center;">Invalid Answer</h1>';
        } else {
            // The response depends on whether or not the question is assessed
            if ($question->weight == 0.0) {
                // Not assessed
                echo "<p style=\"color:green; font-size:120%;\">Thank you for your response.</p>";
            } else {
                // Assessed
                //echo "<p>You selected option ({$attempt->answer}): \"{$attemptanswer->text}\"</p>"; // Don't display the answer... plagiarism risk!
                if ($attemptanswer->value == 1.0) echo '<h1 style="color:green; text-align:center;">Correct</h1>';
                else if ($attemptanswer->value <= 0.0) echo '<h1 style="color:red; text-align:center;">Incorrect</h1>';
                else echo '<h1 style="color:orange; text-align:center;">Partly Correct</h1>';
            }
        }
        break;
        
    case 'explore':
        // The response depends on whether or not the question is assessed
        if ($question->weight == 0.0) {
            // Not assessed
            echo "<p style=\"color:green; font-size:120%;\">Thank you for your response.</p>";
        } else {
            // Assessed
            // Get the value
            $value = 0.0;
            if ($attemptanswer) $value = $attemptanswer->value;
            
            //echo "<p>You selected \"{$attempt->answer}\"</p>"; // Don't display the answer... plagiarism risk!
            if ($value == 1.0) echo '<h1 style="color:green; text-align:center;">Correct</h1>';
            else if ($value <= 0.0) echo '<h1 style="color:red; text-align:center;">Incorrect</h1>';
            else echo '<h1 style="color:orange; text-align:center;">Partly Correct</h1>';
        }
        break;
        
    default:
        error("Unknown question type, \"{$question->type}\".");
        break;
    }
    
    // Display an instruction for continuing
    echo "<p style=\"font-size:150%; color:#0000ff; text-decoration:underline; text-align:center;\">Click here to continue.</p>\n";
}

echo '</div>';
require_once('_page_footer.php');
?>


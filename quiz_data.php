<?php
//////////
// Fetches script-friendly quiz data, and makes quiz attempts.
//
// Note: all requests require the "pwd" parameter, an an internal security measure.
// If at any point, "uuid" is specified but not recognised, it will be added to the database.
// Parameter "avname" can also be specified to provide the avatar name, but it will not be used internally.
//
// A list of all quizzes can be obtained by requesting the script with no additional parameters.
// The quizzes will be returned with one per line, "id|name".
//
// Note: in all cases, if parameter 'quizid' is omitted or 0, then the 'current' quiz will be used, where possible.
//
// Information about a specific quiz can be obtained by specifying parameter "quizid", containing the integer ID of a quiz.
// The first line will contain the name of the quiz, and the second will contain a pipe-separated list of question IDs (e.g. "17|4|5").
// If UUID is specified in parameter "uuid", then the third line will contain the user's current score on the quiz.
//
// Information about a specific question can be obtained by specifying parameters "quizid" and "questionid".
// The first line will contain the ID of the question, followed by whether or not it is assessed (0 or 1), and finally its type (explore or multiplechoice).
//  E.g. "17|1|explore" or "23|0|multiplechoice".
// Each subsequent line will contain information about a potential answer, with format "shortname|value".
// In multiple choice, this will list every available answer, and the shortname will usually be "a", "b", "c" etc..
// Explore questions will usually only mention the correct answer here.
//
// An attempt at a question can be logged by specifying the quiz and question in "quizid" and "questionid", the shortname of the answer in "answer",
//  and the UUID of the avatar in parameter "uuid".
// The first line of the response should be "CORRECT", "PART CORRECT", "INCORRECT", "INVALID", or "UNASSESSED".
// If the response was anything but INVALID, then the ID of the attempt will be in the second line (as an integer)
// If the database insertion fails, then the response will be "ERROR".
//
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

// Suppress error display
ini_set('display_errors', '0');

require_once('config.php');
require_once('lib.php');

// Make sure this request is authorised
require_password();

// Obtain our parameters
$quizid = fetch_param_integer('quizid', 0);
$questionid = fetch_param_integer('questionid', 0);
$answertext = fetch_param_string('answer');
$uuid = fetch_param_string('uuid');
$avname = fetch_param_string('avname', '');

// If the quiz ID was not specified, then use the current quiz ID
if ($quizid <= 0) {
    $curquizid = get_current_quiz();
    if ($curquizid) $quizid = $curquizid;
    else exit('ERROR');
}

// Fetch data about the requested items
$quiz = get_quiz($quizid);
$questions = false;
$questions = get_questions($quizid);
$answers = get_answers($questionid);
$answer = find_answer($questionid, $answertext);

// If user data was provided, then load it
$user = false;
if ($uuid !== null) $user = load_user($uuid, $avname);
// Load attempt information about the quiz
if ($user && $quiz) {
    $score = calculate_score($quiz, $user);
}


// Determine what mode we're in
if ($quizid === null || $quizid < 1) {
///// BROWSE QUIZZES /////
    
    // Get and output all quizzes
    $quizzes = get_quizzes();
    if (!$quizzes) exit();
    foreach ($quizzes as $q) {
        echo "{$q->id}|{$q->name}\n";
    }

} else if ($questionid === null || $questionid < 1) {
///// QUIZ INFORMATION /////

    // Make sure we have quiz data here
    if (!$quiz || !$questions) exit();
    // Output the quiz name on the first line
    echo $quiz->name,"\n";
    // Output the list of question IDs on the second (pipe-delimeted)
    $first = true;
    foreach ($questions as $id => $question) {
        if ($first) $first = false;
        else echo '|';
        echo $id;
    }
    echo "\n";
    
    // If we have a user score, then output it as well
    if (isset($score)) echo $score;
    

} else if ($answertext === null || $uuid == null) {
///// QUESTION INFORMATION /////

    // Make sure we have a specific question and its answers available
    if (!$questions || !isset($questions[$questionid]) || $answers === false) exit();
    // Output summary information
    echo $questions[$questionid]->id,'|';
    if ($questions[$questionid]->weight == 0.0) echo '0|';
    else echo '1|';
    echo $questions[$questionid]->type,"\n";
    // Output each answer on subsequent lines
    foreach ($answers as $ans) {
        echo $ans->shortname,'|',$ans->value,"\n";
    }

} else {
///// ATTEMPTING THE QUIZ /////

    // An attempt has been made.
    // Make sure we have question data and user available.
    if (!$questions || !isset($questions[$questionid]) || !$user) exit('ERROR');
    
    // Attempt to find the appropriate answer
    $attemptanswer = find_answer($questionid, $answertext);
    $value = 0.0;
    if ($attemptanswer) $value = $attemptanswer->value;
    
    // If the answer was not found, and it was multiple choice, then it was an invalid attempt
    if (!$attemptanswer && $questions[$questionid]->type == 'multiplechoice') exit('INVALID');
    
    // Add an attempt to the database
    $attemptid = add_attempt($questionid, $user->id, $answertext);
    if ($attemptid === false) exit("ERROR");
    
    // Indicate the status of the answer
    if ($questions[$questionid]->weight > 0.0) {
        if ($value == 1.0) echo "CORRECT\n";
        else if ($value <= 0.0) echo "INCORRECT\n";
        else echo "PART CORRECT\n";
    } else {
        echo "UNASSESSED\n";
    }
    
    echo $attemptid;
}

exit();

?>

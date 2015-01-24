<?php
// Quiz editing page.
// Can operate in several modes:
//  - View all quizzes (default)
//  - View all questions in a quiz
//  - View specific question
//  - Edit question
//  - Add question
//
// Quizzes and questions can be deleted from the respective view modes.
// By default, the script adtops the mode to view all quizzes.
//
// If parameter "quiz" is specified, then a single quiz is being viewed.
// If parameters "quiz" and "question" are specified, then a single question is being viewed.
// If parameters "quiz" and "editquestion" are specified, then a single question is being edited.
// In edit modee, if "editquestion" is given value "new", then a question is being added to this quiz.
//

require_once('config.php');

// Grab our parameters
$quiz = 0;
$question = 0;
$editquestion = 0;
if (isset($_REQUEST['quiz'])) $quiz = $_REQUEST['quiz'];
if (isset($_REQUEST['question'])) $question = $_REQUEST['question'];
if (isset($_REQUEST['editquestion'])) $editquestion = $_REQUEST['editquestion'];

// Make sure the parameters are db-safe
$addquestion = false;
$quiz = (integer)$quiz;
$question = (integer)$question;
if ($editquestion == 'new') $addquestion = true;
else $editquestion = (integer)$editquestion;


///// FETCH QUIZZES /////



///// FETCH QUESTIONS /////



///// FETCH ANSWERS /////


?>

<html>
<head>
<title>QuizHUD Quiz Editor</title>
</head>
<body>



</body>
</html>


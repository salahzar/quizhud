<?php

//////////
// QuizHUD quiz editing form.
// Can be used to edit an existing quiz, or add a new one.
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

// We need to know if we are in edit mode
$editmode = false;

// This variable will store our quiz object
$quiz = null;
// Any messages to be shown with returned data will be put in here
$message = '';
$errormessage = '';
$confirmmessage = '';

// Has a quiz been specified in the GET, POST or SESSION values?
if (isset($_POST['id'])) {
	// POST - submitting quiz changes
	// Put the relevant POST data into session values, and reload.
	// (This eliminates refresh issues, as browser often complain about re-sending POST data!)
	$_SESSION['id'] = fetch_param_integer('id');
	$_SESSION['name'] = fetch_param_string('name');
	$_SESSION['method'] = fetch_param_string('method');
	
	// Reload the page
	header('Location: '.QUIZHUD_WWW_ROOT.'/browser/quiz_edit.php?quizid='.$_POST['id']);
	exit();

} else if (isset($_SESSION['id'])) {
	// SESSION - applying quiz changes
    // Grab the all the values from the session data, and unset them
    $quiz = new QuizHUDQuiz($_SESSION['id'], stripslashes($_SESSION['name']), stripslashes($_SESSION['method']));
    
    unset($_SESSION['id']);
    unset($_SESSION['name']);
    unset($_SESSION['method']);
    
    // Attempt to apply the values.
    // Are we creating or updating?
    if ($quiz->id <= 0) {
    	// Creating
    	$insertresult = mysql_query("
    		INSERT INTO qh_quiz (name, method)
    		VALUES ('{$quiz->name}', '{$quiz->method}')
    	");
    	
    	// Were we successful?
    	if ($insertresult) {
    		$_SESSION['message'] = 'Quiz created successfully.';
    		$quiz->id = mysql_insert_id();
            // We need to redirect again to add the appropriate GET parameter.
            // (The forms logic could probably be improved here to avoid this,
            //  but for now it at least allows clean page refreshing).
            if (!headers_sent()) {
                header('Location: '.QUIZHUD_WWW_ROOT.'/browser/quiz_edit.php?quizid='.$quiz->id);
                exit();
            }
            
    	} else {
    		$errormessage = 'Failed to create quiz.<br/>'.mysql_error();
    		$editmode = true;
    	}
    	
    } else {
    	// Updating
    	$updateresult = mysql_query("
    		UPDATE qh_quiz
    		SET name = '{$quiz->name}', method = '{$quiz->method}'
    		WHERE id = {$quiz->id}
    		LIMIT 1
    	");
    	
    	// Were we successful?
    	if ($updateresult) {
    		$message = 'Quiz updated successfully.';
    	} else {
    		$errormessage = 'Failed to update quiz.<br/>'.mysql_error();
            $editmode = true;
    	}
    }
    
} else if (isset($_GET['quizid'])) {
	// GET - edit/creating quiz
    // Are we being asked to create a new quiz?
    if ($_GET['quizid'] == 'new') {
        // Yes - create an empty quiz structure
        $quiz = new QuizHUDQuiz(0, '', 'last');
        $editmode = true;
    } else {
        // No - load our quiz from the database
        $quiz = get_quiz((int)$_GET['quizid']);
        if ($quiz === false) error("Failed to load quiz {$_GET['quizid']}.");
        $editmode = fetch_param_boolean('edit', false);
        
        // Have we been asked to delete a question?
        if (isset($_GET['deletequestion'])) {
        	// Has it been confirmed yet?
        	if (isset($_GET['confirmdelete'])) {
        		// Yes - do the deletion
				if (delete_question($_GET['deletequestion'])) $_SESSION['message'] = 'Deletion successful.';
				else $_SESSION['message'] = 'Deletion failed: '.mysql_error();
				
				// Redirect back to this page again
				header('Location: '.QUIZHUD_WWW_ROOT.'/browser/quiz_edit.php?quizid='.$quiz->id);
				exit($_SESSION['message']);
				
        	} else {
        		// Attempt to load the question to be deleted
        		$deletequestion = get_question($quiz->id, (int)$_GET['deletequestion']);
        		// Display a confirmation form
		        $confirmmessage = <<<XXXEODXXX
		        
				 <p class="error">
				  <strong>DELETE?</strong><br/>
				  Are you sure you want to delete this question?<br/><i>(This will remove all associated answers and attempts by users. This action cannot be undone.)</i><br/><br/>
				  <a class="button" href="quiz_edit.php?quizid={$quiz->id}&amp;deletequestion={$_GET['deletequestion']}&amp;confirmdelete=true" title="Click here to confirm the deletion."><span class="yes">Yes</span></a>
				  &nbsp;
				  <a class="button" href="quiz_edit.php?quizid={$quiz->id}" title="Click here to cancel the deletion."><span class="no">No</span></a>
				 </p>
		    
XXXEODXXX;
			}
        }
    }
} else {
	error("Expected parameter 'quizid'.");
}

// Pickup and delete any session message which may have come our way
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
if (isset($_SESSION['errormessage'])) {
    $errormessage = $_SESSION['errormessage'];
    unset($_SESSION['errormessage']);
}

$sectionname = 'quiz';
require_once('_page_header.php');

// Change the intro of the page depending on the mode we're in
if ($quiz->id <= 0) {
    echo "<h2>Adding New Quiz</h2>\n";
    echo <<<XXXEODXXX
    <p>
	 To create a new quiz, enter a title for it, and select a grading method (you can change these values later).
	 Click the "Save" button, and you will then be able to add questions.
    </p>
XXXEODXXX;
} else {
    echo "<h2>Editing Quiz</h2>\n";
    echo <<<XXXEODXXX
    <p>
	 You can alter the quiz details by clicking the "Edit" button, then using the form you are provided.
	 You can also add new questions by clicking the "New Question" button, or edit existing questions (if there are any)
	  by clicking the appropriate edit buttons.
    </p>
XXXEODXXX;
}


// Display the confirmation message, if we have one
if (!empty($confirmmessage)) echo $confirmmessage, "<br/>\n";

// Display any message that was returned
if (!empty($message)) {
     echo '<p class="info">',$message,'</p>';
}

// Display any error message that was returned
if (!empty($errormessage)) {
     echo '<p class="info">',$errormessage,'</p>';
}
?>

<br/><br/>

<div id="quizdata">
<?php
// Should we display regular quiz data, or an editing form?
if ($editmode) {
	// Define our list of valid grading methods
	$gradingmethods = array('last', 'first', 'worst', 'best', 'mean');
	// Editing form
	?>
	<form action="quiz_edit.php" method="post"><div>
	<input type="hidden" name="id" value="<?php echo $quiz->id; ?>" />
	
	<label for="name">Name: </label>
	<input type="text" name="name" id="name" size="35" maxlength="255" style="width:95%;" value="<?php echo $quiz->name; ?>" /><br/><br/>
	
	<label for="method">Grading Method: </label>
	<select name="method" id="method" size="1">
	 <?php
	 	foreach ($gradingmethods as $gm) {
	 		echo "<option value=\"$gm\" ";
	 		if ($gm == $quiz->method) echo "selected=\"selected\" ";
	 		echo ">$gm</option>\n";
	 	}
	 ?>
	</select>
	
	<br/><br/>

	<input type="submit" value="Save"/>
	&nbsp;
	<?php

	// Output a 'Cancel' link, depending on whether or not this is a new page.
	// (For new pages, Cancel should go back to the Quizzes page. For updating a quiz, Cancel should just de-activate edit mode).
	if ($quiz->id <= 0) echo '<a href="',QUIZHUD_WWW_ROOT,'/browser/quiz.php" title="Click here to cancel this new quiz.">Cancel</a>';
	else echo '<a href="',QUIZHUD_WWW_ROOT,'/browser/quiz_edit.php?quizid=',$quiz->id,'" title="Click here to cancel edit mode.">Cancel</a>';
	
	// Close the form
	echo '	</div></form>';
	
	// If this is a new quiz, then stop here
	if ($quiz->id <= 0) {
		echo '</div>';
		require_once('_page_footer.php');
		exit();
	}

} else {
	// Just the regular quiz data
	echo 'Name: <b>',$quiz->name,"</b><br/>\n";
	echo 'Grading Method: <b>',$quiz->method,"</b><br/><br/>\n";
	
	// Add an editing link
	echo '<a class="button" href="',QUIZHUD_WWW_ROOT,'/browser/quiz_edit.php?quizid=',$quiz->id,'&amp;edit=true" title="Click here to edit the quiz data."><span class="edit">Edit</span></a>';
}
?>
</div>

<div style="text-align:center; margin:16px;">
	<a class="button" href="<?php echo QUIZHUD_WWW_ROOT,'/browser/question_multichoice.php?quizid=',$quiz->id; ?>&amp;questionid=new" title="Click here to add a new multiple-choice question to your quiz."><span class="question_multiplechoice">New Multiple Choice Question</span></a>
	&nbsp;
	<a class="button" href="<?php echo QUIZHUD_WWW_ROOT,'/browser/question_explore.php?quizid=',$quiz->id; ?>&amp;questionid=new" title="Click here to add a new explore question to your quiz."><span class="question_explore">New Explore Question</span></a>
</div>

<?php

// Get a list of all questions in this quiz (and don't skip any)
$questions = get_questions($quiz->id, false, false);
if ($questions === false) error("Failed to load questions for quiz {$quiz->id}.");
if (count($questions) == 0) {
	echo '<p class="info">No questions yet.</p>';
} else {
	// Go through each question
	$qnum = 1;
	foreach ($questions as $question) {
		// Display the header info about this question
		echo '<div class="questionsection" id="question',$question->id,'"';
		if (!empty($deletequestion) && $deletequestion->id == $question->id) echo ' style="border:solid 2px red;"';
		echo '>';
		
		echo '<p class="questionheader">';
		
		echo "{$qnum}. {$question->text}";
		echo "</p>\n";
		
		echo '<p class="questionsummary">';
		
		// Edit button (target depends on the question type)
		if ($question->type == 'multiplechoice') {
			echo '<a class="button" href="',QUIZHUD_WWW_ROOT,'/browser/question_multichoice.php?quizid=',$quiz->id,'&amp;questionid=',$question->id,'" title="Click here to edit this question."><span class="edit">Edit</span></a>&nbsp;';
		} else if ($question->type == 'explore') {
			echo '<a class="button" href="',QUIZHUD_WWW_ROOT,'/browser/question_explore.php?quizid=',$quiz->id,'&amp;questionid=',$question->id,'" title="Click here to edit this question."><span class="edit">Edit</span></a>&nbsp;';
		}		
		
		// Delete button
		echo '<a class="button" href="',QUIZHUD_WWW_ROOT,'/browser/quiz_edit.php?quizid=',$quiz->id,'&amp;deletequestion=',$question->id,'" title="Click here to delete this question."><span class="delete">Delete</span></a><br/><br/>';
		
		
		echo "Weight: {$question->weight}<br/>";
		echo "Type: {$question->type}";
		echo "</p>\n";
		
		
		// Get all answers in this question
		$answers = get_answers($question->id);
		if ($answers === false) {
			echo '<p class="error">Failed to load answers</p>';
		} else if (count($answers) == 0) {
			echo '<p class="info">No Answers Listed</p>';
		} else {
			// Go through each answer
		    echo '<table class="answerinfo data">';
		    echo '<tr><th>ID</th><th>Shortname</th><th>Text</th><th>Value</th></tr>',"\n";
		    $lightrow = true;
		    foreach ($answers as $answer) {
		        // Alternate between light and dark rows
		        if ($lightrow) {
		            echo "<tr class=\"light\">\n";
		            $lightrow = false;
		        } else {
		            echo "<tr class=\"dark\">\n";
		            $lightrow = true;
		        }
		        
		        // Output the items of data
		        echo '<td class="id">',$answer->id,'</td>';
		        echo '<td class="shortname">',$answer->shortname,'</td>';
		        echo '<td class="text">',$answer->text,'</td>';
		        echo '<td class="value">',$answer->value,'</td>';
		        
		        echo "</tr>\n";
		    }
		    echo '</table>';
		}
		
		
		echo "</div>\n\n";
	    $qnum++;
	}
}

?>


<?php
require_once('_page_footer.php');
?>

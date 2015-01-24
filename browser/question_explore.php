<?php

//////////
// QuizHUD explore question editing form.
// Can be used to edit an existing explore question, or add a new one.
// Can also edit/add answers to questions.
// Author: Peter R. Bloomfield
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
//////////

require_once('../config.php');
require_once('../lib.php');

start_quizhud_session();
require_login(QUIZHUD_WWW_ROOT.'/browser/quiz.php');

// We have three edit modes: question, answer, and addanswer.
$editmode = fetch_param_string('edit', '');
// Messages from to be displayed will get collected and stored in these variables
$confirmmessage = '';
$message = '';
$errormessage = '';


// Has question edit data been submitted?
if (isset($_POST['save_question'])) {
    // Get the quiz and question IDs
    $quizid = (int)$_POST['quizid'];
    if ($_POST['id'] == 0) $questionid = 'new';
    else $questionid = (int)$_POST['id'];

    // Extract and sanitize our form data
    $formdata_quizid = (int)$_POST['quizid'];
    $formdata_text = htmlentities($_POST['text'], ENT_QUOTES);
    $formdata_weight = (float)$_POST['weight'];

    // Is it a new question?
    if ($_POST['id'] == 0) {
        // Insert it
        $result = mysql_query("
            INSERT INTO qh_question (quizid, text, weight, type)
            VALUES ({$formdata_quizid}, '{$formdata_text}', {$formdata_weight}, 'explore')
        ");
        if ($result) {
            $_SESSION['message'] = 'Added new question successfully.';
            $questionid = mysql_insert_id();
        } else {
            $_SESSION['errormessage'] = 'Failed to add new question.<br/>MySQL: '.mysql_error();
        }
        
    } else {
        // Update it
        $result = mysql_query("
            UPDATE qh_question
            SET text = '{$formdata_text}', weight = {$formdata_weight}
            WHERE id = {$questionid} AND quizid = {$formdata_quizid}
            LIMIT 1
        ");
        if ($result) $_SESSION['message'] = 'Updated question successfully.';
        else $_SESSION['errormessage'] = 'Failed to update question.<br/>MySQL: '.mysql_error();
    }
    
    // Redirect back here to eliminate the POST parameters
    header('Location: '.QUIZHUD_WWW_ROOT."/browser/question_explore.php?quizid={$quizid}&questionid={$questionid}");
    exit();
}

// Has an answer been edited?
if (isset($_POST['save_answers'])) {
    // Extract the quiz and question IDs
    $quizid = (int)$_POST['quizid'];
    $questionid = (int)$_POST['questionid'];
    
    // The answer data will be provided as 'text_x', 'shortname_x' and 'value_x', where 'x' is the answer ID.
    // Go through each post parameter.
    $result = true;
    foreach ($_POST as $parname => $parvalue) {
        // Look for anything start with 'text_'
        if (strpos($parname, 'text_') !== 0) continue;
        // Extract the ID, and find the 'shortname' and 'value' parameters
        $answerid = (int)substr($parname, 5);
        if ($answerid <= 0) continue;
        $shortname = htmlentities($_POST['shortname_'.$answerid], ENT_QUOTES);
        $value = (float)$_POST['value_'.$answerid];
        // Sanitize the text part
        $text = htmlentities($parvalue, ENT_QUOTES);
        
        // Update the entry
        $result = $result && mysql_query("
            UPDATE qh_answer
            SET text = '{$text}', shortname = '{$shortname}', value = {$value}
            WHERE id = {$answerid} AND questionid = {$questionid}
        ");
    }
    
    // Check if it was successful
    if ($result) $_SESSION['message'] = 'Updated answers successfully.';
    else $_SESSION['errormessage'] = 'Failed to update answers.<br/>MySQL: '.mysql_error();
    
    // Reload the page to eliminate the POST parameters
    header('Location: '.QUIZHUD_WWW_ROOT."/browser/question_explore.php?quizid={$quizid}&questionid={$questionid}");
    exit();
}

// Has an answer been added?
if (isset($_POST['add_answer'])) {
    // Extract the items of data
    $quizid = (int)$_POST['quizid'];
    $questionid = (int)$_POST['questionid'];
    $text = htmlentities($_POST['text'], ENT_QUOTES);
    $shortname = htmlentities($_POST['shortname'], ENT_QUOTES);
    $value = (float)$_POST['value'];
    
    // Insert the answer
    $result = mysql_query("
        INSERT INTO qh_answer (questionid, shortname, text, value)
        VALUES ({$questionid}, '{$shortname}', '{$text}', {$value})
    ");
    if ($result) {
        $_SESSION['message'] = 'Added answer successfully.';
    } else {
        $_SESSION['errormessage'] = 'Failed to add answer.<br/>MySQL: '.mysql_error();
    }
    
    // Reload the page to eliminate the POST parameters
    header('Location: '.QUIZHUD_WWW_ROOT."/browser/question_explore.php?quizid={$quizid}&questionid={$questionid}");
    exit();
}


// Find the quiz that has been specified
$quizid = fetch_param_integer('quizid', 0);
if ($quizid == 0) error("Expecting parameter 'quizid'.");
$quiz = get_quiz($quizid);
if (!$quiz) error("Failed to load quiz $quizid.");

// Check to see if a question has been specified
$questionid = fetch_param_string('questionid', '');
if (empty($questionid)) error("Expecting parameter 'questionid'.");
// Are we adding a new question?
$question = null;
if ($questionid == 'new') {
	$questionid = 0;

	// Create a blank question object
	$question = new QuizHUDQuestion(0, $quizid, '', 1.0, 'multiplechoice');
	$editmode = 'question';
} else {
	// Attempt to load the question from the database
	$questionid = (int)$questionid;
	$question = get_question($quizid, (int)$questionid);
	if (!$question) error("Failed to load question $questionid.");
	// Make sure it's the correct type
	if ($question->type != 'explore') error("Cannot edit question of type '{$question->type}' on this page.");
    
    // Have we been asked to delete an answer?
    $deleteanswerid = fetch_param_integer('deleteanswer', 0);
    $confirmdelete = fetch_param_boolean('confirmdelete', false);
    if ($deleteanswerid > 0) {
        // Attempt to load the answer
        $deleteanswer = get_answer($deleteanswerid);
        if (!$deleteanswer) {
            // Answer failed to load
            $errormessage = 'Failed to find answer to be deleted.<br/>MySQL: '.mysql_error();
        } else if ($confirmdelete) {
            // Deletion has been confirmed
            // Attempt to delete the specified answer
            $result = mysql_query("
                DELETE FROM qh_answer
                WHERE id = {$deleteanswerid}
                LIMIT 1
            ");
            
            // Was it successful?
            if ($result) $_SESSION['message'] = 'Deletion successful';
            else $_SESSION['errormessage'] = 'Deletion failed.';
            
            // Redirect back here to eliminate request variables
            header('Location: '.QUIZHUD_WWW_ROOT."/browser/question_explore.php?quizid={$quizid}&questionid={$questionid}");
            exit();
            
        } else {
            // Ask the user to confirm the deletion
            $confirmmessage = <<<XXXEODXXX
             <p class="error">
              <strong>DELETE?</strong><br/>
              Are you sure you want to delete answer "{$deleteanswer->shortname}"?<br/><i>(This action cannot be undone.)</i><br/><br/>
              <a class="button" href="question_explore.php?quizid={$quizid}&amp;questionid={$questionid}&amp;deleteanswer={$deleteanswerid}&amp;confirmdelete=true" title="Click here to confirm the deletion."><span class="yes">Yes</span></a>
              &nbsp;
              <a class="button" href="question_explore.php?quizid={$quizid}&amp;questionid={$questionid}" title="Click here to cancel the deletion."><span class="no">No</span></a>
             </p>
XXXEODXXX;
        }
    }
}


// Pickup and delete any session messages which may have come our way
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
switch ($editmode) {
case 'question':
	echo "<h2>Editing Question</h2>\n";
    echo <<<XXXEODXXX
    <p>
	 Enter the text of your question, and enter a weight.
	 The weight determines how much this question's result contributes to the entire quiz,
	  and normally the default weight of 1.0 is sufficient for most purposes.
	 However, entering a weight of 0 creates an unassessed 'survey' question.
	 You can edit all of these values again later.
    </p>
XXXEODXXX;
	break;
	
case 'answer':
	echo "<h2>Editing Answers</h2>\n";
    echo <<<XXXEODXXX
    <p>
	 You can alter the answers below by changing the text in the boxes.
	 Make sure to click on the "Save" button when you are done to save your changes.
    </p>
XXXEODXXX;
	break;
	
case 'addanswer':
	echo "<h2>Add Answer</h2>\n";
    echo <<<XXXEODXXX
    <p>
	 Enter the details of the new answer in the form below.
     For 'explore' type questions like this, the shortname will identify the individual object
      which the user must click in-world.
     Note that you do not need to add all the wrong answers -- the system will simply assume an answer is wrong
      if it is not on this list.
     (For a more restricted answer set, consider adding a multiple choice question instead.)
	 Click on the "Save" button when you are done to add it to the question.
    </p>
XXXEODXXX;
	break;
	
default:
	echo "<h2>Editing Question</h2>\n";
    echo <<<XXXEODXXX
    <p>
	 You can see the question and answer details below.
	 Click one of the edit icons to edit either the question or answer details.
	 You can also add a new answer by clicking the "Add Answer" button.
    </p>
XXXEODXXX;
	break;
}


// Display the confirmation message, if we have one
if (!empty($confirmmessage)) echo $confirmmessage, "<br/>\n";

// Display any messages that were returned
if (!empty($errormessage)) echo '<p class="error">',$errormessage,'</p>';
if (!empty($message)) echo '<p class="info">',$message,'</p>';
?>

<br/><br/>

<div id="questiondata">
<?php
// Should we display regular quiz data, or an editing form?
if ($editmode == 'question') {
	// Editing form
	?>
	<form action="question_explore.php" method="post"><div>
	<input type="hidden" name="id" value="<?php echo $question->id; ?>" />
    <input type="hidden" name="quizid" value="<?php echo $quizid; ?>" />
	
	Quiz: <b><a href="<?php echo QUIZHUD_WWW_ROOT,'/browser/quiz_edit.php?quizid=',$quiz->id; ?>" title="Click here to go back to the quiz edit page."><?php echo $quiz->name; ?></a></b><br/><br/>
	
	<label for="text">Text: </label>
	<input type="text" name="text" id="text" size="35" maxlength="255" value="<?php echo $question->text; ?>" /><br/><br/>
	
	<label for="weight">Weight: </label>
	<input type="text" name="weight" id="weight" size="5" maxlength="12" value="<?php echo $question->weight; ?>" /><br/><br/>
	
	Type: Explore<br/><br/>
	
	<br/>

	<input type="submit" value="Save" name="save_question" id="save_question"/>
	&nbsp;
	<?php

	// Output a 'Cancel' link, depending on whether or not this is a new question.
	// (For new questions, Cancel should go back to the quiz editing page. For updating a question, Cancel should just de-activate edit mode).
	if ($question->id <= 0) echo '<a href="',QUIZHUD_WWW_ROOT,'/browser/quiz_edit.php?quizid=',$quiz->id,'" title="Click here to cancel this new question.">Cancel</a>';
	else echo '<a href="',QUIZHUD_WWW_ROOT,'/browser/question_explore.php?quizid=',$quiz->id,'&amp;questionid=',$question->id,'" title="Click here to cancel edit mode.">Cancel</a>';
	
	// Close the form
	echo '	</div></form>';
	
	// If this is a new question, then stop here
	if ($question->id <= 0) {
		echo '</div>';
		require_once('_page_footer.php');
		exit();
	}

} else {
	// Just the regular question data
	// (make the quiz name a link back to the quiz edit page)
	echo 'Quiz: <b><a href="',QUIZHUD_WWW_ROOT,'/browser/quiz_edit.php?quizid=',$quiz->id,'" title="Click here to go back to this quiz edit page.">',$quiz->name,'</a></b><br/><br/>';
	echo 'Text: <b>',$question->text,"</b><br/>\n";
	echo 'Weight: <b>',$question->weight,"</b><br/>\n";
	echo "Type: <b>Explore</b><br/><br/>\n";
	
	// Add an editing link
	echo '<a class="button" href="',QUIZHUD_WWW_ROOT,'/browser/question_explore.php?quizid=',$quiz->id,'&amp;questionid=',$question->id,'&amp;edit=question" title="Click here to edit the question."><span class="edit">Edit Question</span></a>';
}
?>
</div>

<br/><br/>

<div id="answerdata">

<?php
// Get all answers in this question
$answers = get_answers($question->id);
if ($answers === false) {
	echo '<p class="error">Failed to load answers</p>';
} else if (count($answers) == 0) {
	echo '<p class="info">No Answers Listed</p>';
} else {
	// If we are editing answers, add a form
	if ($editmode == 'answer') {
		echo '<form action="question_explore.php" method="post"><div>';
		echo "<input type=\"hidden\" name=\"quizid\" id=\"quizid\" value=\"{$quiz->id}\" />\n";
		echo "<input type=\"hidden\" name=\"questionid\" id=\"questionid\" value=\"{$question->id}\" />\n";
	}

	// Display a table of asnwers
    echo '<table class="answerform data">';
    echo '<tr><th>ID</th><th>Shortname</th><th>Text</th><th>Value</th><th>&nbsp;</th></tr>',"\n";
    $lightrow = true;
    $answernum = 0;
    $numanswers = count($answers);
    foreach ($answers as $answer) {
        // Alternate between light and dark rows, and highlight the delete row if necessary
        if (!empty($deleteanswer) && $deleteanswer->id == $answer->id) {
        	echo "<tr class=\"delete\">\n";
            $lightrow = !$lightrow;
        } else if ($lightrow) {
            echo "<tr class=\"light\">\n";
            $lightrow = false;
        } else {
            echo "<tr class=\"dark\">\n";
            $lightrow = true;
        }
        
        // Output the fixed items of data
        echo '<td class="id">',$answer->id,'</td>';
        
        // Are we in edit mode?
        if ($editmode == 'answer') {
            echo "<td class=\"text\"><input size=\"30\" maxlength=\"255\" type=\"text\" name=\"shortname_{$answer->id}\" id=\"shortname_{$answer->id}\" value=\"{$answer->shortname}\" /></td>";
        	echo "<td class=\"text\"><input size=\"30\" maxlength=\"255\" type=\"text\" name=\"text_{$answer->id}\" id=\"text_{$answer->id}\" value=\"{$answer->text}\" /></td>";
		    echo "<td class=\"value\"><input size=\"2\" maxlength=\"12\" type=\"text\" name=\"value_{$answer->id}\" id=\"value_{$answer->id}\" value=\"{$answer->value}\" /></td>";
        } else {
            echo '<td class="shortname">',$answer->shortname,'</td>';
		    echo '<td class="text">',$answer->text,'</td>';
		    echo '<td class="value">',$answer->value,'</td>';
        }
        
        echo '<td class="action">';
        
        // Add a "delete" button
        echo "<a href=\"question_explore.php?quizid={$quiz->id}&amp;questionid={$question->id}&amp;deleteanswer={$answer->id}\" title=\"Click here to delete this answer.\"><img src=\"img/delete.png\" alt=\"delete\" /></a>";
        
        echo '</td>';
        echo "</tr>\n";
        
        $answernum++;
    }
    
    echo '</table><br/><br/>';
    
    // Close the form, or display an edit button
    if ($editmode == 'answer') {
		// Save button
		echo '<input type="submit" value="Save" name="save_answers" id="save_answers"/>&nbsp;';
		// Cancel link
		echo '<a href="',QUIZHUD_WWW_ROOT,'/browser/question_explore.php?quizid=',$quiz->id,'&amp;questionid=',$question->id,'" title="Click here to cancel edit mode.">Cancel</a>';
    
    	echo "</div></form>";
    	
   	} else {
   		echo '<a class="button" href="',QUIZHUD_WWW_ROOT,'/browser/question_explore.php?quizid=',$quiz->id,'&amp;questionid=',$question->id,'&amp;edit=answer" title="Click here to edit the answers."><span class="edit">Edit Answers</span></a>';
   	}
}
?>
<br/><br/>

<?php

// If we are in 'addanswer' mode, then display a form.
// Otherwise, display an 'add answer' button.
if ($editmode == 'addanswer') {
	?>
	<form action="question_explore.php" method="post"><div>
	<input type="hidden" name="quizid" value="<?php echo $quiz->id; ?>" />
	<input type="hidden" name="questionid" value="<?php echo $question->id; ?>" />
	
	<h4 id="addanswer">New Answer</h4>

    <label for="shortname">Shortname: </label>
	<input type="text" name="shortname" id="shortname" size="15" maxlength="255" value="" />&nbsp;
    
	<label for="text">Text: </label>
	<input type="text" name="text" id="text" size="20" maxlength="255" value="" />&nbsp;
	
	<label for="value">Value: </label>
	<input type="text" name="value" id="value" size="2" maxlength="12" value="1.0" />&nbsp;
	
	<input type="submit" name="add_answer" id="add_answer" value="Save" />&nbsp;
	<a href="question_explore.php?quizid=<?php echo $quiz->id; ?>&amp;questionid=<?php echo $question->id; ?>" title="Click here to cancel this new answer.">Cancel</a>
	
	</div></form>

	<?php
	
} else {
	?>
	<a class="button" href="question_explore.php?quizid=<?php echo $quiz->id; ?>&amp;questionid=<?php echo $question->id; ?>&amp;edit=addanswer#addanswer" title="Click here to add a new answer to this question."><span class="addanswer">Add Answer</span></a>
	<?php
}

?>

</div>


<?php
require_once('_page_footer.php');
?>

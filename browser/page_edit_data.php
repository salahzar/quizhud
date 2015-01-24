<?php
//////////
// QuizHUD page editing data handling script.
// Handles POST data coming from a page editing form, and re-directs afterwards as appropriate.
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
require_login(QUIZHUD_WWW_ROOT.'/browser/pages.php');

// Define our destination URLs
global $returnurl;
$returnurl = QUIZHUD_WWW_ROOT.'/browser/page_edit.php';
global $finishurl;
$finishurl = QUIZHUD_WWW_ROOT.'/browser/pages.php';

// Redirects back to the original page with the specified message
function goback($msg)
{
    global $returnurl;
    $_SESSION['message'] = $msg;
    header("Location: $returnurl");
    exit();
}

// Redirects to the finish page with the specified message
function finish($msg)
{
    // Reset our session data
    unset($_SESSION['id']);
    unset($_SESSION['name']);
    unset($_SESSION['title']);
    unset($_SESSION['text']);
    unset($_SESSION['image']);
    unset($_SESSION['layout']);

    global $finishurl;
    $_SESSION['message'] = $msg;
    header("Location: $finishurl");
    exit();
}

// Make sure the data has been submitted
if (empty($_POST['id'])) header('Location: '.QUIZHUD_WWW_ROOT.'/browser/pages.php');

// Fetch all incoming POST data
$id = fetch_param_integer('id', 0);
$name = fetch_param_string('name', '');
$title = fetch_param_string('title', '');
$text = fetch_param_string('text', '', true);
$image = fetch_param_string('image', '');
$layout = fetch_param_string('layout', 'imageright');

// Convert it all into session data
$_SESSION['id'] = $id;
$_SESSION['name'] = $name;
$_SESSION['title'] = $title;
$_SESSION['text'] = $text;
$_SESSION['image'] = $image;
$_SESSION['layout'] = $layout;

// Validate the data
if (!validate_page_name($name)) goback('<strong>ERROR</strong><br/>The name of the page must be specified, and must only contain letters, numbers, dashes, and underscores.');
// Make sure the layout is valid
$layouts = array('imageright', 'imageleft', 'noimage', 'imageonly');
if (!in_array($layout, $layouts)) $layout = 'imageright';

// Is this a new page?
if ($id <= 0) {
    // Yes - we want to insert a new record.
    // Make sure the name is unique.
    $uniqueresult = mysql_query("SELECT * FROM qh_page WHERE name = '$name' LIMIT 1");
    if ($uniqueresult === false) goback('Error checking uniqueness of page name.');
    if (mysql_num_rows($uniqueresult) > 0) goback('ERROR: a page with that name already exists. Please use a different name, or edit/delete the existing page.');

    // Seems fine - insert the new record
    $insertresult = mysql_query("
        INSERT INTO qh_page (name, title, text, image, layout)
        VALUES ('$name', '$title', '$text', '$image', '$layout')
    ");
    
    // Was it successful?
    if ($insertresult) finish("Successfully added new page '{$name}'");
    else goback("ERROR: failed to add new page.<br/>\"".mysql_error()."\"");
    exit();
    
}


// We are updating an existing record.
// Make sure there is no other record with the same name.
$uniqueresult = mysql_query("SELECT * FROM qh_page WHERE id != $id AND name = '$name' LIMIT 1");
if ($uniqueresult === false) goback('Error checking uniqueness of page name.');
if (mysql_num_rows($uniqueresult) > 0) goback('ERROR: a different page with that name already exists. Please use a different name, or edit/delete the other page.');

// Update our record
$updateresult = mysql_query("
    UPDATE qh_page
    SET name = '$name', title = '$title', text = '$text', image = '$image', layout = '$layout'
    WHERE id = $id
    LIMIT 1
");

// Was it successful?
if ($updateresult) finish("Successfully updated page '{$name}'");
else goback("ERROR: failed to update page.<br/>\"".mysql_error()."\"");
exit();


?>
<?php

//////////
// QuizHUD pages administration.
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

// We may need to display a confirmation box
$confirmmessage = '';
// Has a page deletion been requested?
if (isset($_GET['deletepageid'])) {
    // Yes - has it been confirmed yet?
    if (isset($_GET['confirmdelete'])) {
    
        // Yes - do the deletion
        $deleteresult = @mysql_query("DELETE FROM qh_page WHERE id = {$_GET['deletepageid']} LIMIT 1");
        if ($deleteresult) {
            $_SESSION['message'] = 'Deletion successful.';
        } else {
            $_SESSION['message'] = 'Deletion failed.';
        }
        
        // Redirect back to this page again
        header('Location: '.QUIZHUD_WWW_ROOT.'/browser/pages.php');
        exit($_SESSION['message']);
    
    } else {
        // Attempt to fetch the page
        $deletepage = get_page_by_id($_GET['deletepageid']);
        if ($deletepage != false) {
            // Display a confirmation form
            $confirmmessage = <<<XXXEODXXX
            
     <p class="error">
      <strong>DELETE?</strong><br/>
      Are you sure you want to delete page "{$deletepage->name}"?<br/><i>(This action cannot be undone)</i><br/><br/>
      <a class="button" href="pages.php?deletepageid={$_GET['deletepageid']}&amp;confirmdelete=true" title="Click here to confirm the deletion."><span class="yes">Yes</span></a>
      &nbsp;
      <a class="button" href="pages.php" title="Click here to cancel the deletion."><span class="no">No</span></a>
     </p>
        
XXXEODXXX;
        }
    }
}

$sectionname = 'pages';
require_once('_page_header.php');

// Get any message which has been provided by another page
$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
?>


<h3>Pages Editing</h3>
<p>
 You can use this section to add or edit the pre-defined pages which your QuizHUD can display.
 Such pages can be used to display core information when a user clicks a tab on their HUD device, or exploratory information
  when a user is in "Explore" mode and clicks a feature in their environment.
 Pages will be requested by name, so each page name must be unique.
</p>

<?php

// Display the confirmation message, if we have one
if (!empty($confirmmessage)) echo $confirmmessage, "<br/>\n";

// Display any message that was provided
if (!empty($message)) {
     echo '<p class="info">',$message,'</p>';
}
?>

<div style="text-align:center;">
 <a class="button" href="page_edit.php?pageid=new" title="Click here to add a new page"><span class="newpage">Add New Page</span></a><br/><br/>
</div>

<?php

// Fetch a list of all the pages in the system.
$pages = get_pages();
if ($pages === false) error('Failed to query database for pages. Perhaps you have not <a href="install.php" title="Click here to visit the installation page">installed quizHUD</a> yet?');
if (count($pages) == 0) {
    echo '<p class="info">No pages to display</p>';
} else {
    // Define the URLs for deleting and editing pages
    $actionurl_edit = QUIZHUD_WWW_ROOT.'/browser/page_edit.php';
    $actionurl_preview = QUIZHUD_WWW_ROOT.'/hud/page.php';
    $actionurl_delete = QUIZHUD_WWW_ROOT.'/browser/pages.php';
    

    // Display a table of all pages
    echo "<table id=\"pagelist\" class=\"data\">\n";
    echo "<tr class=\"header\"><th>ID</th><th>Name</th><th>Title</th><th>Text</th><th>Image</th><th>Layout</th><th>&nbsp;</th></tr>\n";
    
    // Go through each page
    $lightrow = true;
    foreach ($pages as $p) {
    	// Check for pages being deleted, and alternate between light and dark rows otherwise
    	if (!empty($deletepage) && $deletepage->id == $p->id) {
    		echo "<tr class=\"delete\">\n";
            $lightrow = false;
        } else if ($lightrow) {
            echo "<tr class=\"light\">\n";
            $lightrow = false;
        } else {
            echo "<tr class=\"dark\">\n";
            $lightrow = true;
        }
        
        $textsummary = substr(strip_tags($p->text), 0, 50);
        if (strlen($p->text) > 50) $textsummary .= '...';
        
        echo "<td class=\"id\">{$p->id}</td>";
        echo "<td class=\"name\">{$p->name}</td>";
        echo "<td class=\"title\">{$p->title}</td>";
        echo "<td class=\"text\">{$textsummary}</td>";
        echo "<td class=\"image\">{$p->image}</td>";
        echo "<td class=\"layout\">{$p->layout}</td>";
        
        // Construct the action links
        echo "\n<td class=\"action\">\n";
         // Action: edit page
         echo "<a href=\"{$actionurl_edit}?pageid={$p->id}\" title=\"Edit this HUD page.\"><img src=\"img/page_edit.png\" alt=\"edit\" /></a>&nbsp;\n";
         // Action: preview page
         echo "<a href=\"{$actionurl_preview}?id={$p->name}\" title=\"Preview this HUD page.\"><img src=\"img/eye.png\" alt=\"preview\" /></a>&nbsp;\n";
         // Action: delete page
         echo "<a href=\"{$actionurl_delete}?deletepageid={$p->id}\" title=\"Delete this HUD page.\"><img src=\"img/delete.png\" alt=\"delete\" /></a>\n";         
        echo "</td>\n";
        
        echo "</tr>\n";
    }
    
    echo "</table>\n";
}

?>

<?php
require_once('_page_footer.php');
?>

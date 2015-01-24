<?php

//////////
// QuizHUD pages editing form.
// Can be used to edit an existing page, or add a new one.
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

// This variable will store our page object
$page = null;
// Any message to be shown with returned data will be put in here
$message = '';

// If there is no page ID or session data, then assume we're creating a new page
if (!isset($_SESSION['id']) && !isset($_GET['pageid'])) $_GET['pageid'] = 'new';

// Has a page been specified in the GET or SESSION values?
if (isset($_GET['pageid'])) {
    // Are we being asked to create a new page?
    if ($_GET['pageid'] == 'new') {
        // Yes - create an empty structure
        $page = new QuizHUDPage(0, '', '', '', '', '');
    } else {
        // No - load our page from the database
        $page = get_page_by_id((int)$_GET['pageid']);
        if ($page === false) error("Failed to load page {$_GET['pageid']}.");
    }
} else if (isset($_SESSION['id'])) {
    // Grab the all the values from the session data, and unset them
    $page = new QuizHUDPage($_SESSION['id'], stripslashes($_SESSION['name']), stripslashes($_SESSION['title']), stripslashes($_SESSION['text']), $_SESSION['image'], $_SESSION['layout']);
    
    unset($_SESSION['id']);
    unset($_SESSION['name']);
    unset($_SESSION['title']);
    unset($_SESSION['text']);
    unset($_SESSION['image']);
    unset($_SESSION['layout']);
    
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
    }
}

$sectionname = 'pages';
require_once('_page_header.php');

// Change the intro of the page depending on the mode we're in
if ($page->id <= 0) {
    echo "<h2>Adding New Page</h2>\n";
    echo "<p>Fill in the form below, and click the \"Save\" button beneath to add your new page.</p>";
} else {
    echo "<h2>Editing Page</h2>\n";
    echo "<p>Make alterations to this page by editing the form values, and click the \"Save\" button beneath to save your changes.</p>";
}

// Display any message that was returned
if (!empty($message)) {
     echo '<p class="error">',$message,'</p>';
}
?>

<script type="text/javascript">
function changeImagePreview(sel)
{
    // Get the preview image element
    var elem = document.getElementById('previewImage');
    // Do we have an image selected?
    if (sel.value == "") {
        // No - revert to the preview image
        elem.src = "<?php echo QUIZHUD_WWW_ROOT.'/browser/img/preview.png'; ?>";
    } else {
        // Yes - change its address
        elem.src = "<?php echo QUIZHUD_WWW_ROOT.'/'.QUIZHUD_IMG_FOLDER.'/'; ?>" + sel.value;
    }
}
</script>

<br/><br/>
<form action="page_edit_data.php" method="post" id="pageform">
    <div><input type="hidden" name="id" id="id" value="<?php echo $page->id; ?>" /></div>

    <table>
        
        <tr>
            <td>
                <label for="name">Name: </label>
            </td>
            <td>
                <input type="text" name="name" id="name" value="<?php echo $page->name; ?>" size="30" maxlength="255" /><br/>
                <span class="note">(Must be unique. Please use letters, numbers, dashes and underscores only.)</span>
            </td>
        </tr>

        
        <tr>
            <td>
                <label for="title">Title: </label>
            </td>
            <td>
                <input type="text" name="title" id="title" value="<?php echo $page->title; ?>" size="30" maxlength="255" /><br/>
                <span class="note">(Plaintext heading to be displayed at top of page. No HTML.)</span>
            </td>
        </tr>

        
        <tr>
            <td>
                <label for="text">Text: </label>
            </td>
            <td>
                <textarea name="text" id="text" cols="36" rows="10"><?php echo stripslashes($page->text); ?></textarea><br/>
                <span class="note">(The main body text of the page. Can include HTML.)</span>
            </td>
        </tr>


        <tr>
            <td>
                <label for="image">Image: </label>
            </td>
            <td>
                <select name="image" id="image" size="8" onchange="changeImagePreview(this);">
                 <option value="" <?php if (empty($page->image)) echo "selected=\"selected\""; ?>>(no image)</option>
                    <?php
                        // Output a list of files from the images folder
                        $images = get_images();
                        foreach ($images as $img) {
                            echo "<option value=\"{$img}\"";
                            if ($img == $page->image) echo " selected=\"selected\"";
                            echo ">{$img}</option>\n";
                        }
                    ?>
                </select>
                <br/>
                <span class="note">
                    (You may select one image from the images folder to be displayed with this page. Optional.
                    <a href="help.php#pageimages" title="Click here for help information on page images.">Image Help</a>.)
                </span>
                <br/>
                <img id="previewImage" src="<?php if (empty($page->image)) echo "img/preview.png"; else echo QUIZHUD_WWW_ROOT.'/'.QUIZHUD_IMG_FOLDER.'/'.$page->image; ?>" alt="Preview Image" style="margin-top:8px; border:solid 1px black;"/>
                
            </td>
        </tr>

        
        <tr>
            <td>
                <label for="layout">Layout: </label>
            </td>
            <td>
                <select name="layout" id="layout" size="1">
                    <?php
                        // Define a list of layouts
                        $layouts = array('imageright', 'imageleft', 'noimage', 'imageonly');
                        // Output each one as an option
                        foreach ($layouts as $l) {
                            echo "<option value=\"{$l}\"";
                            if ($l == $page->layout) echo " selected=\"selected\"";
                            echo ">{$l}</option>\n";
                        }
                    ?>
                </select><br/>
                <span class="note">(You can change the layout of the page. See the <a href="help.php#pagelayout" title="Click here for more information about page layouts.">Help</a> page for more information.)</span>
            </td>
        </tr>

        <tr>
            <td>&nbsp;</td>
            <td>
                <input type="submit" value="Save"/>
                &nbsp;
                <a href="pages.php" title="Click here to cancel this form">Cancel</a>
            </td>
        </tr>

    </table>

</form>


<?php
require_once('_page_footer.php');
?>

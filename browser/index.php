<?php

//////////
// QuizHUD administration index.
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
require_login(QUIZHUD_WWW_ROOT.'/browser/index.php');

$sectionname = 'home';
require_once('_page_header.php');
?>


<p style="font-weight:bold;">Welcome to your quizHUD administration homepage.</p>

<?php

// Is quizHUD already installed?
if (is_installed()) {
    echo '<p>quizHUD appears to be installed successfully. Use the navigation menu above to edit the pages which can be viewed on your quizHUD, add/edit quizzes, and to view the user information. Click the Help button for further information.</p>';
} else {
    // No - display a link to the installation page
    $installurl = QUIZHUD_WWW_ROOT.'/browser/install.php';
    error("quizHUD does not appear to have been installed in your database yet. Please visit the <a href=\"$installurl\" title=\"Click here to install quizHUD\">installation page</a> before continuing.");
}

?>

<?php
require_once('_page_footer.php');
?>

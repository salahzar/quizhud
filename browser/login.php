<?php
//////////
// QuizHUD login page.
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
quizhud_logout();

// Has a password been submitted?
$login_attempted = false;
$login_valid = false;
if (isset($_REQUEST['password'])) {
    $login_attempted = true;
    // Is login successful?
    if (quizhud_login($_REQUEST['password'])) {
        // Yes - login the user
        $login_valid = true;
        
        // Attempt to redirect if required
        if (!headers_sent()) {
            // Has a follow-on URL been specified?
            $nexturl = '';
            if (isset($_SESSION['quizhudnexturl'])) {
                $nexturl = $_SESSION['quizhudnexturl'];
                unset($_SESSION['quizhudnexturl']);
            }
            
            // Default to the index page
            if (empty($nexturl)) $nexturl = QUIZHUD_WWW_ROOT.'/browser/index.php';
            
            header('Location: '.$nexturl);
            exit("<a href=\"$nexturl\">$nexturl</a>");
        }
    }
}

require_once('_page_header.php');
?>

<form action="login.php" method="post" id="login"><div>
<h1>quizHUD Admin Login</h1>

<?php
// Check to see if a login was attempted and valid
if ($login_attempted) {
    if ($login_valid) {
        echo '<p class="info">Successful login</p>';
        require_once('_page_footer.php');
        exit();
    } else {
        echo '<p class="error"><strong>Invalid Login</strong></p>';
    }
}
?>

<p>
 Only authorised users may administer the quizHUD system.
 Please enter the admin password below.
 (You should have set this in your 'config.php' when you installed quizHUD).
</p>
<br/>
<label for="password">Password: </label>
<input type="password" size="25" maxlength="255" name="password" id="password" />
&nbsp;
<input type="submit" value="Login" />

</div></form>

<?php
require_once('_page_footer.php');
?>
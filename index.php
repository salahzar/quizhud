<?php
//////////
// QuizHUD entry-point. Re-directs to the regular browser pages.
// Author: Peter R. Bloomfield
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
//////////

// Make sure the configuration script exists
if (!file_exists('config.php')) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en" xml:lang="en">
<head>
 <meta http-equiv="content-type" content="text/html; charset=utf-8" />
 <title>quizHUD is not configured</title>
</head>
<body>

<h1>quizHUD is not configured</h1>
<p>
    The quizHUD software has not been configured yet. You must create a "<b>config.php</b>" file first.
    You can do this by making a copy of the "config_example.php" script that came in your download, renaming it "config.php",
    and editing it (in any normal text editor) to suit your website's configuration.
    You can refer to the README script for more information.
</p>
<p>
    You will also need to create a database for quizHUD, if you have not done so already.
    The "config.php" script will need to know the database details.
</p>
<p>
    When you are finished, upload the "config.php" script to your quizHUD installation, and access the site in your browser.
</p>
<p style="font-style:italic; margin-top:18px;">
    Please refer to the <a href="http://www.sloodle.org/quizhud" title="Click here to visit the quizHUD website">quizHUD website</a> if you need more help.
</p>

</body>
</html>
<?php
    exit();
}

require_once('config.php');
require_once('lib.php');

// It would be nice if we could redirect depending on whether or not we detect
//  the access coming from the quizHUD device in SL.
// Unfortunately, it does not seem to be possible at this time.
$url = QUIZHUD_WWW_ROOT.'/browser/index.php';
header('Location: '.$url);
exit('You should have been re-directed to this address: '.$url);

?>

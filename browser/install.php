<?php
//////////
// QuizHUD installation page.
// Will attempt to install the necessary database tables.
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
require_login();

// Has the user confirmed installation?
$confirmed = false;
if (isset($_REQUEST['confirm'])) {
    if ($_REQUEST['confirm'] == 'Yes') {
        $confirmed = true;
    } else if ($_REQUEST['confirm'] == 'No') {
        // Redirect back to the index if possible
        if (!headers_sent()) {
            header('Location: '.QUIZHUD_WWW_ROOT.'/browser/index.php');
            exit();
        }
    }
}

$sectionname = 'home';
require_once('_page_header.php');

?>

<h3>Installation</h3>

<?php

// Has installation been confirmed?
if ($confirmed) {
    // Yes
    // Start the installation process
    //
    $result = true;
    echo "<pre>";
    
    if ($result) { echo "Creating answers table: qh_answer\n"; @ob_flush(); }
    $result = $result && mysql_query("DROP TABLE IF EXISTS `qh_answer`;");
    $result = $result && mysql_query("
    	CREATE TABLE `qh_answer` (
		`id` int(10) unsigned NOT NULL auto_increment COMMENT 'Primary id',
		`questionid` int(10) unsigned NOT NULL COMMENT 'The question this answer relates to',
		`shortname` varchar(255) NOT NULL COMMENT 'A short name by which this answer can be identified. Must be unique within a given question. Should be a letter for multiple choice, e.g. a, b, c, or d. Should be the name of a clickable feature for explore questions.',
		`text` varchar(255) NOT NULL COMMENT 'The text of this answer',
		`value` float NOT NULL default '1' COMMENT 'The value of this answer if selected',
		PRIMARY KEY  (`id`),
		KEY `questionid` (`questionid`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='One record per possible answer';
		");

    if ($result) { echo "Creating attempt table: qh_attempt\n"; @ob_flush(); }
	$result = $result && mysql_query("DROP TABLE IF EXISTS `qh_attempt`;");
	$result = $result && mysql_query("
		CREATE TABLE `qh_attempt` (
		`id` int(10) unsigned NOT NULL auto_increment COMMENT 'Primary id',
		`questionid` int(10) unsigned NOT NULL COMMENT 'The ID of the question being attempted',
		`userid` int(10) unsigned NOT NULL COMMENT 'The user who made this attempt',
		`answer` varchar(255) NOT NULL COMMENT 'Identifies the answer which was selected. Should relate to the shortname field of the answers section.',
		`timestamp` int(10) unsigned NOT NULL COMMENT 'The time at which this attempt was received',
		PRIMARY KEY  (`id`),
		KEY `userid` (`userid`),
		KEY `questionid` (`questionid`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='One record per submitted answer';
	");

    if ($result) { echo "Creating pages table: qh_page\n"; @ob_flush(); }
	$result = $result && mysql_query("DROP TABLE IF EXISTS `qh_page`;");
	$result = $result && mysql_query("
		CREATE TABLE `qh_page` (
		`id` int(10) unsigned NOT NULL auto_increment COMMENT 'Primary ID',
		`name` varchar(255) NOT NULL COMMENT 'Name of this page (used in requests)',
		`title` varchar(255) NOT NULL COMMENT 'Title to display at the top of the page (plain text only)',
		`text` text NOT NULL COMMENT 'Main text content (can include HTML)',
		`image` varchar(255) default NULL COMMENT 'Path to the image file (relative to the images folder)',
		`layout` enum('imageright','imageleft','noimage','imageonly') NOT NULL default 'imageright' COMMENT 'The layout of the page',
		PRIMARY KEY  (`id`),
		UNIQUE KEY `name` (`name`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Each record represents a page which can appear in the HUD';
	");
    
    if ($result) { echo "Inserting default system pages\n"; @ob_flush(); }
    $result = $result && mysql_query("
        INSERT INTO `qh_page` (`id`, `name`, `title`, `text`, `image`, `layout`) VALUES
        (2, 'home', 'Welcome!', '<p style=\"font-size:16pt;\">Your Quiz HUD is working and ready for use.</p>\n\n<p style=\"font-size:14pt;\">You can learn about your surroundings using the \"Explore\" tab, or take a quiz using the \"Quiz\" tab.</p>', 'qh.jpg', 'imageright'),
        (3, 'help', 'Help', '<p style=\"font-weight:bold; font-size:14pt;\">Use the tabs at the top to navigate to the main parts of the Quiz HUD.</p>\n\n<p style=\"font-size:10pt;\">If you click the \"Explore\" tab, you can select features in your environment to learn about.</p>\n\n<p style=\"font-size:10pt;\">If you click the \"Quiz\" tab, you can take a quiz to assess your learning.</p>', 'question.jpg', 'imageright'),
        (5, 'explore', 'Explore Mode', '<p>\nWelcome to explore mode!<br/><br/>\n\nIn this mode, you can click the objects around you in the virtual environment.\n</p>', 'magnifier.jpg', 'imageright');
    ");

    if ($result) { echo "Creating question table: qh_question\n"; @ob_flush(); }
	$result = $result && mysql_query("DROP TABLE IF EXISTS `qh_question`;");
	$result = $result && mysql_query("
		CREATE TABLE `qh_question` (
		`id` int(10) unsigned NOT NULL auto_increment COMMENT 'Primary id',
		`quizid` int(10) unsigned NOT NULL COMMENT 'The quiz this question belongs to',
		`text` varchar(255) NOT NULL COMMENT 'The text of the question',
		`weight` float NOT NULL default '1' COMMENT 'The weight of this question, e.g. 0.0 for an unassessed question',
		`type` enum('multiplechoice','explore') NOT NULL COMMENT 'Indicates the type of question. Multiple choice questions offer a set of answers which may be selected from. Explore questions allow the user to click features in-world.',
		PRIMARY KEY  (`id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='One record per question'; 
	");

    if ($result) { echo "Creating quiz table: qh_quiz\n"; @ob_flush(); }
	$result = $result && mysql_query("DROP TABLE IF EXISTS `qh_quiz`;");
	$result = $result && mysql_query("
		CREATE TABLE `qh_quiz` (
		`id` int(10) unsigned NOT NULL auto_increment COMMENT 'Primary key',
		`name` varchar(255) NOT NULL COMMENT 'Name of the quiz',
		`method` enum('first','last','worst','best','mean') NOT NULL default 'last' COMMENT 'Defines the method used to calculate a students grade on each question if multiple attempts have been made',
		PRIMARY KEY  (`id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='One record per quiz';
	");

    if ($result) { echo "Creating user table: qh_user\n"; @ob_flush(); }
	$result = $result && mysql_query("DROP TABLE IF EXISTS `qh_user`;");
	$result = $result && mysql_query("
		CREATE TABLE `qh_user` (
		`id` int(10) unsigned NOT NULL auto_increment COMMENT 'Primary id',
		`uuid` char(36) NOT NULL COMMENT 'UUID of the avatar',
		`name` varchar(255) default NULL COMMENT 'Name of the avatar',
		PRIMARY KEY  (`id`),
		UNIQUE KEY `uuid` (`uuid`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='One record per known user';
	");

    if ($result) { echo "Creating configuration table: qh_config\n"; @ob_flush(); }
	$result = $result && mysql_query("DROP TABLE IF EXISTS `qh_config`;");
	$result = $result && mysql_query("
		CREATE TABLE `qh_config` (
		`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'Primary ID',
		`name` VARCHAR( 255 ) NOT NULL COMMENT 'The name of the setting.',
		`value` VARCHAR( 255 ) NULL COMMENT 'The value of the setting.',
		UNIQUE (`name`)
		) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci COMMENT = 'Each record is a quizHUD configuration setting.';
	");
    
    echo "</pre><br/>\n";
    
    // Did an error occur?
    if ($result === false) {
        echo '<p class="error"><strong>MySQL Error</strong><br/>'.mysql_error().'</p>';
    } else {
        echo '<p class="info">Success</p>';
    }
    
    // Store the new version number in the database.
    set_config('version', QUIZHUD_VERSION);
    
    
} else {
    // Not yet
    // Display a warning if the quizHUD is already installed
    if (is_installed()) echo '<p class="error"><strong>WARNING</strong><br/>quizHUD already seems to be installed. You will lose all your data if you proceed with the installation.</p>';
    
    // Confirmation message and buttons
    $url = QUIZHUD_WWW_ROOT.'/browser/install.php';
    $url_yes = $url.'?confirm=Yes';
    $url_no = $url.'?confirm=No';
    
    echo '<p>This will setup the necessary tables in your database. Do wish to continue?&nbsp;';
    echo "<a class=\"button\" href=\"$url_yes\" title=\"Click here to continue with the installation\"><span class=\"yes\">Yes</span></a> ";
    echo "<a class=\"button\" href=\"$url_no\" title=\"Click here to cancel the installation\"><span class=\"no\">No</span></a> ";
    echo "</p>\n";
     
}
?>


<?php
require_once('_page_footer.php');
?>

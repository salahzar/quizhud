<?php
// Server-side configuration script for the quiz HUD.
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

///// CUSTOM CONFIGURATION SETTINGS /////

// The root of the quizhud site (MODIFY THESE FOR YOUR WEBSITE)
define('QUIZHUD_WWW_ROOT', ''); // The URL to your quiz HUD installation
define('QUIZHUD_DIR_ROOT', ''); // The absolute path on disk to your quiz HUD installation

// Administration password (use this to login through your web-browser when you want to edit stuff)
define('QUIZHUD_ADMIN_PWD', '');
// Internal password for requests coming from SL (PUT YOUR OWN RANDOM PASSWORD HERE)
define('QUIZHUD_INTERNAL_PWD', '');

// Database connection details (MODIFY THESE FOR YOUR DATABASE)
$dbhost = '';
$dbuser = '';
$dbpass = '';
$dbname = '';


///// END CUSTOM CONFIGURATION SETTINGS /////


// There is not normally any need to modify this:
define('QUIZHUD_IMG_FOLDER', 'img'); // Folder where the images are stored, relative to the root of the installation, e.g. 'img'

// Error checks... do not change these!
if (QUIZHUD_WWW_ROOT == '') exit('ERROR: WWW root not set in config.php');
if (QUIZHUD_DIR_ROOT == '') exit('ERROR: DIR root not set in config.php');
if (empty($dbhost) || empty($dbuser) || empty($dbname)) exit('ERROR: database details not fully specified in config.php');

// Connect to the database
global $db;
$db = @mysql_connect($dbhost, $dbuser, $dbpass);
if (!$db) exit('DATABASE ERROR');
if (!mysql_select_db($dbname)) exit('DATABASE ERROR');



?>

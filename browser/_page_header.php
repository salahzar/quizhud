<?php
// Header for QuizHUD administration pages.
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

require_once('../config.php');
require_once('../lib.php');

// Define the list of pages we will use to build our navigation menu
$navmenu = array();
$navmenu['home'] = array('file'=>'index.php', 'title'=>'Home', 'img'=>'img/house.png');
$navmenu['pages'] = array('file'=>'pages.php', 'title'=>'Pages', 'img'=>'img/page.png');
$navmenu['quiz'] = array('file'=>'quiz.php', 'title'=>'Quizzes', 'img'=>'img/book.png');
$navmenu['users'] = array('file'=>'users.php', 'title'=>'Users', 'img'=>'img/group.png');
$navmenu['help'] = array('file'=>'help.php', 'title'=>'Help', 'img'=>'img/help.png');

// The $sectionname variable should define which navigation section is active.
// Determine what our page title should be (if it's not already specified)
if (empty($pagetitle)) {
    $pagetitle = 'quizHUD';
    if (!isset($sectionname)) $sectionname = '';
    if (!empty($sectionname) && isset($navmenu[$sectionname])) $pagetitle = 'quizHUD :: '.$navmenu[$sectionname]['title'];
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en" xml:lang="en">
<head>
 <meta http-equiv="content-type" content="text/html; charset=utf-8" />
 <link rel="stylesheet" type="text/css" href="styles.php" />
 <title><?php echo $pagetitle; ?></title>
</head>
<body>

<!-- Header Section -->
<div class="roundcorners_green" id="header">
 <div class="top"><div class="right"></div></div>
 <div class="content" style="text-align:left;">
 
 <span style="float:right; font-family:sans-serif; font-size:70%;">
  <?php
    if (is_logged_in()) {
        echo '<a href="'.QUIZHUD_WWW_ROOT.'/browser/login.php" title="Logout of the administration system.">Log Out</a> ';
    }
   ?>
  </span>
 
  <!-- Navigation Menu -->
  <table id="nav">
   <tr>
    <th style="padding-right:8px;">quizHUD ::</th>
    <?php
        // Build our navigation menu
        foreach ($navmenu as $name => $nav) {
            // Check if this is the active section
            if ($name == $sectionname) echo '    <td class="active">';
            else echo '    <td>';
            // Output the link
            echo "<a href=\"{$nav['file']}\" title=\"Click to go to the quizHUD {$nav['title']} section.\">";
            // Output the image and link name
            //echo "<img src=\"{$nav['img']}\" alt=\"icon\"/>",ucwords($name);
            
            // We'll put the link name in a span, and use CSS to add the icon
            echo "<span style=\"background:url({$nav['img']}) no-repeat left; padding-left:20px;\">",ucwords($name),'</span>';
            
            // Close the cell
            echo "</a></td>\n";
        }
    ?>
   </tr>
  </table>
  <!-- End Navigation Menu -->
  
 </div>
 <div class="bottom"><div class="right"></div></div>
</div>

<!-- Content Section -->
<div class="roundcorners_blue" id="main">
 <div class="top"><div class="right"></div></div>
 <div class="content">

 
 
 
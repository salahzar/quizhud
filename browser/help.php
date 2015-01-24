<?php

//////////
// QuizHUD help page
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
require_login(QUIZHUD_WWW_ROOT.'/browser/help.php');

$sectionname = 'help';
require_once('_page_header.php');
?>

<h3>Help</h3>
<p>
 The quizHUD is designed to allow you to create educational material and quizzes on a website,
  which can be provided to users dynamically in Second Life, via a "<acronym title="Heads Up Display">HUD</acronym>" device.
 (A HUD device is something which attaches to the user interface, just like any other window).
</p>
<br/>

<h4 id="pages">Pages</h4>
<p>
 Educational material is delivered as separate pages, which you can create/edit using the "Pages" section (see the menu above).
 Each page can contain text (with HTML formatting) and/or a single image, with various layouts which you may select.
 These pages are typically accessed when the user is using the quizHUD in "Explore" mode.
</p>

<h5 id="pageimages">Images</h5>
<p>
 You can select an image from the list to add to your page, and if you have JavaScript enabled in your browser, you will see
 a preview of it underneath.
 You can upload your own images to use on your pages, typically by using FTP software to connect to your quizHUD website.
 From the root of your quizHUD installation, look for the folder called "<?php echo QUIZHUD_IMG_FOLDER ?>", and upload your images there.
 Note that the quizHUD will not resize your images at all -- they will be displayed <strong>full size</strong>, so you will need
 to perform any scaling necessary.
</p>

<h5 id="pagelayout">Page Layouts</h5>
<p>
 There are currently 4 available layouts to choose from:
</p>
 <ul>
  <li><b>imageright</b> - the image is displayed at the top-right, with the title and body text on the left <i>(this is the default layout)</i></li>
  <li><b>imageleft</b> - the image is displayed at the top-left, with the title and body text on the right</li>
  <li><b>noimage</b> - no image is display; the title and text take up all of the space</li>
  <li><b>imageonly</b> - the image is centered in the view; no title or text are displayed at all</li>
 </ul>

<p> 
 <i>(Note: if no image is specified, then all layouts except 'imageonly' will effectively revert to 'noimage')</i>
</p>
<br/>

<h4 id="exploremode">Explore Mode</h4>
<p>
 In Explore mode, the user can click objects/features in their Second Life surroundings to learn more about them.
 When an object is clicked, the quizHUD detects what was clicked, and requests the corresponding page from your website.
 This is displayed at an approprate scale on the user's quizHUD, alongside their view of the 3d world.
 Please note, however, that you need to prepare all of the 'clickable' objects in advance by adding special scripts to them.
</p>
<br/>

<h4 id="quizmode">Quiz Mode</h4>
<p>
 You can administer assessed or non-assessed quizzes (i.e. surveys) using the quizHUD, with all attempts stored in the database.
 Each question can have an individual weighting, and there are various grading methods available.
 In each case, the grading method applies to all attempts at a single question, with the results from each question summed together
  to give the final outcome for the quiz.
 Percentage results are given in relation to the maxmimum possible score.
</p>
 <ul>
  <li><b>last</b> - only the user's most recent attempt is counted <i>(this is the default method)</i></li>
  <li><b>first</b> - only the user's first attempt is counted</li>
  <li><b>best</b> - only the user's best attempt is counted</li>
  <li><b>worst</b> - only the user's worst attempt is counted</li>
  <li><b>mean</b> - the average (mean) of all the user's attempts is calculated</li>
 </ul>
 
<p>
 There are two different types of question which can be created, either of which may be assessed or unassessed:
</p>

<h5 id="question-multichoice">Multiple Choice</h5>
<p>
 The user is presented a fixed set of options from which to select.
 You can define a score for each answer, so that there may be multiple correct answers, or some partially correct answers.
 (However, please note, only one answer can be selected by the user).
</p>

<h5 id="question-explore">Explore</h5>
<p>
 The user must answer the question by clicking a feature in their virtual surroundings.
 Any object which can be clicked in "Explore Mode" (see above) can be selected by the user.
 You can define as many correct or partially correct responses as you like,
  and any object which is clicked but not specified is treated as a wrong answer, and given a score of 0.
</p>



<?php
require_once('_page_footer.php');
?>
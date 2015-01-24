<?php
//////////
// QuizHUD browser stylesheet (some aspects are auto-generated).
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

header('Content-type: text/css');
?>

/***** MAIN LAYOUT ELEMENTS *****/
#header {
    margin-left:8px;
    margin-right:8px;
    margin-top:8px;
    margin-bottom:4px;
}

#main {
    margin-left:8px;
    margin-right:8px;
    margin-top:4px;
    margin-bottom:4px;
    
    text-align:left;
    font-family:sans-serif;
    font-size:100%;
    color:white;
}

#footer {
    margin-left:8px;
    margin-right:8px;
    margin-top:4px;
    margin-bottom:8px;
    
    text-align:right;
    vertical-align:middle;
    
    font-family:sans-serif;
    font-size:70%;
    color:#656565;
}


/***** NAV MENU *****/
#nav {
    font-size:100%;
    font-family:sans-serif;
}

#nav th {
    font-size:200%;
    vertical-align:middle;
    font-weight:bold;
    white-space:nowrap;
}

#nav td {
    font-size:125%;
    padding:0;
    vertical-align:middle;
    white-space:nowrap;
}

#nav td a {
    
    background: url(img/button_on.gif) #ffffff top left repeat;
    border:solid 1px #079b1a;
    
    color:#015b08;
    text-align:center;
    vertical-align:middle;
    
    text-decoration:none;
    
    padding-top:0px;
    padding-bottom:0px;
    padding-left:4px;
    padding-right:4px;
}

#nav td a:visited {
    text-decoration:none;
}

#nav td a:hover {
    background: url(img/button_off.gif) #ffffff top left repeat;
    text-decoration:none;
    border:solid 1px #67eb7a;
    color:#217b28;
}

#nav td.active a {
    background: url(img/button_off.gif) #ffffff top left repeat;
    border:solid 1px #67eb7a;
    color:#015b08;
}

#nav td a img {
    border-style:none;
    margin-right:4px;
}


#nav span.home {

}



<?php
// Define the names and colours of our rounded corner styles.
$roundcorners = array();
$roundcorners['green'] = '#47cb5a';
$roundcorners['orange'] = '#d3900e';
$roundcorners['blue'] = '#5878ee';

// Output the rounded corners code for each colour we have.
foreach ($roundcorners as $colname => $colcode) {
echo <<<XXXEODXXX
/***** ROUND CORNERS BOXES -- {$colname} *****/
.roundcorners_{$colname} {
    background-color:{$colcode};
}

.roundcorners_{$colname} .content {
    padding-left:16px;
    padding-right:16px;
    padding-top:0;
    margin:0;
}

.roundcorners_{$colname} .top {
    width:100%;
    height:16px;
    background:url(img/corner_{$colname}_tl.gif) no-repeat top left;
}

.roundcorners_{$colname} .top .right {
    width:100%;
    height:100%;
    background:url(img/corner_{$colname}_tr.gif) no-repeat top right;
}

.roundcorners_{$colname} .bottom {
    width:100%;
    height:16px;
    background:url(img/corner_{$colname}_bl.gif) no-repeat bottom left;
}

.roundcorners_{$colname} .bottom .right {
    width:100%;
    height:100%;
    background:url(img/corner_{$colname}_br.gif) no-repeat bottom right;
}

XXXEODXXX;
}

?>


/***** LOGIN *****/
#login {
    margin-bottom:24px;
}

/***** LINKS *****/
#main a {
    text-decoration:none;
    color:#00ddff;
}

#main a:visited {
    text-decoration:none;
    color:#ff9999;
}

#main a:hover {
    text-decoration:underline;
    color:#ffff00;
}

#main a img {
    border-style:none;
}

/***** BUTTON LINKS *****/
#main a.button {
    background-color:#44ccee;
    color:#000000;
    border:solid 1px #222222;
    font-weight:bold;
    text-decoration:none;
    
    padding-left:4px;
    padding-right:4px;
    
    padding-top:2px;
    padding-bottom:2px;
    
    white-space:nowrap;
}

#main a:visited.button {
    color:#000000;
    border:solid 1px #222222;
    text-decoration:none;
}

#main a:hover.button {
    background-color:#3399dd;
    color:#00ff00;
    border:solid 1px #000000;
    text-decoration:none;
}

#main a.button span.yes {
    background:url(img/tick.png) no-repeat left;
    padding-left:20px;
}

#main a.button span.no {
    background:url(img/cross.png) no-repeat left;
    padding-left:20px;
}

#main a.button span.newpage {
    background:url(img/page_add.png) no-repeat left;
    padding-left:20px;
}

#main a.button span.newquiz {
    background:url(img/book_add.png) no-repeat left;
    padding-left:20px;
}

#main a.button span.quiz {
    background:url(img/book.png) no-repeat left;
    padding-left:20px;
}

#main a.button span.results {
    background:url(img/book_open.png) no-repeat left;
    padding-left:20px;
}

#main a.button span.user {
    background:url(img/user.png) no-repeat left;
    padding-left:20px;
}

#main a.button span.edit {
    background:url(img/edit.png) no-repeat left;
    padding-left:20px;
}

#main a.button span.question_multiplechoice {
    background:url(img/text_list_numbers.png) no-repeat left;
    padding-left:20px;
}

#main a.button span.question_explore {
    background:url(img/find.png) no-repeat left;
    padding-left:20px;
}

#main a.button span.delete {
    background:url(img/delete.png) no-repeat left;
    padding-left:20px;
}

#main a.button span.addanswer {
    background:url(img/table_row_insert.png) no-repeat left;
    padding-left:20px;
}

#main a.button span.arrowup_notext {
	background-color:white;
    background:url(img/arrow_up.png) no-repeat left;
    width:18px;
    height:18px;
}

#main a.button span.arrowdown_notext {
	background-color:white;
    background:url(img/arrow_down.png) no-repeat left;
    width:18px;
    height:18px;
}


/***** DATA TABLES *****/
table.data {
    border-style:none;
    background-color:white;
    color:black;
    border-collapse:collapse;
}

table.data tr.header {
}

table tr.light {
    background-color:#cccccc;
}

table tr.dark {
    background-color:#aaaaaa;
}

table tr.current {
    background-color:#ffdd77;
}

table tr.delete {
    background-color:#ff5656;
}

table.data th {
    padding-left:4px;
    padding-right:4px;
}

table.data td {
    padding-left:4px;
    padding-right:4px;
}


#pagelist {
    width:100%;
    margin-left:auto;
    margin-right:auto;
}

#pagelist .id { width:2%; }
#pagelist .name { width:12%; }
#pagelist .title { width:14%; }
#pagelist .text { width:40%; }
#pagelist .image { width:12%; }
#pagelist .layout { width:12%; }
#pagelist .action { width:6%; white-space:nowrap; text-align:center; }


#userlist {
    width:70%;
    margin-left:auto;
    margin-right:auto;
}

#userlist .id { width:10%; }
#userlist .uuid { width:45%; }
#userlist .name { width:35%; }
#userlist .action { width:10%; white-space:nowrap; text-align:center; }


#quizlist {
    width:50%;
    margin-left:auto;
    margin-right:auto;
}

#quizlist .id { width:10%; }
#quizlist .name { width:45%; }
#quizlist .method { width:35%; }
#quizlist .action { width:10%; white-space:nowrap; text-align:center; }


#resultslist {
    width:40%;
    margin-left:auto;
    margin-right:auto;
}

#resultslist .name { width:70%; } /* Can be quiz or avatar name. */
#resultslist .score { width:10%; }
#resultslist .action { width:15%; white-space:nowrap; text-align:center ; }


#questioninfo {
    width:50%;
    margin-left:auto;
    margin-right:auto;
}

#questioninfo .id { width:10%; }
#questioninfo .text { width:60%; }
#questioninfo .weight { width:10%; }
#questioninfo .type { width:20%; }


.attemptinfo {
    width:60%;
    margin-left:auto;
    margin-right:auto;
}

.attemptinfo .id { width:10%; }
.attemptinfo .answer { width:50%; }
.attemptinfo .score { width:15%; }
.attemptinfo .time { width:25%; }


.answerinfo {
    width:60%;
    margin-left:auto;
    margin-right:auto;
}

.answerinfo .id { width:10%; }
.answerinfo .shortname { width:35%; }
.answerinfo .text { width:45%; }
.answerinfo .value { width:10%; }


/***** QUESTIONS *****/
#questionmenu {
    width:30%;
    font-size:80%;
    border:solid 1px black;
    margin-left:auto;
    margin-right:auto;
    white-space:nowrap;
}

.questionsection {
    margin-top:4px;
    margin-bottom:4px;
    background-color:#6888ee;
    padding-bottom:8px;
}

.questionheader {
    text-align:left;
    background-color:#7898ff;
    font-weight:bold;
    color:white;
    font-size:120%;
    padding:4px;
}

.questionsummary {
    font-size:80%;
    text-align:left;
    padding-left:24px;
}


/***** PAGE EDIT FORM *****/
#pageform {
    
}

#pageform table {
    border-style:none;
    border-collapse:collapse;
}

#pageform td {
    vertical-align:top;
    padding-bottom:16px;
}

#pageform label {
}

#pageform .note {
    font-style:italic;
    font-size:80%;
    color:#000000;
}

/***** QUIZ EDIT FORMS *****/
#quizdata {
	width:30%;
	margin-left:auto;
	margin-right:auto;
	border:solid 1px black;
	padding:16px;
	font-size:120%;
	text-align:center;
}

#questiondata {
	width:40%;
	margin-left:auto;
	margin-right:auto;
	border:solid 1px black;
	padding:16px;
	font-size:100%;
	text-align:center;
}

#answerdata {
	width:70%;
	margin-left:auto;
	margin-right:auto;
	border:solid 1px black;
	padding:16px;
	font-size:100%;
	text-align:center;
}

.answerform {
    width:90%;
    margin-left:auto;
    margin-right:auto;
    text-align:left;
}

.answerform .id { width:7%; }
.answerform .shortname { width:25%;  }
.answerform .text { width:45%; }
.answerform .value { width:8%; }
.answerform .action { text-align:left; width:15%; white-space:nowrap; }




/***** CORE STYLES *****/
.error {
    background-color:#ffaaaa;
    border:solid 2px #dd0000;
    text-align:center;
    color:#000000;
    padding:8px;
}

.error a { color:#0000ff; }
.error a:visited { color:#0000ff; }
.error a:hover { color:#ffffff; }

#main .error a { color:#0000ff; }
#main .error a:visited { color:#0000ff; }
#main .error a:hover { color:#ffffff; }

.info {
    background-color:#aaffaa;
    border:solid 2px #00dd00;
    text-align:center;
    color:black;
    padding:4px;
}


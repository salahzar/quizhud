//////////
// QuizHUD User script.
//
// Works with a separate manager device (owned or deeded to control parcel media) to present
//  a web-based quiz and other educational material on a HUD device.
//
// Authors:
//  - Peter R. Bloomfield (SL: Pedro McMillan)
//  - Clement Ramel (SL: Pidtwicky Acker)
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
//
//////////


///// CONFIGURATION /////

// Root URL of the site we are connecting to.
// (should correspond to the value in your config.php file).
string QUIZHUD_WWW_ROOT = "";

// QuizHUD password for security (should correspond to value in your config.php file).
string QUIZHUD_INTERNAL_PWD = "";

// The ID number of quiz which this HUD should use.
// If you make this 0, then it will always use the 'current' quiz,
//  which can be changed through the web-interface Quiz page.
integer quizid = 0;


///// DATA /////

// Location of the remove scripts which display our components
string SCRIPT_PAGE = "/hud/page.php"; // Display a page
string SCRIPT_QUIZ = "/hud/quiz.php"; // Display part of a quiz
string SCRIPT_QUIZ_DATA = "/quiz_data.php"; // Get script-readable quiz data


// Chat channel upon which page requests will be sent.
integer CHANNEL_PAGE_REQUEST = -1874673199;

// Chat channel upon which exploration features are requested
//  (user clicks a feature, feature chats to the HUD, HUD chats to the manager).
// Note: format of incoming messages is "uuid|name",
//  where "uuid" is the key of the avatar who touched it,
//  and "name" is the name of the object touched (the internal identifier).
integer CHANNEL_EXPLORE_REQUEST = -1874673198;


// This is a list of the IDs of questions in the current quiz
list questionids = [];
// Which question number (in our list) are we currently on?
// (This value will be negative or past the end of the list when are finished with the quiz, so validate it before use!)
integer questionnum = -1;

// HTTP request key for quiz data
key httpquiz = NULL_KEY;

// Information about the current question
string q_type = ""; // Type of question: "multiplechoice" or "explore"
list q_shortnames = []; // List of shortnames which are accepted -- only relevant for multiple choice

// Are we currently showing a quiz?
integer showingquiz = FALSE;


///// FUNCTIONS /////

// Request that a general page be loaded
display_page(string name)
{
    llRegionSay(CHANNEL_PAGE_REQUEST, QUIZHUD_INTERNAL_PWD + "\n" + QUIZHUD_WWW_ROOT + SCRIPT_PAGE + "?id=" + name);
}

// Request that summary information about the quiz be displayed
display_quiz()
{
    llRegionSay(CHANNEL_PAGE_REQUEST, QUIZHUD_INTERNAL_PWD + "\n" + QUIZHUD_WWW_ROOT + SCRIPT_QUIZ + "?quizid=" + (string)quizid + "&uuid=" + (string)llGetOwner());
}

// Request that a particular question be displayed
display_question(integer questionid)
{
    llRegionSay(CHANNEL_PAGE_REQUEST, QUIZHUD_INTERNAL_PWD + "\n" + QUIZHUD_WWW_ROOT + SCRIPT_QUIZ + "?quizid=" + (string)quizid + "&questionid=" + (string)questionid);
}

// Request that feedback from a question attempt be displayed
display_feedback(integer questionid, integer attemptid)
{
    llRegionSay(CHANNEL_PAGE_REQUEST, QUIZHUD_INTERNAL_PWD + "\n" + QUIZHUD_WWW_ROOT + SCRIPT_QUIZ + "?quizid=" + (string)quizid + "&questionid=" + (string)questionid + "&attemptid=" + (string)attemptid + "&uuid=" + (string)llGetOwner());
}

// Send an attempt to the server.
// Returns the HTTP key
key send_attempt(integer questionid, string shortname)
{
    string body = "quizid=" + (string)quizid;
    body += "&pwd=" + QUIZHUD_INTERNAL_PWD;
    body += "&questionid=" + (string)questionid;
    body += "&answer=" + llEscapeURL(shortname);
    body += "&uuid=" + (string)llGetOwner();
    body += "&avname=" + llEscapeURL(llKey2Name(llGetOwner()));
    return llHTTPRequest(QUIZHUD_WWW_ROOT + SCRIPT_QUIZ_DATA, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
}

// Activate the named tab, and de-activate the rest
activate_tab(string name)
{
    // Go through each prim in the linkset (remember they start at 1 for a linkset)
    integer num_prims = llGetNumberOfPrims();
    if (num_prims == 1) return;
    integer i = 0;
    for (i = 1; i <= num_prims; i++) {
        // Is this a tab?
        if (llGetSubString(llGetLinkName(i), 0, 3) == "tab_") {
            // Yes - is it the one being activated?
            if (llGetLinkName(i) == name) {
                llSetLinkColor(i, <1.0, 1.0, 1.0>, ALL_SIDES);
            } else {
                llSetLinkColor(i, <0.6, 0.6, 0.6>, ALL_SIDES);
            }
        }
    }
}

// Go through all tabs (including answers) and show or hide the specified tabs.
//  tabs = the list of tabs to show or hide (all others are hidden or shown respectively)
//  show = if TRUE then the listed tabs are shown while the rest are hidden. Otherwise, vice versa.
tab_visibility(list tabs, integer show)
{
    // Go through each prim in the linkset (remember they start at 1 for a linkset)
    integer num_prims = llGetNumberOfPrims();
    if (num_prims == 1) return;
    integer i = 0;
    integer in_list = FALSE;
    for (i = 1; i <= num_prims; i++) {
        // Is this a tab?
        if (llGetSubString(llGetLinkName(i), 0, 3) == "tab_" || llGetSubString(llGetLinkName(i), 0, 9) == "answertab_") {
            // Is it in our list?
            in_list = (llListFindList(tabs, [llGetLinkName(i)]) >= 0);
            // Should we show it?
            if ((in_list && show) || (!in_list && !show)) llSetLinkAlpha(i, 1.0, ALL_SIDES);
            else llSetLinkAlpha(i, 0.25, ALL_SIDES);
        }
    }
}


///// STATES /////

// In this state, the user is simply browsing and/or using the "explore" feature.
default
{
    state_entry()
    {
        // Move to idle state if not attached as HUD item or if not configured
        if (llGetAttached() <= 30) {
            state idle;
            return;
        }
        if (QUIZHUD_WWW_ROOT == "") {
            llOwnerSay("Error: not configured yet. Please provide the WWW root in the script.");
            state idle;
            return;
        }
        llOwnerSay("Connected to: " + QUIZHUD_WWW_ROOT);
        
        // Initially display the home page or a quiz summary
        if (showingquiz) {
            activate_tab("tab_quiz");
            display_quiz();
        } else {
            activate_tab("tab_home");
            display_page("home");
        }
        // Listen for explore requests
        llListen(CHANNEL_EXPLORE_REQUEST, "", NULL_KEY, "");
        
        // Show the main tabs, but hide the answer tabs
        tab_visibility(["tab_home", "tab_quiz", "tab_explore", "tab_help"], TRUE);
    }
    
    touch_end(integer n)
    {
        // Only respond if attached as a HUD device
        if (llGetAttached() <= 30) return;
        // Ignore anyone but the owner (shouldn't ever be a problem, so long as it's attached!)
        if (llDetectedKey(0) != llGetOwner()) return;
        
        // If we are showing the quiz, and the root prim has been touched, then start the quiz
        integer linknum = llDetectedLinkNumber(0);
        if (showingquiz && (linknum == 0 || linknum == 1)) {
            state start_quiz;
            return;
        }
        
        // Determine the name of the item that was touched
        string name = llGetLinkName(linknum);
        // Ignore anything but tabs
        if (llGetSubString(name, 0, 3) != "tab_") return;
        
        // What was it?
        if (name == "tab_home") {
            // Show the home page
            activate_tab(name);
            display_page("home");
            showingquiz = FALSE;
            
        } else if (name == "tab_quiz") {
            // Show summary quiz info
            activate_tab(name);
            display_quiz();
            showingquiz = TRUE;
            
        } else if (name == "tab_explore") {
            // Show the explore page
            activate_tab(name);
            display_page("explore");
            showingquiz = FALSE;
            
        } else if (name == "tab_help") {
            // Show the help page
            activate_tab(name);
            display_page("help");
            showingquiz = FALSE;
        }
    }
    
    listen(integer channel, string name, key id, string msg)
    {
        // Check the channel
        if (channel == CHANNEL_EXPLORE_REQUEST) {
            // Only respond if attached as a HUD device
            if (llGetAttached() <= 30) return;
        
            // Parse the data (uuid|name)
            list parts = llParseString2List(msg, ["|"], []);
            if (llGetListLength(parts) < 2) return;
            key av = (key)llList2String(parts, 0);
            if (av != llGetOwner()) return;
            
            // Activate the explore tab
            activate_tab("tab_explore");
            // Request the explore info page
            display_page(llList2String(parts, 1));
            
            showingquiz = FALSE;
        }
    }
    
    attach(key id) { llResetScript(); }
    on_rez(integer par) { llResetScript(); }
}

// Idle state: move to this state when not configured or not attached to HUD
state idle
{
    state_entry()
    {
        if (llGetAttached() <= 30) {
            llSetText("ERROR: Please attach me as a HUD device", <1.0, 0.0, 0.0>, 1.0);
        }
    }
    
    state_exit()
    {
        llSetText("", <0.0, 0.0, 0.0>, 0.0);
    }
    
    touch_start(integer num)
    {
        if (llDetectedKey(0) != llGetOwner()) return;
        if (llGetAttached() <= 30) llOwnerSay("Error: please attach me as a HUD device");
        if (QUIZHUD_WWW_ROOT == "") llOwnerSay("Error: not configured yet. Please provide the WWW root in the script.");
    }
    
    attach(key id) { llResetScript(); }
    on_rez(integer par) { llResetScript(); }
}

// Fetch and store quiz data, ready to start a new attempt
state start_quiz
{
    state_entry()
    {
        // Request the quiz data page with info about the quiz
        string body = "quizid=" + (string)quizid;
        body += "&pwd=" + QUIZHUD_INTERNAL_PWD;
        body += "&uuid=" + (string)llGetOwner();
        body += "&avname=" + llEscapeURL(llKey2Name(llGetOwner()));
        httpquiz = llHTTPRequest(QUIZHUD_WWW_ROOT + SCRIPT_QUIZ_DATA, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
        
        // Show only the quiz tab
        tab_visibility(["tab_quiz"], TRUE);
        
        llSetTimerEvent(0.0);
        llSetTimerEvent(8.0);
    }
    
    state_exit()
    {
        llSetTimerEvent(0.0);
    }
    
    http_response(key id, integer status, list meta, string body)
    {
        // Ignore unexpected response
        if (id != httpquiz) return;
        // Make sure this wasn't an error
        if (status != 200) {
            llOwnerSay("HTTP request failed with status code " + (string)status);
            state default;
            return;
        }
        if (body == "ERROR") {
            llOwnerSay("A server error occurred");
            state default;
            return;
        }
        if (body == "") {
            llOwnerSay("Empty response from server");
            state default;
            return;
        }
        
        // Split the response into lines
        list lines = llParseString2List(body, ["\n"], []);
        if (llGetListLength(lines) < 2) {
            llOwnerSay("ERROR: expecting 2 lines of response data from server.");
            state default;
            return;
        }
        
        // Store the separate parts
        string quizname = llList2String(lines, 0);
        questionids = llParseString2List(llList2String(lines, 1), ["|"], []);
        // Make sure we have at least one question
        if (llGetListLength(questionids) == 0) {
            llOwnerSay("Error: no questions found in quiz!");
            state default;
            return;
        }
        
        // Start with the first question
        questionnum = 0;
        llOwnerSay("Starting quiz: " + quizname);
        state ask_question;
    }
    
    timer()
    {
        llSetTimerEvent(0.0);
        llOwnerSay("Timed-out waiting for response from server.");
        state default;
    }
    
    attach(key id) { llResetScript(); }
    on_rez(integer par) { llResetScript(); }
}

// Loads question data from the server, and asks the question.
state ask_question
{
    state_entry()
    {
        // Make sure the quiz isn't finished yet
        if (questionnum < 0 || questionnum >= llGetListLength(questionids)) {
            llOwnerSay("ERROR: invalid question number");
            state default;
            return;
        }
        
        // Load the question data
        llOwnerSay("Loading question " + (string)(questionnum + 1));
        httpquiz = llHTTPRequest(QUIZHUD_WWW_ROOT + SCRIPT_QUIZ_DATA + "?quizid=" + (string)quizid + "&pwd=" + QUIZHUD_INTERNAL_PWD + "&questionid=" + llList2String(questionids, questionnum), [HTTP_METHOD, "GET"], "");
        
        llSetTimerEvent(0.0);
        llSetTimerEvent(8.0);
    }
        
    state_exit()
    {
        llSetTimerEvent(0.0);
    }
    
    http_response(key id, integer status, list meta, string body)
    {
        // Ignore unexpected response
        if (id != httpquiz) return;
        // Make sure this wasn't an error
        if (status != 200) {
            llOwnerSay("HTTP request failed with status code " + (string)status);
            state default;
            return;
        }
        if (body == "ERROR") {
            llOwnerSay("A server error occurred");
            state default;
            return;
        }
        if (body == "") {
            llOwnerSay("Empty response from server");
            state default;
            return;
        }
        
        // Split the response into lines
        list lines = llParseString2List(body, ["\n"], []);
        body = "";
        integer numlines = llGetListLength(lines);
        
        // Extract the type of the quiz
        list lineparts = llParseStringKeepNulls(llList2String(lines, 0), ["|"], []);
        if (llGetListLength(lineparts) < 3) {
            llOwnerSay("ERROR: cannot determine question type.");
            state default;
            return;
        }
        q_type = llList2String(lineparts, 2);
        lineparts = [];
        
        // If it's a multiple choice question, then extract the valid answer shortnames
        q_shortnames = [];
        if (q_type == "multiplechoice") {
            integer i = 0;
            for (i = 1; i < numlines; i++) {
                q_shortnames += [llGetSubString(llList2String(lines, i), 0, 0)];
            }
            
            // Multiple choice questions require at least one answer
            if (llGetListLength(q_shortnames) == 0) {
                llOwnerSay("ERROR: invalid question. Multiple choice questions require at least one available answer.");
            }
        }
        
        // Load the question in the display
        llOwnerSay("Question loaded.");
        display_question((integer)llList2String(questionids, questionnum));
        state answer_question;
    }
        
    timer()
    {
        llSetTimerEvent(0.0);
        llOwnerSay("Timed-out waiting for response from server.");
        state default;
    }

    attach(key id) { llResetScript(); }
    on_rez(integer par) { llResetScript(); }
}

// This state is waiting for the question to be answered
state answer_question
{
    state_entry()
    {
        // If this is an explore question, then listen for explore objects
        if (q_type == "explore") llListen(CHANNEL_EXPLORE_REQUEST, "", NULL_KEY, "");
        httpquiz = NULL_KEY;
        
        // Make the appropriate tabs visible
        list showtabs = ["tab_quiz"];
        if (q_type == "multiplechoice") {
            // (No answer tabs for explore questions)
            integer numoptions = llGetListLength(q_shortnames);
            integer i = 0;
            for (i = 0; i < numoptions; i++) {
                showtabs += ["answertab_" + llList2String(q_shortnames, i)];
            }
        }
        tab_visibility(showtabs, TRUE);
    }
    
    state_exit()
    {
        llSetTimerEvent(0.0);
    }
    
    touch_end(integer n)
    {
        // Ignore anyone but the owner (in case this was rezzed not attached to HUD)
        if (llDetectedKey(0) != llGetOwner()) return;
        // Ignore this if this is not a multiple choice question
        if (q_type != "multiplechoice") return;
        // Ignore this if an attempt has already been sent
           if (httpquiz != NULL_KEY) return;
        
        // Determine the name of the item that was touched
        string name = llGetLinkName(llDetectedLinkNumber(0));
        // Ignore anything but answer tabs
        if (llGetSubString(name, 0, 9) != "answertab_") return;
        
        // Check the letter of the tab
        string shortname = llGetSubString(name, 10, 10);
        // Is it a valid answer?
        if (llListFindList(q_shortnames, [shortname]) >= 0) {
            // Send the attempt to the server
            httpquiz = send_attempt((integer)llList2String(questionids, questionnum), shortname);
            
        } else {
            llOwnerSay("Invalid selection. Please try another.");
        }
    }
    
    listen(integer channel, string name, key id, string msg)
    {
        // Check the channel
        if (channel == CHANNEL_EXPLORE_REQUEST) {
        
            // Ignore this if this is not an explore question
            if (q_type != "explore") return;
            // Ignore this if an attempt has already been sent
            if (httpquiz != NULL_KEY) return;
        
            // Parse the data (uuid|name)
            list parts = llParseString2List(msg, ["|"], []);
            if (llGetListLength(parts) < 2) return;
            key av = (key)llList2String(parts, 0);
            if (av != llGetOwner()) return;
            
            // Send the attempt to the server
            httpquiz = send_attempt((integer)llList2String(questionids, questionnum), llList2String(parts, 1));
        }
    }
    
    http_response(key id, integer status, list meta, string body)
    {
        // Ignore unexpected response
        if (id != httpquiz) return;
        llSetTimerEvent(0.0);
        // Make sure this wasn't an error
        if (status != 200) {
            llOwnerSay("HTTP request failed with status code " + (string)status);
            state default;
            return;
        }
        if (body == "ERROR") {
            llOwnerSay("A server error occurred");
            state default;
            return;
        }
        if (body == "") {
            llOwnerSay("Empty response from server");
            state default;
            return;
        }
        
        // Split the response into lines
        list lines = llParseString2List(body, ["\n"], []);
        body = "";
        integer numlines = llGetListLength(lines);
        if (numlines < 2) {
            llOwnerSay("ERROR: expecting 2 lines of response data from server.");
            state default;
            return;
        }
        
        // The first line contains the result, and the second contains the attempt ID
        string result = llList2String(lines, 0);
        integer attemptid = (integer)llList2String(lines, 1);
        
        // Inform the user of the result
        if (result == "CORRECT") llOwnerSay("That was correct. Well done!");
        else if (result == "PART CORRECT") llOwnerSay("That was partly correct.");
        else if (result == "INCORRECT") llOwnerSay("That was incorrect.");
        else if (result == "UNASSESSED") llOwnerSay("Thank you for your selection. (This question was not assessed)");
        else {
            llOwnerSay("Invalid response. Please try again.");
            httpquiz = NULL_KEY;
            return;
        }
        
        // Display the feedback page, and advance the quiz
        display_feedback((integer)llList2String(questionids, questionnum), attemptid);
        state next_question;
    }
    
    timer()
    {
        llSetTimerEvent(0.0);
        llOwnerSay("Timed-out waiting for response from server.");
        state default;
    }

    attach(key id) { llResetScript(); }
    on_rez(integer par) { llResetScript(); }
}

// Advance to the next question when the user touches the display,
//  or finish the quiz as necessary
state next_question
{
    state_entry()
    {
        // Make only the quiz tab visible
        tab_visibility(["tab_quiz"], TRUE);
    
        // Advance the question counter
        questionnum++;
    }
    
    touch_end(integer num)
    {
        // Ignore anyone but the owner (in case this was rezzed not attached to HUD)
        if (llDetectedKey(0) != llGetOwner()) return;
        // If it was the root prim (the display) that was touched, then continue
        if (llDetectedLinkNumber(0) <= 1) {
            // Have we reached the end of the quiz?
            if (questionnum >= llGetListLength(questionids)) {
                // Yes - go back to the quiz summary (shows the score)
                llOwnerSay("Quiz Complete.");
                state default;
            } else {
                // No - advanced to the next question
                state ask_question;
            }
        }
    }

    attach(key id) { llResetScript(); }
    on_rez(integer par) { llResetScript(); }
}


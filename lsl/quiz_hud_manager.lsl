//////////
// Quiz Manager script.
// Manages the per-user parcel media for the Quiz HUD object.
// Note: relies on separate parcel media command (PMC) scripts to perform appropriate function calls.
// This mitigates the load of script delays.
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

// QuizHUD password for security (should correspond to value in your config.php file).
string QUIZHUD_INTERNAL_PWD = "";


///// DATA /////

// Chat channel upon which page requests will be received.
integer CHANNEL_PAGE_REQUEST = -1874673199;

// Link message channels for parcel media commands
list CHANNEL_PMC = [
    -1874673200,
    -1874673201,
    -1874673202,
    -1874673203,
    -1874673204,
    -1874673205,
    -1874673206,
    -1874673207,
    -1874673208,
    -1874673209
];

// The most recently used parcel media command script
integer currentpmc = 0;



///// FUNCTIONS /////

// Send the parcel media command to change the given user's URL
set_user_media(key user, string url)
{
    // Progress to the next PMC script
    currentpmc = ((currentpmc + 1) % llGetListLength(CHANNEL_PMC));
    // Send a link message to our PMC script
    llMessageLinked(LINK_THIS, llList2Integer(CHANNEL_PMC, currentpmc), url, user);
}


///// STATES /////

default   
{  
    state_entry()  
    { 
        // Listen for page requests from any source
        llListen(CHANNEL_PAGE_REQUEST, "", NULL_KEY, "");
    }
    

    listen(integer channel, string name, key id, string msg)
    {
        // Check which channel it is
        if (channel == CHANNEL_PAGE_REQUEST) {
            // Make sure we can get a valid owner from the requesting object.
            // (This provides security to prevent fraud.)
            key user = llGetOwnerKey(id);
            if (user == NULL_KEY) return;
            
            // The provided string should contain a password and a URL, separated by a newline character
            list parts = llParseString2List(msg, ["\n"], []);
            if (llGetListLength(parts) != 2) return;

            // Make sure the password is correct
            if (llList2String(parts, 0) != QUIZHUD_INTERNAL_PWD) return;
            // Load the provided URL as the per-user media texture.
            set_user_media(user, llList2String(parts, 1));
        }
    }
}


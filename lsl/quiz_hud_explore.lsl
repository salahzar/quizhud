//////////
// QuizHUD User script.
//
// Works with separate HUD and  manager devices.
// When clicked, it will report itself and illicit a response,
//  such as displaying a resource, or answering a quiz question.
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

// Set this name to reflect the object's identity.
// When used in "explore" mode, it will display the HUD page of this name.
// Can contain letters, numbers, dashes, and underscores.
// Should typically start with "explore-".
string EXPLORE_NAME = "explore-test";


///// DATA /////

// Chat channel upon which exploration features are requested.
//  (user clicks a feature, feature chats to the HUD, HUD chats to the manager).
// Note: format of incoming messages is "uuid|name",
//  where "uuid" is the key of the avatar who touched it,
//  and "name" is the name of the object touched (the internal identifier).
integer CHANNEL_EXPLORE_REQUEST = -1874673198;


///// STATES /////

// Single state -- nothing complex!
default
{
    touch_end(integer num)
    {
        // Report each toucher
        integer i = 0;
        for (i = 0; i < num; i++) {
            llRegionSay(CHANNEL_EXPLORE_REQUEST, (string)llDetectedKey(i) + "|" + EXPLORE_NAME);
        }
    }
}


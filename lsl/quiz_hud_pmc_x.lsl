//////////
// Quiz Manager Parcel Media Command script.
// Performs the parcel media command calls on behalf of the quizHUD manager script.
// This gets around the issue of script delays.
//
// Authors:
//  - Peter R. Bloomfield (SL: Pedro McMillan)
//
// This script is released as-is, without warranty, under the GNU GPL v3.
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

// This script's number.
// Each of these scripts in a given object should have a separate number,
//  which should not exceed the size of the channel list below.
integer MY_SCRIPT_NUM = 0;


///// DATA /////

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

// The channel number which we will listen on
integer MY_CHANNEL = -1;


///// STATES /////

default
{
    state_entry()
    {
        // Determine our channel number
        if (MY_SCRIPT_NUM >= 0 && MY_SCRIPT_NUM < llGetListLength(CHANNEL_PMC)) MY_CHANNEL = llList2Integer(CHANNEL_PMC, MY_SCRIPT_NUM);
    }

    link_message(integer sender_num, integer num, string sval, key kval)
    {
        // Ignore anything on the wrong channel
        if (num != MY_CHANNEL) return;
        // The key should identify the avatar, and the string should be a URL
        if (kval == NULL_KEY || sval == "") return;
        
        // Run the PMCs
        llParcelMediaCommandList([
            PARCEL_MEDIA_COMMAND_AGENT, kval, PARCEL_MEDIA_COMMAND_STOP,
            PARCEL_MEDIA_COMMAND_AGENT, kval, PARCEL_MEDIA_COMMAND_URL, sval,
            PARCEL_MEDIA_COMMAND_AGENT, kval, PARCEL_MEDIA_COMMAND_PLAY
        ]);
    }
}

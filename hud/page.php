<?php
//////////
// Renders a content page constructed from database data.
// The name of the page should be provided in parameter 'id'.
//
// Author: Peter R. Bloomfield
//
// GPL:
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

// Fetch the parameter
$id = 'home';
if (isset($_REQUEST['id'])) $id = $_REQUEST['id'];

require_once('_page_header.php');

// Attempt to get the page
if (!$page = get_page($id)) error("Failed to find page \"$id\".");
$page->render();

require_once('_page_footer.php');
?>

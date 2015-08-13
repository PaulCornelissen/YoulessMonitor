<?php
/*
This file is part of Youless Monitor.

Youless Monitor is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Youless Monitor is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Youless Monitor.  If not, see <http://www.gnu.org/licenses/>.
*/

session_start();

if (isset($_POST['login']) && $_POST['login'] == 1) {
	$u = addslashes($_POST['user']);
	$p = sha1($_POST['pass']);
	
	$r = $db->getLogin($u, $p);

	if ($r) {
		$_SESSION['user_id'] = $r;
	} else {
		$_SESSION['user_id'] = false;
	}
}

if (isset($_GET['logout']) && $_GET['logout'] == 1) {
	session_destroy();
	header("Location: login.php");
	exit;
}

if (!isset($_SESSION['user_id']) && (!NO_LOGIN || !defined(NO_LOGIN))) {
	header("Location: login.php");
	exit;
}

?>

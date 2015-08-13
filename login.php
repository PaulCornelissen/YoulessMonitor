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

include "inc/settings.inc.php";

if(defined(NO_LOGIN) && NO_LOGIN) {
	header("Location: index.php");
}

$loginInvalid = false;

if(isset($_SESSION['user_id']) && !$_SESSION['user_id'])
{
	$loginInvalid = true;
	unset($_SESSION['user_id']);
}
?>
<!DOCTYPE HTML>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1"/>
		<title>YouLess - Energy Monitor</title>
		<link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico" />
		<link type="text/css" href="css/style.min.css" rel="stylesheet" />
		<link type="text/css" href="css/responsive.css" rel="stylesheet" />		
		<script type="text/javascript" src="js/jquery-1.8.3.js"></script>
		<script>
			$(document).ready(function() {
				$('input[name=user]').focus();
			});
		</script>
	</head>
	<body>
		<div id="topHeader"></div>
		<div id="header">
		
			<div id="logo"></div>
					
		</div>
		<div id="container">
			<div id="loginForm">
				<form method="post" action="index.php">
				<table>
					<tr>
						<td colspan="2" id="invalidLogin"><?php echo ($loginInvalid ? 'Gebruikersnaam en/of wachtwoord onjuist' : '') ?></td>
					</tr>				
					<tr>
						<td>Gebruikersnaam:</td>
						<td><input type="text" name="user" size="20" /></td>
					</tr>
					<tr>
						<td>Wachtwoord:</td>
						<td><input type="password" name="pass" size="20" /></td>
					</tr>
					<tr>
						<td colspan="2">
							<input type="hidden" name="login" value="1" />
							<input id="loginSubmit" type="submit" value="Inloggen"/>
						</td>
					</tr>
				</table>

				</form>
			</div>
		</div>
	</body>
</html>
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
?>
<!DOCTYPE HTML>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>YouLess - Energy Monitor</title>
		<link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico" />
		<link type="text/css" href="css/style.min.css" rel="stylesheet" />
	</head>
	<body>
				
		<div id="topHeader"></div>
		<div id="header">
			<div id="logo"></div>
		</div>
		<div id="container">
			<div id="installDiv">
		
<?php

	$errorMsg = '';
	$ok = true;

	if (version_compare(PHP_VERSION, '5.2.0') <= 0) 
	{
		$errorMsg .= '<p class="error"><b>PHP 5.2.0</b> is vereist</p>';
		$ok = false;
	}	
//	if(!file_exists('inc/settings.inc.php'))
//	{
//		$errorMsg .= '<p class="error"><b>settings.inc.php</b> ontbreekt, pas <b>settings.inc.php.example</b> aan en hernoem deze naar <b>settings.inc.php</b></p>';
//		$ok = false;
//	}
	if(!extension_loaded('pdo_mysql'))
	{
		$errorMsg .= '<p class="error"><b>PDO Mysql</b> extension ontbreekt!</p>';
		$ok = false;
	}
	if(!extension_loaded('curl'))
	{
		$errorMsg .= '<p class="error"><b>CURL extension</b> ontbreekt!</p>';
		$ok = false;
	}
	
	echo $errorMsg;
	if($ok)
	{
		if(isset($_POST['install']) && $_POST['install'] == "1") {
			$public = "false";
			$debug = "false";
			if(isset($_POST['public']) && $_POST['public'] == "true") $public = "true";
			if(isset($_POST['debug']) && $_POST['debug'] == "true") $debug = "true";

			$settings = "<?php
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

// Rename to settings.inc.php

// DB Settings
define('DB_HOST', '" . $_POST['DBHOST'] . "');	
define('DB_NAME', '" . $_POST['DBNAME'] . "');
define('DB_USER', '" . $_POST['DBUSER'] . "');
define('DB_PASS', '" . $_POST['DBPASS'] . "');
define('DB_PREFIX', '" . $_POST['DBPREFIX'] . "');

// YouLess settings
define('YL_ADDRESS', '" . $_POST['YLADDRESS'] . "');
define('YL_PASSWORD', '" . $_POST['YLPASS'] . "');

// Set this to true if you don't care for any protection (e.g. running on a private LAN)
define('NO_LOGIN', " . $public . ");

// Set this to true to get all kinds of verbose debuging output
define('VERBOSE', " . $debug . ");
?>";

			$file = realpath(dirname(__FILE__)) . "/inc/testsettings2.inc.php";
			if($debug == 'true') echo "Generated settings:<br>" . nl2br(htmlspecialchars($settings)) . "<br><br>";

			file_put_contents($file, $settings);

			define('DB_HOST', $_POST['DBHOST']);	
			define('DB_NAME', $_POST['DBNAME']);
			define('DB_USER', $_POST['DBUSER']);
			define('DB_PASS', $_POST['DBPASS']);
			define('DB_PREFIX', $_POST['DBPREFIX']);
			
			try {
			    $db = new PDO("mysql:host=".DB_HOST, DB_USER, DB_PASS);
			    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
			
			    $query = "CREATE DATABASE IF NOT EXISTS `".DB_NAME."`;

					CREATE TABLE IF NOT EXISTS  `".DB_NAME."`.`" . DB_PREFIX . "data_m` (
					  `id` int(11) NOT NULL AUTO_INCREMENT,
					  `time` datetime NOT NULL,
					  `unit` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					  `delta` int(11) NOT NULL,
					  `value` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
					  PRIMARY KEY (`id`),
					  UNIQUE KEY `time` (`time`)
					) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1;

					ALTER TABLE  `".DB_NAME."`.`" . DB_PREFIX . "data_m` ADD COLUMN cpKwh   	decimal(10,6);
					ALTER TABLE  `".DB_NAME."`.`" . DB_PREFIX . "data_m` ADD COLUMN IsLow   	tinyint(1);				

					CREATE TABLE IF NOT EXISTS  `".DB_NAME."`.`" . DB_PREFIX . "settings` (
					  `key` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					  `value` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					  UNIQUE KEY `key` (`key`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;	

					INSERT INTO  `".DB_NAME."`.`" . DB_PREFIX . "settings` (`key`, `value`) VALUES
					('cpkwh', '0.22'),
					('cpkwh_low', '0.21'),
					('dualcount', '1'),
					('cpkwhlow_start', '23:00'),
					('cpkwhlow_end', '07:00'),
					('liveinterval', '1000'),
					('version','2.2.0'),
					('LastUpdate_UnixTime', '0'),
					('livelengte', '60000'),
					('cooldown', '60');
									
					CREATE TABLE IF NOT EXISTS  `".DB_NAME."`.`" . DB_PREFIX . "users` (
					  `id` int(11) NOT NULL AUTO_INCREMENT,
					  `username` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					  `password` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					  PRIMARY KEY (`id`)
					) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1;
					
					CREATE TABLE IF NOT EXISTS  `".DB_NAME."`.`" . DB_PREFIX . "meter` (
						`time` datetime NOT NULL ,
						`count` decimal( 10, 3 ) NOT NULL ,
						`islow` tinyint( 1 ) NOT NULL ,
						UNIQUE KEY `islow` ( `islow` )
					) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
					
					INSERT INTO  `".DB_NAME."`.`" . DB_PREFIX . "meter` (`time`, `count`, `islow`) VALUES
					( '2013-01-01','0','0' ),
					( '2013-01-01','0','1' );
						
					INSERT INTO  `".DB_NAME."`.`" . DB_PREFIX . "users` (`id`, `username`, `password`) VALUES
					(2, '" . $_POST['USER'] . "', '" . sha1($_POST['PASS']) . "'); ";

			    $succes = $db->exec($query);
				
				if($succes > 0)
				{
					echo "<p style='color:green;'>Database installatie succesvol.</p>";
					echo "<p style='color:green;'>Gebruikersnaam en wachtwoord zijn opgeslagen.</p>";
					if($debug) echo "<p><b>Debug informatie:</b><br>" . nl2br($query) . "</p>";
				}
			} catch (PDOException $e) {
			    die(print("<p class='error'>Database error: ". $e->getMessage() ."</p>"));
			}
			

			// We try to automatically delete the install.php and update.php file
			if(unlink(realpath(dirname(__FILE__)) . "/install.php")) {
				echo "<p style='color:green;'>Automatische verwijdering install.php succesvol!</p>";
			}
			else {
				echo '<p class="error"><b>Error:</b> couldn\'t self delete!<br>U dient het bestand <b>install.php</b> zelf te verwijderen!</p>';
			}

			if(unlink(realpath(dirname(__FILE__)) . "/update.php")) {
				echo "<p style='color:green;'>Automatische verwijdering update.php succesvol!</p>";
			}
			else {
				echo '<p class="error"><b>Error:</b> couldn\'t delete update file!<br>U dient het bestand <b>update.php</b> zelf te verwijderen!</p>';
			}
		}
		else {
			echo '<p class="error"><b>Pas op:</b> vul dit formulier secuur in!</p>
				<form action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '" method="post">
					<input type="hidden" name="install" value="1">
					<table>
						<tr><td>Database hostname*: 		</td><td><input type="text" name="DBHOST" value="localhost"></td>
						<tr><td>Database name*:				</td><td><input type="text" name="DBNAME" value="youless"></td>
						<tr><td>Database user*:				</td><td><input type="text" name="DBUSER" value=""></td>
						<tr><td>Database password*:			</td><td><input type="password" name="DBPASS" value=""></td>
						<tr><td>Table prefix:<br><br>		</td><td><input type="text" name="DBPREFIX" value="YL_"><br><br></td>

						<tr><td>Youless adress*:			</td><td><input type="text" name="YLADDRESS" value=""></td>
						<tr><td>Youless password:<br><br>	</td><td><input type="password" name="YLPASS" value=""><br><br></td>

						<tr><td>Website username*:			</td><td><input type="text" name="USER" value="admin"></td>
						<tr><td>Website password:<br><br>	</td><td><input type="password" name="PASS" value="admin"><small>(default: admin)</small><br><br></td>

						<tr><td colspan="2">		<input type="checkbox" name="public" value="true"> Disable authentication?</td>
						<tr><td colspan="2">		<input type="checkbox" name="debug" value="true"> Enable debug output? (recommended: off!)<br><br></td>
						<tr><td>							</td><td><input type="submit" value="Installatie beginnen!"></td>
					</table>
				</form>';
		}
	}
?>
			</div>
		</div>
	<div id="footer"><?PHP //include("inc/date-modified.php");?></div>
	</body>
</html>

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

	$settingsFile = realpath(dirname(__FILE__)) . "/inc/settings.inc.php";
	$errorMsg = '';
	$ok = true;

	if (version_compare(PHP_VERSION, '5.2.0') <= 0) 
	{
		$errorMsg .= '<p class="error"><b>PHP 5.2.0</b> is vereist</p>';
		$ok = false;
	}
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

	// If any error's occured, we don't continue.
	if($ok)
	{
		// We only continue if the install form has been sent, otherwise we show the form.
		if(isset($_POST['install']) && $_POST['install'] == "1") {

			// First we write the settings to file
			$public = "false";
			$debug = "false";
			if(isset($_POST['public']) && $_POST['public'] == "true") $public = "true";
			if(isset($_POST['debug']) && $_POST['debug'] == "true") $debug = "true";

			$settings = "<?php
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

			if($debug == 'true') echo "Gegenereerde instellingen:<br>" . nl2br(htmlspecialchars($settings)) . "<br><br>";

			file_put_contents($settingsFile, $settings);


			// We construct a query to create and/or update the database
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

					INSERT IGNORE INTO  `".DB_NAME."`.`" . DB_PREFIX . "settings` (`key`, `value`) VALUES
					('cpkwh', '0.22'),
					('cpkwh_low', '0.21'),
					('dualcount', '1'),
					('cpkwhlow_start', '23:00'),
					('cpkwhlow_end', '07:00'),
					('liveinterval', '1000'),
					('livelengte', '60000'),
					('cooldown', '60');

					REPLACE INTO `".DB_NAME."`. `" . DB_PREFIX . "settings` (`key`, `value`) VALUES
					('version','2.2.0'),
					('LastUpdate_UnixTime', '0');
									
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
					";
				
				if(!isset($_POST['upgrade']))
				{
					$query .= "REPLACE INTO  `".DB_NAME."`.`" . DB_PREFIX . "users` (`id`, `username`, `password`) VALUES
					(2, '" . $_POST['USER'] . "', '" . sha1($_POST['PASS']) . "'); ";
				}

			    $succes = $db->exec($query);
				
				if($succes > 0)
				{
					if(isset($_POST['upgrade'])) {
						echo "<p style='color:green;'>Database succesvol bijgewerkt.</p>";
					}
					else {
						echo "<p style='color:green;'>Database installatie succesvol.</p>";
						echo "<p style='color:green;'>Gebruikersnaam en wachtwoord zijn opgeslagen.</p>";
					}
					if($debug == 'true') echo "<p><b>Debug informatie:</b><br>" . nl2br($query) . "</p>";
				}
			} catch (PDOException $e) {
			    die(print("<p class='error'>Database fout: ". $e->getMessage() ."</p>"));
			}
			

			// We try to automatically delete the install.php and update.php file
			$installpath = realpath(dirname(__FILE__)) . "/install.php";
			$updatepath = realpath(dirname(__FILE__)) . "/update.php";
			if(file_exists($installpath))
			{
				if(unlink($installpath)) {
					echo "<p style='color:green;'>Automatische verwijdering install.php succesvol!</p>";
				}
				else {
					echo '<p class="error"><b>Fout:</b> dit script kon zichzelf niet verwijderen! U dient het bestand <b>install.php</b> zelf te verwijderen!</p>';
				}
			}
			else {
				echo '<p class="error"><b>Fout:</b> Kan het bestand <b>install.php</b> niet vinden! Dit is ongewoon. Controleer zelf of het installatiebestand verwijdert is!</p>';
			}

			if(file_exists($updatepath))
			{
				if(unlink($updatepath)) {
					echo "<p style='color:green;'>Automatische verwijdering update.php succesvol!</p>";
				}
				else {
					echo '<p class="error"><b>Fout:</b> kon het bestand niet verwijderen! U dient het bestand <b>update.php</b> zelf te verwijderen!</p>';
				}
			}

			echo '<p><b>Volgende stap:</b><br>Maak een cronjob op uw server. Zie de readme voor meer uitleg.<br>
			Als geheugensteuntje het pad naar het script:<br>' . htmlspecialchars(realpath(dirname(__FILE__))) . '/cronjob.php</p>';
		}
		// We show a form to collect the data required for installation.
		else {
			$upgrade = false;
			if (file_exists($settingsFile)) {
				// Settings file allready exists, so we want to upgrade
				include($settingsFile);
				$upgrade = true;
			}

			// We check if all required settings are present
			if(!DEFINED('DB_HOST')) 	define('DB_HOST', 'localhost');	
			if(!DEFINED('DB_NAME')) 	define('DB_NAME', 'youless');
			if(!DEFINED('DB_USER')) 	define('DB_USER', 'youless');
			if(!DEFINED('DB_PASS')) 	define('DB_PASS', 'wachtwoord');
			if(!DEFINED('DB_PREFIX')) 	define('DB_PREFIX', 'YL_');
			if(!DEFINED('YL_ADDRESS')) 	define('YL_ADDRESS', '192.168.0.125');
			if(!DEFINED('YL_PASSWORD')) define('YL_PASSWORD', '');
			if(!DEFINED('NO_LOGIN')) 	define('PUBLIC_CHECKED', '');
			else {
				if(NO_LOGIN) define('PUBLIC_CHECKED', ' checked');
				else define('PUBLIC_CHECKED', '');
			}
			if(!DEFINED('VERBOSE')) 	define('DEBUG', '');
			else {
				if(VERBOSE) define('DEBUG', ' checked');
				else define('DEBUG', '');
			}

			// We show a form to acquire all data
			if($upgrade) echo '			<p class="error"><b>Pas op:</b> Er is een bestaande installatie gedetecteerd. De bestaande instelling zijn hieronder overgenomen. Controleer deze a.u.b. goed.</p>
';
			echo '			<p class="error"><b>Pas op:</b> Vul dit formulier secuur in!</p>
				<form action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '" method="post">
					<input type="hidden" name="install" value="1">';
			if($upgrade) echo '
					<input type="hidden" name="upgrade" value="1">';
			echo '
					<table>
						<tr><td>Database hostname*: 			</td><td><input type="text" name="DBHOST" value="' . DB_HOST . '"></td>
						<tr><td>Database naam*:					</td><td><input type="text" name="DBNAME" value="' . DB_NAME . '"></td>
						<tr><td>Database gebruikersnaam*:		</td><td><input type="text" name="DBUSER" value="' . DB_USER . '"></td>
						<tr><td>Database wachtwoord*:			</td><td><input type="password" name="DBPASS" value="' . DB_PASS . '"></td>
						<tr><td>Tabelnaamvoorvoegsel:<br><br>	</td><td><input type="text" name="DBPREFIX" value="' . DB_PREFIX . '"><small>(Als je upgrade heeft je database waarschijnlijk geen prefix.)</small><br><br></td>

						<tr><td>Youless adres*:					</td><td><input type="text" name="YLADDRESS" value="' . YL_ADDRESS . '"></td>
						<tr><td>Youless wachtwoord:<br><br>		</td><td><input type="password" name="YLPASS" value="' . YL_PASSWORD . '"><br><br></td>
';
			if(!$upgrade) echo '
						<tr><td>Website gebruikersnaam*:		</td><td><input type="text" name="USER" value="admin"></td>
						<tr><td>Website wachtwoord:<br><br>		</td><td><input type="password" name="PASS" value="admin"><small>(standaard: admin)</small><br><br></td>
';
			echo '
						<tr><td colspan="2">	<input type="checkbox" name="public" value="true"' . PUBLIC_CHECKED . '> Inloggen uitschakelen?</td>
						<tr><td colspan="2">	<input type="checkbox" name="debug" value="true"' . DEBUG . '> Debug output weergeven? <b>(aangeraden: UIT!)</b><br><br></td>
						<tr><td>								</td><td><input type="submit" value="Installatie beginnen!"></td>
					</table>
				</form>';
		}
	}
?>
			</div>
		</div>
	<div id="footer"><?PHP include("inc/date-modified.php");?></div>
	</body>
</html>

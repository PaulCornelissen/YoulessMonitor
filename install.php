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
	if(!file_exists('inc/settings.inc.php'))
	{
		$errorMsg .= '<p class="error"><b>settings.inc.php</b> ontbreekt, pas <b>settings.inc.php.example</b> aan en hernoem deze naar <b>settings.inc.php</b></p>';
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
	if($ok)
	{
		include 'inc/settings.inc.php';
		
		try {
		    $db = new PDO("mysql:host=".DB_HOST, DB_USER, DB_PASS);
		    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
		
		    $query = "CREATE DATABASE IF NOT EXISTS `".DB_NAME."`;
				CREATE TABLE IF NOT EXISTS  `".DB_NAME."`.`" . DB_PREFIX . "data_h` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `time` datetime NOT NULL,
				  `unit` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
				  `delta` int(11) NOT NULL,
				  `value` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
				  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				  PRIMARY KEY (`id`),
				  KEY `time` (`time`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1;

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
				
				
				CREATE TABLE IF NOT EXISTS  `".DB_NAME."`.`" . DB_PREFIX . "kwh_h` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `kwh` varchar(20) NOT NULL,
				  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				  PRIMARY KEY (`id`),
				  KEY `inserted` (`inserted`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;					

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
				(2, 'admin', 'd033e22ae348aeb5660fc2140aec35850c4da997');

				INSERT INTO  `".DB_NAME."`.`" . DB_PREFIX . "settings` (`key`, `value`) VALUES
				('version', '2.1.0'); ";

		    $succes = $db->exec($query);
			
			if($succes > 0)
			{
				echo "<p style='color:green;'>Installatie succesvol. Verwijder <b>install.php</b> en <b>update.php</b></p>";
				echo "<p style='color:green;'>Default gebruikersnaam/wachtwoord is <b>admin</b>/<b>admin</b></p>";
				echo "<p><b>Debug informatie:</b><br>" . nl2br($query) . "</p>";
			}
		} catch (PDOException $e) {
		    die(print("<p class='error'>Database error: ". $e->getMessage() ."</p>"));
		}		
	}
?>
			</div>
		</div>
	<div id="footer"><?PHP include("inc/date-modified.php");?></div>
	</body>
</html>

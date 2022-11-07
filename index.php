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

ini_set('memory_limit', '-1');

if(file_exists('install.php') || file_exists('update.php'))
{
	die('<p>Verwijder <b>install.php</b> en <b>update.php</b>!</p>');
}

include "inc/settings.inc.php";
include "classes/database.class.php";
$db = new Database();

include "classes/generic.class.php";	
$gen = new Generic();

include "inc/session.inc.php";

date_default_timezone_set('Europe/Amsterdam');

$settings = $db->getSettings();
$metercal = $db->getMetercal();

if($settings === false) {
	die('<p>Fatal error. Did you properly install the softare? To do so, please run install.php or update.php</p>');
}

$startTime = explode(":", $settings['cpkwhlow_start']);
$endTime = explode (":", $settings['cpkwhlow_end']);

$startSelect = $gen->timeSelector($startTime[0], $startTime[1], 'cpkwhlow_start');
$endSelect = $gen->timeSelector($endTime[0], $endTime[1], 'cpkwhlow_end');

$intervalOptions = array(
	'500' => '500',
	'1000' => '1000',
	'2000' => '2000',
	'5000' => '5000'
);
$intervalSelect = $gen->selector('liveinterval', $settings['liveinterval'], $intervalOptions);

$liveOptions = array(
    '60000' => '1 min',
    '120000' => '2 min',
    '180000' => '3 min',
    '300000' => '5 min',
    '600000' => '10 min',
);
$liveSelect = $gen->selector('livelengte', $settings['livelengte'], $liveOptions);

$contributors = array(
    '-LA-' => '',
    'J van der Kroon' => '',
    'Xander' => '', 
    'Michiel Nijkamp' => '',
    'Paul Cornelissen' => 'http://bit-byters.net/'
);

$credits = 'Copyright 2015. Deze software is beschikbaar onder de <a href="http://www.gnu.org/licenses/gpl.html" target="_blank">GNU General Public License versie 3</a>. Dit is vrije software.<br><br>De bijdragers aan deze software zijn:<br>';
foreach($contributors as $k => $v) {
    if($v == '') $credits .= $k . "<br>\n";
    else $credits .= '<a href="' . $v . '">' . $k . "</a><br>\n";
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
		<script type="text/javascript" src="js/jquery-ui-1.8.18.custom.min.js"></script>
		<script type="text/javascript" src="js/highstock.js"></script>
		<script type="text/javascript" src="js/modules/exporting.js"></script>
		<script type="text/javascript" src="js/script.js"></script>
	</head>
	<body>
    
        <div id="overlaySucces" class="overlay">
            <div class="dialog" id="dialogSucces">
                <div id="message"></div>
                <input type="button" id="closeDialogSucces" class="closeDialog" value="Sluit"/>
            </div>
            <div class="overlayBack"></div>
        </div>

        <div id="overlayCredits" class="overlay">
            <div class="dialog" id="dialogCredits">
                <div id="messageCredits"><?php echo $credits ?></div>
                <input type="button" id="closeDialogCredits" class="closeDialog" value="Sluit"/>
            </div>
            <div class="overlayBack"></div>
        </div>
    
		<div id="settingsOverlay"   data-dualcount="<?php echo htmlspecialchars($settings['dualcount']); ?>" 
                                    data-liveinterval="<?php echo htmlspecialchars($settings['liveinterval']); ?>" 
                                    data-livelengte="<?php echo htmlspecialchars($settings['livelengte']); ?>">

			<form>
				<table id="settingsMeters" class="settingsTab">
					<tr>
						<td style="width:200px;" colspan="2">Meter type:</td>
						<td>Enkel<input type="radio" name="dualcount" value="0" <?php echo ($settings['dualcount'] == 0 ? 'checked=checked' : '') ?>/> 
                        Dubbel<input type="radio" name="dualcount" value="1" <?php echo ($settings['dualcount'] == 1 ? 'checked=checked' : '') ?>/></td>
					</tr>				
					<tr>
						<td colspan="2">Prijs per kWh:</td>
						<td><input type="text" name="cpkwh" value="<?php echo $settings['cpkwh']; ?>"/></td>
					</tr>
					<tr class="cpkwhlow" <?php echo ($settings['dualcount'] == 1 ? '' : 'style="display:none;"') ?>;>
						<td colspan="2">Prijs per kWh (laagtarief):</td>
						<td><input type="text" name="cpkwh_low" value="<?php echo htmlspecialchars($settings['cpkwh_low']); ?>"/></td>
					</tr>	
					<tr class="cpkwhlow" <?php echo ($settings['dualcount'] == 1 ? '' : 'style="display:none;"') ?>;>
						<td colspan="2">Tijd laagtarief:</td>
						<td><?php echo $startSelect; ?> tot <?php echo $endSelect; ?></td>
					</tr>
                    <tr>
                        <td colspan="2">Update interval live weergave:</td>
                        <td><?php echo $intervalSelect; ?> ms</td>
                    </tr>
                    <tr>
                        <td colspan="2">Lengte live weergave:</td>
                        <td><?php echo $liveSelect; ?></td>
                    </tr>
					<?php if(!defined(NO_LOGIN) || !NO_LOGIN) echo '
					<tr>
						<td colspan="3"><br></td>
					</tr>
					<tr>
						<td colspan="2">Admin wachtwoord:</td>
						<td><input type="password" name="password" value=""/></td>
					</tr>
					<tr>
						<td colspan="2">Bevestig admin wachtwoord:</td>
						<td><input type="password" name="confirmpassword" value=""/></td>
					</tr>'; 
					?>
					<tr>
						<td colspan="3"><br></td>
					</tr>
					<tr>
						<td></td>
						<td>Datum/tijd</td>
						<td>Stand</td>
					</tr>	
					<?php foreach($metercal as $k => $v) { ?>
					<tr class="meter_row">
						<td><?php if ($v['islow'] == '0') { echo 'Hoog tarief'; }  else { echo 'Laag tarief'; }  ?></td>
						<td><input type="text"   name="metercal[<?php echo $k; ?>][time]" value="<?php echo htmlspecialchars($v['time']); ?>"/></td>
						<td><input type="text"   name="metercal[<?php echo $k; ?>][count]" value="<?php echo htmlspecialchars($v['count']); ?>"/></td>
						<td><input type="hidden" name="metercal[<?php echo $k; ?>][islow]" value="<?php echo htmlspecialchars($v['islow']); ?>"/></td>
					</tr>						
					<?php } ?>
					<tr>
						<td colspan="3"><br></td>
					</tr>
					<tr>
						<td colspan="2"></td>
						<td><input type="submit" id="saveSettings" value="Opslaan"/><input type="button" id="hideSettings" value="Sluiten"/></td>
					</tr>
				</table>				
			</form>	
			<div id="version"><a href="#" id="showCredits">Versie <?php echo htmlspecialchars($settings['version']); ?></a></div>
		</div>
		
		<div id="topHeader">
			<div id="settings"><a href="#" id="showSettings">Instellingen</a></div>
			<?php if(!defined(NO_LOGIN) || !NO_LOGIN) echo '<div id="logout"><a href="?logout=1">Logout</a></div>'; ?>
		</div>
		<div id="header">
		
			<div id="logo"></div>
		
			<div id="menu">
				<ul class="btn">
					<li class="selected"><a href="#" data-chart="live" class="showChart">Live</a></li>
					<li id="day"><a href="#" data-chart="day" class="showChart">Dag</a></li>
					<li id="week"><a href="#" data-chart="week" class="showChart">Week</a></li>
					<li id="month"><a href="#" data-chart="month" class="showChart">Maand</a></li>
					<li id="year"><a href="#" data-chart="year" class="showChart">Jaar</a></li>
				</ul>
			</div>
			
			<div id="range" class="counter chart day week month year"></div>
			<div id="meter" class="counter chart live day week month year"></div>
			<div id="cpkwhCounter" class="counter chart day week month year"></div>
			<div id="wattCounter" class="counter chart live day week month year"></div>
			<div id="kwhCounter" class="counter chart day week month year" style="display:none;"></div>
			
			
		</div>
		<div id="container">

			<div class="chart day week month" id="datepickContainer">

				<input type="text" id="datepicker" value="<?php echo date("Y-m-d"); ?>">&nbsp;            
				<a id="previous" href="#" style="text-decoration: none;color: #000000">&lt;&lt;</a>&nbsp;&nbsp;
				<a id="next" href="#" style="text-decoration: none;color: #000000">&gt;&gt;</a>
			</div>
			<div id="history" class="chart day week month year"></div>
			<div id="live" class="chart live" style="height: 500px; min-width: 500px;"></div>
		</div>
	</body>
</html><?php 
// We quickly update the stats gathering so that if the user opens the history, he won't have to wait for these to be collected.
include('cronjob.php'); 
?>
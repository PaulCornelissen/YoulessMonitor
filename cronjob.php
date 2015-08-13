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

require_once "inc/settings.inc.php";
require_once "classes/curl.class.php";
require_once "classes/request.class.php";
require_once "classes/database.class.php";
require_once "classes/generic.class.php";

$request = 	new Request();
$db = 		new Database();
$gen = 		new Generic();
$settings = $db->getSettings();


if($settings['LastUpdate_UnixTime'] >= time() - $settings['cooldown']) 
{
	// Cooldown active
	exit(VERBOSE ? "Cooldown active.\n" : "");
}

$liveData = json_decode($request->getLiveData(), true);




// Update data table with 1 min data
$data 			= $request->getLastHour();

$row 			= explode(",", $data['val']);
$total 			= count($row);
$time 			= strtotime($data['tm']);
$receivedTime 	= $time + 3600;

if(VERBOSE) print_r($data);
if(VERBOSE) echo "Time: " . $time . " LastReceived_UnixTime: " . $settings['LastReceived_UnixTime'] . "\n";

// If the last update was <1 hour ago, we only need to do a partial update
// 
if($time <= $settings['LastReceived_UnixTime'] - 60) 
{
	$i = floor(($settings['LastReceived_UnixTime'] - $time) / 60) - 1;
}
else 
{
	$i = 0;
}


for($t=$i;$t<$total;$t++)
{
	$mtime = $time + ( $t * $data['dt'] );
	$low=$gen->IsLowKwh(date('Y-m-d H:i:00',$mtime));
	if ( $low == 0 ) 
	{
		$tariff=(float)$settings['cpkwh'];
	} 
	else 
	{
		$tariff=(float)$settings['cpkwh_low'];
	}

	$db->addMinuteData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], str_replace("\"", "",$row[$t]), $low, $tariff );
}




// Update data table with 10 min data
// Only if last update was > 1 hour ago
if($settings['LastReceived_UnixTime']<time()-3630)
{	

	$data = $request->getLast24Hours();		
	
	$row = explode(",", $data['val']);
	$total = count($row);
	$time = strtotime($data['tm']);
	
	for($t=0;$t<$total;$t++)
	{
		for($TenMinLoop=0;$TenMinLoop<10;$TenMinLoop++)
		{	
			$mtime = $time + ( $t * $data['dt'] ) + $TenMinLoop*60;
			$low=$gen->IsLowKwh(date('Y-m-d H:i:00',$mtime));
			
			if ( $low == 0 ) 
			{
				$tariff=(float)$settings['cpkwh'];
			} 
			else 
			{
				$tariff=(float)$settings['cpkwh_low'];
			}
			
			// Speel wat met de waardes om een mooi kringeltje in de grafiek te krijgen die aangeeft dat het een 10min waarde is.
			switch ($TenMinLoop):
				case 4:
					$db->add10MinuteData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round((str_replace("\"", "",$row[$t]))*1.02+2,0), $low, $tariff );
					break;
				case 5:
					$db->add10MinuteData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",$row[$t])/1.02-2,0), $low, $tariff );
					break;
				default:
					$db->add10MinuteData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",$row[$t]),0), $low, $tariff );
			endswitch;
		}
	}
}




// Update data table with 1 hour data
// Only if last update was > 1 day ago
if($settings['LastReceived_UnixTime']<time()-24*3600)
{	

	$data 	= $request->getLast7Days();		
	
	$row 	= explode(",", $data['val']);
	$total 	= count($row);
	$time 	= strtotime($data['tm']);
	
	for($t=0;$t<$total;$t++)
	{

		for($HourLoop=0;$HourLoop<60;$HourLoop++)
		{	
			$mtime 	= $time + ( $t * $data['dt'] ) + $HourLoop*60;
			$low 	= $gen->IsLowKwh(date('Y-m-d H:i:00',$mtime));
			
			if ( $low == 0 ) 
			{
			  $tariff=(float)$settings['cpkwh'];
			} 
			else 
			{
			  $tariff=(float)$settings['cpkwh_low'];
			}
			
			// Speel wat met de waardes om een mooi kringeltje in de grafiek te krijgen die aangeeft dat het een uurwaarde is.
			switch ($HourLoop):
				case 18:
					$db->add1HourData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round((str_replace("\"", "",$row[$t]))*1.02+2,0), $low, $tariff );
					break;
				case 24:
					$db->add1HourData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round((str_replace("\"", "",$row[$t]))/1.02+2,0), $low, $tariff );
					break;
				case 30:
					$db->add1HourData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",$row[$t])*1.02-2,0), $low, $tariff );
					break;
				case 36:
					$db->add1HourData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",$row[$t])/1.02-2,0), $low, $tariff );
					break;
				default:
					$db->add1HourData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",$row[$t]),0), $low, $tariff );
			endswitch;
		}
	}
}



//Update database with latest update timestamp
$db->updateSettings('LastUpdate_UnixTime', time());
$db->updateSettings('LastReceived_UnixTime', $receivedTime);



/*

// Update data table with 1 day data					//WORKING (BUT VERY SLOW DUE TO THE ALMOST 50k INSERTED(31*24*60)POINTS) | BETA FEATURE!!

// Only if last update was > 1 week ago
if($settings['LastUpdate_UnixTime']<time()-7*24*3600)
{	

	print "<br>update 1 day data<br>";

	
	$ThisMonth = date("m");
	$ThisMonth = 5;			//replace $ThisMonth (=now) by value
	
	
	// Update data table with 1 day data
	$data = $request->getThisMonth($ThisMonth);	
	
	$row = explode(", ", $data['val']);
	
	$total = count($row);
	$time = strtotime($data['tm']);

	
	print "<br>";		
	print "$total days/month";
	print "<br>";
	
	print date('Y-m-d H:i:00',$time);
	print "<br>";
	
	print $data['un'];
	print "<br>";

	print $data['dt'];
	print "<br>";
	print "<br>";		

	print_r ($row);
	print "<br>";
	print "<br>";		

	
	
	
	$ThisDay = date("d");
	//$total = $ThisDay-1;
	//$total = $ThisDay;		//replace $total (=rowcount) by value
	

	
	for($t=0;$t<$total;$t++)
	{

		
		for($DayLoop=0;$DayLoop<1440;$DayLoop++)
		{	
			$mtime = $time + ( $t * $data['dt'] ) + $DayLoop*60;
			$low=$gen->IsLowKwh(date('Y-m-d H:i:00',$mtime));
			
			if ( $low == 0 ) {
			  $tariff=(float)$settings['cpkwh'];
			} else {
			  $tariff=(float)$settings['cpkwh_low'];
			}
			
			switch ($DayLoop%60):
				case (0):
					$db->add1DayData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",str_replace(',','.',$row[$t]))/24*1000*1.02+2,0), $low, $tariff );
					break;
				case (2):
					$db->add1DayData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",str_replace(',','.',$row[$t]))/24*1000/1.02-2,0), $low, $tariff );
					break;
				case (4):
					$db->add1DayData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",str_replace(',','.',$row[$t]))/24*1000*1.02+2,0), $low, $tariff );
					break;
				case (6):
					$db->add1DayData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",str_replace(',','.',$row[$t]))/24*1000/1.02-2,0), $low, $tariff );
					break;
				case (8):
					$db->add1DayData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",str_replace(',','.',$row[$t]))/24*1000*1.02+2,0), $low, $tariff );
					break;
				case (10):
					$db->add1DayData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",str_replace(',','.',$row[$t]))/24*1000/1.02-2,0), $low, $tariff );
					break;
				case (12):
					$db->add1DayData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",str_replace(',','.',$row[$t]))/24*1000*1.02+2,0), $low, $tariff );
					break;
				case (14):
					$db->add1DayData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",str_replace(',','.',$row[$t]))/24*1000/1.02-2,0), $low, $tariff );
					break;
				case (16):
					$db->add1DayData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",str_replace(',','.',$row[$t]))/24*1000*1.02+2,0), $low, $tariff );
					break;
					default:
					$db->add1DayData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",str_replace(',','.',$row[$t]))/24*1000,0), $low, $tariff );
			endswitch;
			
		}
		
		
	}
	
}	
*/




/*

{
	
print "<br>update -1 data<br>";		

	$time = time()-(3600*24*2);
	$nu = time();
	for ($i = $time; $i < $nu ;$i = $i + 60 ) {
		$db->addMissingMinuteData( date('Y-m-d H:i:00',$i));
	}
}

$time_end = microtime_float();
$totaltime = round($time_end - $time_start,4);

print "-1: $totaltime sec<br><br>";
$time_start = microtime_float();

*/

	
?>

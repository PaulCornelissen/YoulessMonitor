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
include "inc/settings.inc.php";
include "classes/curl.class.php";
include "classes/request.class.php";	
include "classes/database.class.php";
include "classes/generic.class.php";

session_start();

$request = new Request();
$db = new Database();
$gen = new Generic();
$settings = $db->getSettings();

if((isset($_SESSION['user_id']) && $_SESSION['user_id'] != false) || NO_LOGIN)  
{

	if(isset($_GET['a']) && $_GET['a'] == 'live')
	{
		echo $request->getLiveData();
	}
	elseif(isset($_GET['a']) && ( $_GET['a'] == 'day' || $_GET['a'] == 'week' || $_GET['a'] == 'month' || $_GET['a'] == 'year'  ) && isset($_GET['date']))
	{	
		
		$sqlDate = $_GET['date'];
		$range =  $_GET['a'];
		
		
		if ( $range == 'day' ) {
			$rows = $db->getSpecificDay($sqlDate);
		} 
		elseif ( $range == 'week') {
			$week = date('W',strtotime($sqlDate));
			$year = date('Y',strtotime($sqlDate));
	
			$begin = date("Y-m-d", strtotime($year."W".$week));
			$end = date("Y-m-d", strtotime($year."W".$week)+(6*86400));		
				
			$rows = $db->getSpecificRange($begin, $end);
		}
		elseif ( $range == 'month') {

			$begin = date("Y-m-d",strtotime('first day of this month',strtotime($sqlDate))); 
			$end = date("Y-m-d",strtotime('last day of this month',strtotime($sqlDate))); 
		
			$rows = $db->getSpecificRange($begin, $end);
				
		}
		elseif ( $range == 'year') {

			$begin = date('Y-m-d',strtotime("first day of January",strtotime($sqlDate)));
			$endOfYearTimestamp = strtotime("last day of december",strtotime($sqlDate)) - 86400;
			$endOfTodayTimestamp = strtotime("+1 day",time());
			if($endOfTodayTimestamp > $endOfTodayTimestamp) 
			{
				$end = date('Y-m-d', $endOfTodayTimestamp);
			}
			else {
				$end = date('Y-m-d', $endOfYearTimestamp);
			}

			$rows = $db->getSpecificRange($begin, $end);
		}


		if(count($rows) == 0)
		{
		
			echo '{"ok": 0, "msg":"Geen data beschikbaar op deze datum", "start": "'. $sqlDate .'", "val": " 0, 0", "kwh": 0, "price": 0}';
		
		}
		else
		{
			$i=0;
			$otime=99999999999999999999;
			$leeg[] = 0;
			$prevtime = time();
			$dataStr = '';
			$it=0;

			foreach($rows as $k)
			{
				$total = substr_count($k->value, ",") + 1;
				
				if ($k->time < $otime) {
					$otime = $k->time;
				}

				$diff = floor(($k->time - $prevtime) / 60) - $total;
				if($i > 0 && $diff > 0) {
					for($j = 0; $j < $diff; $j++) {
						$dataStr .= ',0';
					}
				}

				$dataStr .= ($i!=0 ? "," : "").$k->value;

				$prevtime = $k->time;
				$i++;
			}
			
			// Output data
			$startTime = date('Y-m-d-H-i',$otime); 
			echo '{"ok": 1, "start": "'. $startTime .'", "val": "'. str_replace("\"", "", $dataStr) .'"}';	
		
		}
			
	}
	elseif(isset($_GET['a']) && $_GET['a'] == 'calculate_day' && isset($_GET['date']))
	{	
		
		$sqlDate = $_GET['date'];
		
		// Get data from specific day
		$costs = $gen->calculateDayKwhCosts($sqlDate);	
			
		// Output data
		echo '{"ok": 1, "kwh": "'. number_format($costs['kwh'], 2, ',', '') .'", "kwhLow": "'. number_format($costs['kwhLow'], 2, ',', '') .'", "price": "'. number_format($costs['price'], 2, ',', '') .'", "priceLow": "'. number_format($costs['priceLow'], 2, ',', '') .'", "priceTotal": "'. number_format($costs['priceTotal'], 2, ',', '') .'", "kwhTotal": "'. number_format($costs['kwhTotal'], 2, ',', '') .'"}';	
		
			
	}	
	elseif(isset($_GET['a']) && $_GET['a'] == 'calculate_week' && isset($_GET['date']))
	{	
		
		$sqlDate = $_GET['date'];
		
		$week = date('W',strtotime($sqlDate));
		$year = date('Y',strtotime($sqlDate));
	
		$start = date("Y-m-d", strtotime($year."W".$week));
		$end = date("Y-m-d", strtotime($year."W".$week)+(6*86400));
		
		// Calculate totals/costs
		$costs = $gen->calculateRangeKwhCosts($start, $end);
		
		// Output data
		echo '{"ok": 1, "kwh": "'. number_format($costs['kwh'], 2, ',', '') .'", "kwhLow": "'. number_format($costs['kwhLow'], 2, ',', '') .'", "price": "'. number_format($costs['price'], 2, ',', '') .'", "priceLow": "'. number_format($costs['priceLow'], 2, ',', '') .'", "priceTotal": "'. number_format($costs['priceTotal'], 2, ',', '') .'", "kwhTotal": "'. number_format($costs['kwhTotal'], 2, ',', '') .'"}';	
	}	
	elseif(isset($_GET['a']) && $_GET['a'] == 'calculate_month' && isset($_GET['date']))
	{	
		
		$sqlDate = $_GET['date'];

		$begin = date("Y-m-d",strtotime('first day of this month',strtotime($sqlDate))); 
		$end = date("Y-m-d",strtotime('last day of this month',strtotime($sqlDate))); 

		
		// Calculate totals/costs
		$costs = $gen->calculateRangeKwhCosts($begin, $end);
		
		// Output data
		echo '{"ok": 1, "kwh": "'. number_format($costs['kwh'], 2, ',', '') .'", "kwhLow": "'. number_format($costs['kwhLow'], 2, ',', '') .'", "price": "'. number_format($costs['price'], 2, ',', '') .'", "priceLow": "'. number_format($costs['priceLow'], 2, ',', '') .'", "priceTotal": "'. number_format($costs['priceTotal'], 2, ',', '') .'", "kwhTotal": "'. number_format($costs['kwhTotal'], 2, ',', '') .'"}';	
	}	
	elseif(isset($_GET['a']) && $_GET['a'] == 'calculate_year' && isset($_GET['date']))
	{	
		
		$sqlDate = $_GET['date'];
		
		$start = date('Y-m-d', strtotime("-1 year",strtotime($sqlDate)));
		$end = date('Y-m-d',strtotime("+1 day",strtotime($sqlDate)));
						
		// Calculate totals/costs
		$costs = $gen->calculateRangeKwhCosts($start, $end);
		
		// Output data
		echo '{"ok": 1, "kwh": "'. number_format($costs['kwh'], 2, ',', '') .'", "kwhLow": "'. number_format($costs['kwhLow'], 2, ',', '') .'", "price": "'. number_format($costs['price'], 2, ',', '') .'", "priceLow": "'. number_format($costs['priceLow'], 2, ',', '') .'", "priceTotal": "'. number_format($costs['priceTotal'], 2, ',', '') .'", "kwhTotal": "'. number_format($costs['kwhTotal'], 2, ',', '') .'"}';	
	}	
	elseif(isset($_GET['a']) && $_GET['a'] == 'get_meter')
	{	
		// Calculate totals/costs
		$meter  = $db->getMeterstand('0');
		$meterl = $db->getMeterstand('1');
		$islow  = $gen->IsLowKwh(date('Y-m-d H:i:00',time()));
				
		// Output data
		echo '{"ok": 1, "meter": "'. number_format($meter, 3, ',', '') .'", "meterl": "'. number_format($meterl, 3, ',', '') .'", "islow": "'. number_format($islow, 0, ',', '') .'"}';	
	}	
	elseif(isset($_GET['a']) && $_GET['a'] == 'get_islowkwh' && isset($_GET['date']))
	{	
		// Calculate totals/costs
		$islow = $gen->IswLowKwh($date);
				
		// Output data
		echo '{"ok": 1, "islow": "'. number_format($islow, 1, ',', '') .'"}';	
	}	
	elseif(isset($_GET['a']) && $_GET['a'] == 'saveSettings')
	{
		$error = '';
		$includedFields = array(
			'dualcount',
			'liveinterval',
			'livelengte',
			'cpkwh',
			'cpkwh_low'
		);
		
		foreach($_POST as $k => $v)
		{
			$$k = $v;
			if(in_array($k, $includedFields))
			{
				if(is_numeric($v)) 
				{
					$db->updateSettings($k, $v);
				}
				else 
				{
					$error .= "'" . htmlspecialchars($v) . "' is geen getal.<br>";
				}
			}
		}
		if(
			is_numeric($cpkwhlow_start_hour) && $cpkwhlow_start_hour <= 24 && $cpkwhlow_start_hour >= 0 &&
			is_numeric($cpkwhlow_end_hour) && $cpkwhlow_end_hour <= 24 && $cpkwhlow_end_hour >= 0 &&
			is_numeric($cpkwhlow_start_min) && $cpkwhlow_start_min <= 59 && $cpkwhlow_start_min >= 0 &&
			is_numeric($cpkwhlow_end_min) && $cpkwhlow_end_min <= 59 && $cpkwhlow_end_min >= 0) 
		{
			$cpkwhlow_start = $cpkwhlow_start_hour.":".$cpkwhlow_start_min;
			$cpkwhlow_end = $cpkwhlow_end_hour.":".$cpkwhlow_end_min;
			
			$db->updateSettings('cpkwhlow_start', $cpkwhlow_start);
			$db->updateSettings('cpkwhlow_end', $cpkwhlow_end);
		}
		else 
		{
			$error .= "Ongeldige tarieftijden.<br>";
		}
		
		foreach($metercal as $v)
		{
			if(is_numeric($v['count']) && ($v['islow'] == 0 || $v['islow'] == 1)) 
			{
				$db->updateMeterc($v['time'], $v['count'], $v['islow']);
			}
			else 
			{
				$error .= htmlspecialchars($v['count']) . " & " . htmlspecialchars($v['islow']) . " zijn ongeldig.<br>";
			}
		}

	
		if(isset($password) && isset($confirmpassword) && $password != "" && $confirmpassword != "")
		{
			if($password == $confirmpassword)
			{
				$db->updateLogin(sha1($password));
			}
			else 
			{
				$error .= "De ingevoerde wachtwoorden zijn niet gelijk.";
			}
		}
		
		if($error == '') 
		{
			echo '{"ok": 1, "msg":"Instellingen succesvol opgeslagen"}';	
		}
		else 
		{
			echo '{"ok": 0, "msg":"' . $error . '"}';		
		}
		
	}
	elseif(isset($_GET['a']) && $_GET['a'] == 'calculate_range' && isset($_GET['stime']) && isset($_GET['etime']))
	{	
		$stime = $_GET['stime'];
		$etime = $_GET['etime'];
		
		$costs = $gen->calculateTimeRangeKwhCosts($stime,$etime);
		
		// Output data
		echo '{"ok": 1, "kwh": "'. number_format($costs['kwh'], 2, ',', '') .'", "kwhLow": "'. number_format($costs['kwhLow'], 2, ',', '') .'", "price": "'. number_format($costs['price'], 2, ',', '') .'", "priceLow": "'. number_format($costs['priceLow'], 2, ',', '') .'", "priceTotal": "'. number_format($costs['priceTotal'], 2, ',', '') .'", "kwhTotal": "'. number_format($costs['kwhTotal'], 2, ',', '') .'"}';	
	}	
	else
	{
		echo '{"ok": 0, "msg":"Fout: Verkeerd geformuleerd verzoek!"}';
	}
}
else
{
	echo '{"ok": 0, "msg":"Fout: Login required!"}';
}
?>

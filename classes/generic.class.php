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

class Generic {

	
    /**
     * Create selector
     */
	public function selector($name, $selected, $options){

		$html = "<select name='".$name."'>\n";
		foreach($options as $k => $v) 
		{
			$html .= "<option value='" . $k . "'" . ($k==$selected?" selected":"") . ">$v</option>\n";
		}
		$html .= "</select>\n";
		
		return $html;
	}

    /**
     * Create time selector
     */
	public function timeSelector($selectedHour, $selectedMin, $prefix){
		$html = "<select name='".$prefix."_hour'>\n";
		for ($i=0;$i<24;$i++) 
		{
			$html .= "<option value='" . sprintf("%02d", $i) . "'" . ($i==$selectedHour?" selected":"") . ">$i</option>\n";
		}
		$html .= "</select>:<select name='".$prefix."_min'>\n";
		for ($i=0;$i<60;$i+=5) 
		{
			$html .= "<option value='" . sprintf("%02d", $i) . "'" . ($i==$selectedMin?" selected":"") . ">" . sprintf("%02d", $i) . "</option>\n";
		}
		$html .= "</select>";
		
		return $html;
	}
	
    /**
     * Calculate kwhs and costs for a range of days
     */	
     public function calculateRangeKwhCosts($beginDate, $endDate){
     	 
		return $this->calculateTimeRangeKwhCosts(date ("Y-m-d 00:00:00", strtotime($beginDate)),date ("Y-m-d 00:00:00", strtotime ("+1 day", strtotime($endDate))) );

     }
     
    /**
     * Calculate kwhs and costs for specific day
     */	
     public function calculateDayKwhCosts($checkDate){
     	
		return $this->calculateTimeRangeKwhCosts(date ("Y-m-d 00:00:00", strtotime($checkDate)),date ("Y-m-d 00:00:00", strtotime ("+1 day", strtotime($checkDate))) );

     }     
     
     /*
	 * Calculate kwhs and costs for specific day
     */	
     public function calculateYearKwhCosts($checkDate){
     	
		return $this->calculateTimeRangeKwhCosts(date ("Y-m-d 00:00:00", strtotime($checkDate)),date ("Y-m-d 00:00:00", strtotime ("-1 year", strtotime($checkDate))) );

     }     
	 public function calculateTimeRangeKwhCosts($beginDate, $endDate){

	      	$this->db = new Database();
			$settings = $this->db->getSettings();

			$data = array(
				'kwh' => 0,
				'kwhLow' => 0,
				'price' => 0,
				'priceLow' => 0,
				'priceTotal' => 0,
				'kwhTotal' => 0
			);

			$rows = $this->db->getSpecificTimeRange($beginDate, $endDate);
//			file_put_contents('php://stderr', print_r($rows, TRUE));
			
			foreach($rows as $k) {
				if ( $k->islow == 0 ) {
					$data['kwh'] = $k->kwh;
					$data['price'] = $k->price;
				} else {
					$data['kwhLow']  = $k->kwh;
					$data['priceLow']  = $k->price;
				}
			}
	
/*			
			
				if ( $this->isLowKwh($k->time) == 0 ) {
					$data['kwh'] = $data['kwh'] + $k->value;
				} else {
					$data['kwhLow'] = $data['kwhLow'] + $k->value;
				}


			$data['kwh'] = ($data['kwh'] /60) / 1000;
			$data['kwhLow'] = ($data['kwhLow'] /60) / 1000;
												
			$data['price'] = $data['kwh'] * (float)$settings['cpkwh'];
			$data['priceLow'] = $data['kwhLow'] * (float)$settings['cpkwh_low'];	
*/			
			
			$data['priceTotal'] = $data['price'] + $data['priceLow'];
			$data['kwhTotal'] = $data['kwh'] + $data['kwhLow'];
                      	 		
		return $data;	
		  
     }

	 
	 /**
     * Determine low/high rate
     */	
     public function isLowKwh($checkDate){
     	
     	$this->db = new Database();
     	$settings = $this->db->getSettings();
		
		$kwhLow = 0;

		if($settings['dualcount'] == 1)
		{

			$getDay = date('N', strtotime($checkDate));
			$rtime = date('Hi',strtotime($checkDate));	
			$ttime = date('Y-m-d',strtotime($checkDate)); 
			
/*			$feestdagen = $this->db->getFeestdagen();
			
			foreach($feestdagen as $k){
			
                if (strtotime($k['datum'])  == strtotime($ttime))
                {
                       $getDay = '8';
                }
			}
*/
			
			$timeStart = (int)str_replace(":","", $settings['cpkwhlow_start']);
			$timeEnd = (int)str_replace(":","", $settings['cpkwhlow_end']);
			
			
			$holiday = $this->calculateHoliday(substr($checkDate,0,10));		
			if ($getDay == '6' || $getDay == '7' || $holiday == true || $rtime >= $timeStart || $rtime < $timeEnd ){
				$kwhLow = 1;
			}
		}
			
		return $kwhLow;		  
     }     
  
   public function updateDatabase(){

    	$this->db = new Database();
		$settings = $this->db->getSettings();

		$rows = $this->db->data_m();
					
		foreach($rows as $k) {
		
		if ( $this->isLowKwh($k->time) == 0) {
			$low = FALSE;  
			$tf = (float)$settings['cpkwh'];
		} else {
			$low= TRUE;
			$tf = (float)$settings['cpkwh_low'];
		}
		
		$this->db->addMinuteData( $k->time, $k->unit, $k->delta, $k->value, $low, $tf );
			
		}
		return;	
		  
     }

    /**
	* Calculate if a specific day is a holiday
	*/	

	public function calculateHoliday($checkDate){
	
		$jaar = date('Y');
		$feestdag = array();
		$a = $jaar % 19;
		$b = intval($jaar/100);
		$c = $jaar % 100;
		$d = intval($b/4);
		$e = $b % 4;
		$g = intval((8 * $b + 13) / 25);
		$theta = intval((11 * ($b - $d - $g) - 4) / 30);
		$phi = intval((7 * $a + $theta + 6) / 11);
		$psi = (19 * $a + ($b - $d - $g) + 15 -$phi) % 29;
		$i = intval($c / 4);
		$k = $c % 4;
		$lamda = ((32 + 2 * $e) + 2 * $i - $k - $psi) % 7;
		$maand = intval((90 + ($psi + $lamda)) / 25);
		$dag = (19 + ($psi + $lamda) + $maand) % 32;

		$feestdag[] = date('Y-m-d', mktime (1,1,1,1,1,$jaar)); // Nieuwjaarsdag
		$feestdag[] = date('Y-m-d',mktime (0,0,0,$maand,$dag-2,$jaar)); // Goede Vrijdag
		$feestdag[] = date('Y-m-d',mktime (0,0,0,$maand,$dag,$jaar)); // 1e Paasdag
		$feestdag[] = date('Y-m-d',mktime (0,0,0,$maand,$dag+1,$jaar)); // 2e Paasdag

		if ($jaar < '2014'){
			$feestdag[] = date('Y-m-d',mktime (0,0,0,4,30,$jaar)); // Koninginnedag
		}
		else {
			$feestdag[] = date('Y-m-d',mktime (0,0,0,4,26,$jaar)); // Koningsdag
		}
		$feestdag[] = date('Y-m-d',mktime (0,0,0,5,5,$jaar)); // Bevrijdingsdag
		$feestdag[] = date('Y-m-d',mktime (0,0,0,$maand,$dag+39,$jaar)); // Hemelvaart
		$feestdag[] = date('Y-m-d',mktime (0,0,0,$maand,$dag+49,$jaar)); // 1e Pinksterdag
		$feestdag[] = date('Y-m-d',mktime (0,0,0,$maand,$dag+50,$jaar)); // 2e Pinksterdag
		$feestdag[] = date('Y-m-d',mktime (0,0,0,12,25,$jaar)); // 1e Kerstdag
		$feestdag[] = date('Y-m-d',mktime (0,0,0,12,26,$jaar)); // 2e Kerstdag

		return in_array($checkDate, $feestdag) ? true : false;
	}  
 
 
}

?>

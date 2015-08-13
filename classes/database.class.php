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

class Database {

    private $_db = null;

    /**
     * Constructor, makes a database connection
     */
    public function __construct() {

        try {
            $this->_db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS, array( 
      			PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
   			));
            $this->_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->_db->query('SET CHARACTER SET utf8');
        } catch (PDOException $e) {
            exit('Error while connecting to database.'.$e->getMessage());
        }
    }

    private function printErrorMessage($message) {
        echo $message . "<br>\n";
    }

    /**
     * Get login 
     */
     public function getLogin($username, $password) {
        try {
            $sth = $this->_db->prepare("SELECT id FROM " . DB_PREFIX . "users WHERE username= ? AND password= ? ");

            $sth->bindValue(1, $username, PDO::PARAM_STR);
            $sth->bindValue(2, $password, PDO::PARAM_STR);
            $sth->execute();
            $row = $sth->fetch(PDO::FETCH_OBJ);
			return $row->id;
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    }

    /**
     * Update login 
     */
     public function updateLogin($password) {
        try {
            $sth = $this->_db->prepare("UPDATE " . DB_PREFIX . "users SET password= ? WHERE username='admin'");

            $sth->bindValue(1, $password, PDO::PARAM_STR);
            $sth->execute();

			return $sth->rowCount();
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    }
    
	/**
     * Update settings
     */
    public function updateSettings($key, $value) {
        try {
			$sth = $this->_db->prepare("INSERT INTO " . DB_PREFIX . "settings (`value`,`key`) VALUES (:value, :key) ON DUPLICATE KEY UPDATE `value`=:value, `key`=:key");
			
			$sth->bindValue(':value', $value, PDO::PARAM_STR);
			$sth->bindValue(':key', $key, PDO::PARAM_STR);			
            $sth->execute();
            
			return $sth->rowCount();
       } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    }    

    public function updateMeterc($time, $count, $islow) {
        try {
			$sth = $this->_db->prepare("INSERT INTO " . DB_PREFIX . "meter (`time`,`count`,`islow`) VALUES (:time, :count, :islow) ON DUPLICATE KEY UPDATE `time`=:time, `count`=:count, `islow`=:islow");
			$sth->bindValue(':time', $time, PDO::PARAM_STR);
			$sth->bindValue(':count', $count, PDO::PARAM_STR);			
			$sth->bindValue(':islow', $islow, PDO::PARAM_STR);			
            $sth->execute();
            
			return $sth->rowCount();
       } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    }    
	
    /**
     * Get settings 
     */
     public function getSettings() {
        try {
            $sth = $this->_db->prepare("SELECT * FROM " . DB_PREFIX . "settings");
            
            $sth->setFetchMode(PDO::FETCH_ASSOC);
            $sth->execute();

            $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
            
            $settings = array();
            foreach($rows as $k => $v)
            {
            	$settings[$v['key']] = $v['value'];
            }
            
            return $settings;
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
            return false;
        }
    }    

    /**
     * Get metercalibration
     */
     public function getMetercal() {
        try {
            $sth = $this->_db->prepare("SELECT * FROM " . DB_PREFIX . "meter");
            
            $sth->setFetchMode(PDO::FETCH_ASSOC);
            $sth->execute();

            $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
            
            $settings = array();
            foreach($rows as $k => $v)
            {
			    $metercal[$v['time']] = $v['time'];
			    $metercal[$v['count']] = $v['count'];
			    $metercal[$v['islow']] = $v['islow'];
            }
            
            return $rows;
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    }    

	
    /**
	* Get Feestdagen 
     */
     public function getFeestdagen() {
        try {
            $sth = $this->_db->prepare("SELECT * FROM " . DB_PREFIX . "feestdagen");
            
            $sth->setFetchMode(PDO::FETCH_ASSOC);
            $sth->execute();

            $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
            
			return $rows;
			
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    }    

	public function getMeterstand($IsLow) {
		try {
            $sth = $this->_db->prepare("select sum(value) as stand from (
											select sum(value)/60/1000 as value
											from `" . DB_PREFIX . "data_m` a
											where IsLow = :IsLow
											and time > (
												SELECT max(time) FROM `" . DB_PREFIX . "meter` b WHERE islow = :IsLow)
											union 
											select count as value 
											from " . DB_PREFIX . "meter 
											where time=(
												select max(time) from `" . DB_PREFIX . "meter` where IsLow= :IsLow) 
											and IsLow= :IsLow) e");
											
			$sth->bindValue(':IsLow', $IsLow, PDO::PARAM_STR);

            
            $sth->setFetchMode(PDO::FETCH_ASSOC);
            $sth->execute();

            $row = $sth->fetch(PDO::FETCH_OBJ);

			return $row->stand;


		} catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
		
	}

	
	
  
	/**
	* Get specific day
	*/
    public function getSpecificDay($date) {
        try {
            $sth = $this->_db->prepare("
            SELECT
            	value, UNIX_TIMESTAMP(time) as time
            FROM 
            	" . DB_PREFIX . "data_m
            WHERE
            	DATE_FORMAT(time, '%Y-%m-%d') = ?	
            ORDER BY  
				time ASC");

			$sth->bindValue(1, $date, PDO::PARAM_STR);           			
            $sth->setFetchMode(PDO::FETCH_ASSOC);
            $sth->execute();

            $rows = $sth->fetchAll(PDO::FETCH_OBJ);
			return $rows;
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    }

	/**
	* Get specific range
	*/
    public function getSpecificRange($begin, $end) {
        try {
            $sth = $this->_db->prepare("
            SELECT
            	value, UNIX_TIMESTAMP(time) as time
            FROM 
            	" . DB_PREFIX . "data_m
            WHERE
            	DATE_FORMAT(time, '%Y-%m-%d') BETWEEN ? AND ?	
            ORDER BY  
				time ASC");

			$sth->bindValue(1, $begin, PDO::PARAM_STR);  
			$sth->bindValue(2, $end, PDO::PARAM_STR);  			         			
            $sth->setFetchMode(PDO::FETCH_ASSOC);
            $sth->execute();

            $rows = $sth->fetchAll(PDO::FETCH_OBJ);
			return $rows;
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    }  

	/**
	* Get specific timerange
	*/
    public function getSpecificTimeRange($begin, $end) {
        try {
            $sth = $this->_db->prepare("			
			SELECT 
				sum(value)/60/1000 as kwh, sum(value)/60/1000*cpKwh as price,  IsLow as islow
			FROM
				`" . DB_PREFIX . "data_m` 
            WHERE
                UNIX_TIMESTAMP(time) between ? AND ?
			GROUP 
				by IsLow");

			$sth->bindValue(1, $begin, PDO::PARAM_STR);  
			$sth->bindValue(2, $end, PDO::PARAM_STR);  			         			
            $sth->setFetchMode(PDO::FETCH_ASSOC);
            $sth->execute();

            $rows = $sth->fetchAll(PDO::FETCH_OBJ);
			return $rows;
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    }  


    
	/**
	* Get month
	*/
    public function getMonth($date) {
        try {
            $sth = $this->_db->prepare("
            SELECT
            	time,
            	GROUP_CONCAT(value) as value
            FROM 
            	" . DB_PREFIX . "data_m
            WHERE
            	DATE_FORMAT(time, '%Y-%m') = ?
            GROUP BY
            	DATE_FORMAT(time, '%d')
            ORDER BY  
				time ASC");

			$sth->bindValue(1, $date, PDO::PARAM_STR);   			         			
            $sth->setFetchMode(PDO::FETCH_ASSOC);
            $sth->execute();

            $rows = $sth->fetchAll(PDO::FETCH_OBJ);
			return $rows;
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    }    
    
	/**
	* Get kwh count
	*/
    public function getKwhCount($datetime) {
        try {
            $sth = $this->_db->prepare("
			SELECT 
				*,
				if((SIGN(timestampdiff(second, inserted,:date)) = -1),
				( ( (timestampdiff(second, inserted,:date))*(timestampdiff(second, inserted,:date)) ) / (-1*(timestampdiff(second, inserted,:date))) ),
				(timestampdiff(second, inserted,:date)) ) as TimeDif
			FROM 
				" . DB_PREFIX . "kwh_h
			WHERE 
				inserted <= :date OR inserted > :date
			ORDER BY 
				TimeDif ASC 
			LIMIT 1;");

			$sth->bindValue(':date', $datetime, PDO::PARAM_STR);           			
            $sth->setFetchMode(PDO::FETCH_ASSOC);
            $sth->execute();
            
            $row = $sth->fetch(PDO::FETCH_OBJ);
			return $row;
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    }     
	
    /**
    * Add hourly data (cronjob)
    */ 
    public function addHourlyData($time, $unit, $delta, $values) {
        try {
            $sth = $this->_db->prepare("INSERT INTO " . DB_PREFIX . "data_h (
            	time,
				unit,
				delta,
				value
            ) VALUES (
            	?,
				?,
				?,
				?
            )");

            $sth->bindValue(1, $time, PDO::PARAM_STR);
			$sth->bindValue(2, $unit, PDO::PARAM_STR);
			$sth->bindValue(3, $delta, PDO::PARAM_INT);
			$sth->bindValue(4, $values, PDO::PARAM_STR);
            $sth->execute();
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    } 	
    
   /**
    * Add minute data (cronjob)
    */ 
    public function addMinuteData($time, $unit, $delta, $values, $islow, $cpkwh) {

        try {
            $sth = $this->_db->prepare("INSERT IGNORE INTO " . DB_PREFIX . "data_m (
            	time,
				unit,
				delta,
				value,
				cpKwh,
				IsLow
            ) VALUES (
            	:time,
				:unit,
				:delta,
				:value,
				:cpkwh,
				:islow
            )");

            $sth->bindValue(':time', $time, PDO::PARAM_STR);
			$sth->bindValue(':unit', $unit, PDO::PARAM_STR);
			$sth->bindValue(':delta', $delta, PDO::PARAM_INT);
			$sth->bindValue(':value', $values, PDO::PARAM_STR);
			$sth->bindValue(':cpkwh', $cpkwh, PDO::PARAM_STR);
			$sth->bindValue(':islow', $islow, PDO::PARAM_STR);
			
            $sth->execute();
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    } 

    /**
    * Add 10-minute data (cronjob)
    */ 
    public function add10MinuteData($time, $unit, $delta, $values, $islow, $cpkwh) {
        try {
            $sth = $this->_db->prepare("INSERT IGNORE INTO " . DB_PREFIX . "data_m (
            	time,
				unit,
				delta,
				value,
				cpKwh,
				IsLow
            ) VALUES (
            	:time,
				:unit,
				:delta,
				:value,
				:cpkwh,
				:islow
            ) 
	    ON DUPLICATE KEY UPDATE IsLow = :islow");

            $sth->bindValue(':time', $time, PDO::PARAM_STR);
			$sth->bindValue(':unit', $unit, PDO::PARAM_STR);
			$sth->bindValue(':delta', $delta, PDO::PARAM_INT);
			$sth->bindValue(':value', $values, PDO::PARAM_STR);
			$sth->bindValue(':cpkwh', $cpkwh, PDO::PARAM_STR);
			$sth->bindValue(':islow', $islow, PDO::PARAM_STR);
			
            $sth->execute();
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    } 

	/**
    * Add 1 hour data (cronjob)
    */ 
	public function add1HourData($time, $unit, $delta, $values, $islow, $cpkwh) {

        try {
            $sth = $this->_db->prepare("INSERT IGNORE INTO " . DB_PREFIX . "data_m (
            	time,
				unit,
				delta,
				value,
				cpKwh,
				IsLow
            ) VALUES (
            	:time,
				:unit,
				:delta,
				:value,
				:cpkwh,
				:islow
            ) 
	    ON DUPLICATE KEY UPDATE IsLow = :islow");

            $sth->bindValue(':time', $time, PDO::PARAM_STR);
			$sth->bindValue(':unit', $unit, PDO::PARAM_STR);
			$sth->bindValue(':delta', $delta, PDO::PARAM_INT);
			$sth->bindValue(':value', $values, PDO::PARAM_STR);
			$sth->bindValue(':cpkwh', $cpkwh, PDO::PARAM_STR);
			$sth->bindValue(':islow', $islow, PDO::PARAM_STR);
			
            $sth->execute();
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    } 
	
	
    /**
    * Add 1 day data (cronjob)
    */ 
	public function add1DayData($time, $unit, $delta, $values, $islow, $cpkwh) {

        try {
            $sth = $this->_db->prepare("INSERT IGNORE INTO " . DB_PREFIX . "data_m (
            	time,
				unit,
				delta,
				value,
				cpKwh,
				IsLow
            ) VALUES (
            	:time,
				:unit,
				:delta,
				:value,
				:cpkwh,
				:islow
            ) 
	    ON DUPLICATE KEY UPDATE IsLow = :islow");

            $sth->bindValue(':time', $time, PDO::PARAM_STR);
			$sth->bindValue(':unit', $unit, PDO::PARAM_STR);
			$sth->bindValue(':delta', $delta, PDO::PARAM_INT);
			$sth->bindValue(':value', $values, PDO::PARAM_STR);
			$sth->bindValue(':cpkwh', $cpkwh, PDO::PARAM_STR);
			$sth->bindValue(':islow', $islow, PDO::PARAM_STR);
			
            $sth->execute();
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    } 	
	
	
	
    /**
    * Add hourly kwh (cronjob)
    */ 
    public function addHourlyKwh($kwh) {
        try {
            $sth = $this->_db->prepare("INSERT INTO " . DB_PREFIX . "kwh_h (
            	kwh
            ) VALUES (
				?
            )");

            $sth->bindValue(1, $kwh, PDO::PARAM_STR);
            $sth->execute();
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    }     
    


    /**
    * Add missing minute data (cronjob)
    */ 
    public function addMissingMinuteData($time) {

        try {
            $sth = $this->_db->prepare("INSERT INTO " . DB_PREFIX . "data_m (   time,
				unit,
				delta,
				value,
				cpKwh,
				IsLow
            ) select :time,
				'',
				'-1',
				'0',
				'0',
				'0'
              from dual where exists (select * from " . DB_PREFIX . "data_m where time <  :time);
	    ");

            $sth->bindValue(':time', $time, PDO::PARAM_STR);
            $sth->execute();
        } catch (PDOException $e) {
        }
    } 
	
	
	/**
	* Get specific timerange
	*/
    public function data_m() {
        try {
            $sth = $this->_db->prepare("
            SELECT
            	*
            FROM 
            	" . DB_PREFIX . "data_m
            ORDER BY  
				time ASC");

            $sth->execute();

            $rows = $sth->fetchAll(PDO::FETCH_OBJ);
			return $rows;
        } catch (PDOException $e) {
            $this->printErrorMessage($e->getMessage());
        }
    }  
}
?>

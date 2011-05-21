<?php
App::import('Sanitize');

class Trip extends AppModel {
	var $name = 'Trip';
	var $displayField = 'headsign';
	
	var $primaryKey = 'trip_id';
	var $useTable = 'trips';
	var $virtualFields = array(
		'id' => 'Trip.trip_id',
		'headsign' => 'Trip.trip_headsign',
		'short_name' => 'Trip.trip_short_name'
	);
	
	//The Associations below have been created with all possible keys, those that are not needed can be removed
	var $belongsTo = array(
		'Route' => array(
			'className' => 'Route',
			'foreignKey' => 'route_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
		'Shape' => array(
			'className' => 'Shape',
			'foreignKey' => 'shape_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
		'Service' => array(
			'className' => 'Service',
			'foreignKey' => 'service_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);

	var $hasMany = array(
		'StopTime' => array(
			'className' => 'StopTime',
			'foreignKey' => 'trip_id',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		)
	);

	// Baseline timestamp is at midnight before the current service day starts

	function _timeToTimestamp($time, $baselineTimestamp) 
	{
		$hours = (int)substr($time,0,2);
		$minutes = (int)substr($time,3,2);
		$seconds = (int)substr($time,6,2);
	
		$relativeTimestamp = 0;
		$relativeTimestamp += 3600*$hours;
		$relativeTimestamp += 60*$minutes;
		$relativeTimestamp += $seconds;
		
		return ($baselineTimestamp + $relativeTimestamp);
	}

	function _tripsInAreaByStops(&$tripsInArea, $baselineTimestamp, $trimFrom=null, $trimTo=null, $previousDay = false) 
	{
		$trips = array();
		$lastStop = null;
		$lastTripId = null;
		$skip = false;
		$oneDaySeconds = 3600*24;
		$tripsThreshold = Configure::read('Trip.previousDayTripsThreshold');
		
		foreach($tripsInArea as $stopOnTrip) {
			$stop = array();
			$tripId = $stopOnTrip['trips']['trip_id'];
			$stop["lat"] = $stopOnTrip['stops']['stop_lat'];
			$stop["lng"] = $stopOnTrip['stops']['stop_lon'];
			
			$stop["time"] = $stopOnTrip['stop_times']['departure_time_seconds'] + $baselineTimestamp;
			
			if($stop["time"] > ($oneDaySeconds - ($oneDaySeconds - $tripsThreshold)/2) && $previousDay == true) {
				//offset previous days trips
				$stop["time"] -= $oneDaySeconds;
			}
			
			if(empty($trips[$tripId])) {
				$trips[$tripId] = array();
				$trips[$tripId]["stops"] = array();
				$trips[$tripId]["route"]["short_name"] = $stopOnTrip['routes']['route_short_name'];
			}
			
			if(!empty($trimFrom) && !empty($trimTo)) {
				// only do efficient mode if trimFrom, trimTo are set, and the lastStop is set, and the current trip hasn't changed
				if(!empty($lastStop) && $lastTripId == $tripId) {
					if($stop["time"] >= $trimFrom && $lastStop["time"] < $trimFrom) {
						// If last stop was the last stop before the beginning of the time period
						array_push($trips[$tripId]["stops"],$lastStop);
					}
					
					if($stop["time"] >= $trimFrom && $stop["time"] <= $trimTo) {
						array_push($trips[$tripId]["stops"],$stop);
					}
					
					if($lastStop["time"] <= $trimTo && $stop["time"] > $trimTo) {
						// Add one stop out of bounds
						array_push($trips[$tripId]["stops"],$stop);
					}
				} else {
					if($stop["time"] >= $trimFrom) {
						array_push($trips[$tripId]["stops"],$stop);
					}
				}
			} else {
				// if $trimFrom isn't set, push all stops regardless of their time
				array_push($trips[$tripId]["stops"],$stop);
			}
			$lastStop = $stop;
			$lastTripId = $tripId;
		}
		return $trips;
	}
	
	// Returns the timestamp at midnight of the current timestamp (removes hour/seconds)
	// TODO Move to separate class
	function baselineTimestamp($timestamp) {
		$baselineTimestamp = $timestamp - 3600*((int)date("H",$timestamp)) - 60*((int)date("i",$timestamp)) - ((int)date("s",$timestamp));
		return $baselineTimestamp;
	}
	
	function cacheTrip() {
		$startDay = $this->baselineTimestamp(time()) + 86400; // generate stuff for tomorrow
		$endDay = $startDay + 86400;
		$offset = 0;
		$additionalOverlapRequest = 1401;
		$increment = 60*15; // 15 mins
		$i=0;
		// generate at half-hour increments
		do {
			$fileTarget = Configure::read('TripCache.prefix').($startDay+$offset).'.json';
			if(!file_exists($fileTarget)) {
				$i++;
				$startTimestamp = $startDay+$offset-$additionalOverlapRequest;
				if($startTimestamp < $startDay) $startTimestamp += $additionalOverlapRequest;
				$endTimestamp = $startDay+$offset+$increment+$additionalOverlapRequest;
				if($endTimestamp > $endDay) {
					$endTimestamp -= $additionalOverlapRequest+1;
				}
				
				$resultJson = json_encode($this->getTripsInAreaAtTime($startTimestamp, $endTimestamp,null,null,null,null,$additionalOverlapRequest));
				
				
				$fp = fopen($fileTarget, 'w');
				
				echo("Did ".date('Ymd H:i:s',$startDay+$offset)." Timestamp:".($startDay+$offset));
				
				fwrite($fp, $resultJson);
				fclose($fp);
			}
			$offset += $increment;
		} while($endDay > $offset+$startDay && $i < 1);
	}
	
	// Assumes timestamps are local time (California)
	function getTripsInAreaAtTime($startTimestamp, $endTimestamp, $north=NULL, $south=NULL, $east=NULL, $west=NULL, $timeBuffer = null) 
	{
		
		$ymdStart = date("Ymd",$startTimestamp);
		$ymdEnd = date("Ymd",$endTimestamp);
		$timeStart = date("H:i:s",$startTimestamp); // local times (California)
		$timeEnd = date("H:i:s",$endTimestamp);
		$timeStartSeconds = $this->_timeToTimestamp($timeStart, 0);  // local times (California)
		$timeEndSeconds = $this->_timeToTimestamp($timeEnd, 0);
		if($north == NULL || $south == NULL || $east == NULL || $west == NULL) {
			$geo = false;
		} else {
			$geo= true;
		}
		if($geo) {
			$boundingBox['north'] = Sanitize::escape($north);
			$boundingBox['east'] = Sanitize::escape($east);
			$boundingBox['west'] = Sanitize::escape($west);
			$boundingBox['south'] = Sanitize::escape($south);
		}
		
		$currentService = $this->Service->currentServiceId($ymdStart);
		
		$databasePrefix = Configure::read('Database.prefix');
		$databaseFrom = $databasePrefix."stop_times LEFT JOIN ".$databasePrefix."trips ON ".$databasePrefix."stop_times.trip_id = ".$databasePrefix."trips.trip_id ";
		$databaseFrom .= "LEFT JOIN ".$databasePrefix."stops ON ".$databasePrefix."stop_times.stop_id = ".$databasePrefix."stops.stop_id ";
		$databaseFrom .= "LEFT JOIN ".$databasePrefix."routes ON ".$databasePrefix."routes.route_id = ".$databasePrefix."trips.route_id ";
		
		// FIXME: Performance optimization: instead of comparing with departure_time, use pregenerated departure_time_seconds int for comparison
		$databaseWhere = $databasePrefix."stop_times.departure_time_seconds >= ".$timeStartSeconds." AND ".$databasePrefix."stop_times.departure_time_seconds <= ".$timeEndSeconds." AND ".$databasePrefix."trips.service_id = '".$currentService."'";
		
		if($geo) {
			$databaseLocationWhere = $databasePrefix."stops.stop_lat >= ".$boundingBox["south"]." AND ";
			$databaseLocationWhere .= $databasePrefix."stops.stop_lat <= ".$boundingBox["north"]." AND ";
			$databaseLocationWhere .= $databasePrefix."stops.stop_lon >= ".$boundingBox["west"]." AND ";
			$databaseLocationWhere .= $databasePrefix."stops.stop_lon <= ".$boundingBox["east"];
			$databaseWhere .= " AND ".$databaseLocationWhere;
		}
		
		$databaseOrderBy = $databasePrefix.'trips.trip_id, '.$databasePrefix.'stop_times.stop_sequence ASC';
		
		$selectFields = $databasePrefix."stop_times.departure_time_seconds, ".$databasePrefix."trips.trip_id, ".$databasePrefix."routes.route_short_name, ".$databasePrefix."stops.stop_lat, ".$databasePrefix."stops.stop_lon";
		
		if($timeStartSeconds < Configure::read('Trip.previousDayTripsThreshold')) {
			// early morning, check for trips overflowing from the previous day..
			$oneDaySeconds = 3600*24;
			$yesterday = date("Ymd",$startTimestamp-$oneDaySeconds);
			$yesterdaysService = $this->Service->currentServiceId($yesterday);
			$databaseWherePreviousDay = $databasePrefix."stop_times.departure_time_seconds >= ".($timeStartSeconds+$oneDaySeconds)." AND ".$databasePrefix."stop_times.departure_time_seconds <= ".($timeEndSeconds+$oneDaySeconds)." AND ".$databasePrefix."trips.service_id = '".$yesterdaysService."'";;
			$query = "SELECT ".$selectFields." FROM ".$databaseFrom." WHERE (".$databaseWhere.") OR (".$databaseWherePreviousDay.") ORDER BY ".$databaseOrderBy;
			$previousDay = true;
		} else {
			$query = "SELECT ".$selectFields." FROM ".$databaseFrom." WHERE ".$databaseWhere." ORDER BY ".$databaseOrderBy;
			$previousDay = false;
		}
		//die($query);
		$tripsInArea = $this->query($query);
		
		$baselineTimestamp = $this->baselineTimestamp($startTimestamp);
		
		if(!empty($timeBuffer)) {
			// add efficient triming if time buffer is set.
			$trimFrom = $startTimestamp+$timeBuffer;
			$trimTo = $endTimestamp-$timeBuffer;
		} else {
			$trimFrom = null;
			$trimTo = null;
		}
		
		$tripsOrganized = $this->_tripsInAreaByStops($tripsInArea,$baselineTimestamp,$trimFrom,$trimTo,$previousDay);
		return $tripsOrganized;

		if($ymdStart != $ymdEnd) {
			//need to do 2 independent queries and merge the results.. (recursively?)
			$timeEnd = "23:59:59";
			die("Sorry! The case around midnight has not been handled yet :(");
		} else {
			// If end time < 6am, do an additional request from the previous day.
			if(intval(date("H",$endTimestamp)) < 6) {
				
			}
		}
		
		/*SELECT ".TABLE_STOP_TIMES.".`departure_time`, ".TABLE_TRIPS.".`id`,".TABLE_TRIPS.".`route_id`, ".TABLE_STOPS.".`latitude`, ".TABLE_STOPS.".`longitude` 
		FROM ".TABLE_STOP_TIMES." LEFT JOIN ".TABLE_TRIPS." ON ".TABLE_STOP_TIMES.".`trip_id` = ".TABLE_TRIPS.".`id` LEFT JOIN
		" ".TABLE_STOPS." ON ".TABLE_STOP_TIMES.".`stop_id` = ".TABLE_STOPS.".`id`
		WHERE ".TABLE_STOP_TIMES.".`departure_time` >= '10:00:00' AND ".TABLE_STOP_TIMES.".`departure_time` <= '11:00:00' AND ".TABLE_TRIPS.".service_id = '".1."' AND ".TABLE_STOPS.".`latitude`>= ".$boundingBox["south"]." AND ".TABLE_STOPS.".`latitude`<= ".$boundingBox["north"]." AND ".TABLE_STOPS.".`longitude`<= ".$boundingBox["east"]." AND ".TABLE_STOPS.".`longitude`>= ".$boundingBox["west"]." 
				die( ORDER BY ".TABLE_TRIPS.".`id`, ".TABLE_STOP_TIMES.".`stop_sequence` ASC");
		*/
		//$this->query();
	}
}
?>
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
	
	var $shapeCache;
	
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
	
	/**
	* Does some shape caching
	**/
	function _getShape($id) {
		if(empty($shapeCache[$id])) {
			$shapeCache[$id] = $this->Shape->find('all',array('recursive' => -1, 'fields' => array('Shape.shape_pt_lat','Shape.shape_pt_lon'), 'order' => array('Shape.shape_pt_sequence'), 'conditions' => array('Shape.shape_id' => $id)));
		}
		return $shapeCache[$id];
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
				$trips[$tripId]["shape_id"] = $stopOnTrip['trips']['shape_id'];
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
		
		// add in shapes, interpolate timing
		foreach($trips as &$trip) {
			$shapeData = $this->_getShape($trip["shape_id"]);
			if(!empty($trip["stops"]) && !empty($shapeData)) {
				
				$trip["stops"] = $this->shapeToScheduledShape($shapeData, $trip["stops"]);
				$this->computeShapeTimes($trip["stops"]);
			}
		}
		
		return $trips;
	}
	
	// Returns the timestamp at midnight of the current timestamp (removes hour/seconds)
	// TODO Move to separate class
	function baselineTimestamp($timestamp) {
		$baselineTimestamp = $timestamp - 3600*((int)date("H",$timestamp)) - 60*((int)date("i",$timestamp)) - ((int)date("s",$timestamp));
		return $baselineTimestamp;
	}
	
	function cacheTrip($startTimeOffset = 0) {
		$startDay = $this->baselineTimestamp(time()) + 86400 + $startTimeOffset; // generate stuff for tomorrow
		$endDay = $startDay + 86400;
		$offset = 0;
		$additionalOverlapRequest = Configure::read('TripCache.extraTimeBuffer');
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
				
				$trips = $this->getTripsInAreaAtTime($startTimestamp, $endTimestamp,null,null,null,null,$additionalOverlapRequest);

				$resultJson = json_encode($trips);
				
				
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
		
		$selectFields = $databasePrefix."stop_times.departure_time_seconds, ".$databasePrefix."trips.trip_id, ".$databasePrefix."trips.shape_id, ".$databasePrefix."routes.route_short_name, ".$databasePrefix."stops.stop_lat, ".$databasePrefix."stops.stop_lon";
		
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
		echo $query;
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
	
	
	/**
	* Convert a naive shape to a shape with timing data (some points won't have timing data though, use computeShapeTimes)
	* Complexity: O(n)
	* Discussion about this method at: http://stackoverflow.com/questions/6605834/associating-nearby-points-with-a-path
	* Output format: ordered array of points
	* Point properties: 
	*	stop = true (depending on the source of the point)
	*	shape = true (depending on the source of the point)
	*	latitude
	*	longitude
	*	if is stop
	*		departure_time_seconds
	*		arrival_time_seconds
	* TODO Unit test
	**/
	function shapeToScheduledShape($shape, $trip) {
		$tripSize = count($trip);
		$shapeSize = count($shape);
		$pointsLeft = $tripSize + $shapeSize;
		// pointers point to next to process
		$stopPointer = 0;
		$shapePointer = 0;
		// current empty pointer
		$resultPointer = 0;
		
		$results = array();
		$results[$resultPointer] = array();
		
		$this->setStopResult($trip, $stopPointer, $results, $resultPointer);
		$pointsLeft--;
		
		// determine shape pointer starting position
		foreach($shape as $point) {
			if($this->distanceBetweenPoints($point["Shape"]["shape_pt_lat"], $point["Shape"]["shape_pt_lon"], $results[0]["lat"], $results[0]["lng"]) < Configure::read('ShapeStopMergingThreshold')) {
				break;
			}
			$shapePointer++;
		}

		while($tripSize > $stopPointer) {
			$lat1 = $results[$resultPointer-1]["lat"];
			$lng1 = $results[$resultPointer-1]["lng"];
			
			// try stop, then shape, see which is closer
			$shapeDistance = 1000000;
			if($shapePointer < $shapeSize) {
				$shapeLat1 = $shape[$shapePointer]["Shape"]["shape_pt_lat"];
				$shapeLng1 = $shape[$shapePointer]["Shape"]["shape_pt_lon"];
				$shapeDistance = $this->distanceBetweenPoints($lat1, $lng1, $shapeLat1, $shapeLng1);
			}
			
			$stopDistance = 1000000;
			if($stopPointer < $tripSize) {
				$stopLat1 = $trip[$stopPointer]["lat"];
				$stopLng1 = $trip[$stopPointer]["lng"];
				$stopDistance = $this->distanceBetweenPoints($lat1, $lng1, $stopLat1, $stopLng1);
			}
			
			$results[$resultPointer] = array();
			
			if($shapeDistance < $stopDistance) {
				$this->setShapeResult($shape, $shapePointer, $results, $resultPointer);
			} else {
				$this->setStopResult($trip, $stopPointer, $results, $resultPointer);
			}

			$pointsLeft--;
		}
		
		return $results;
	}
	
	/**
	* Add time to all points by interpolating between stop points
	* Assumes the first point is a stop with time
	* Complexity: O(n)
	**/
	function computeShapeTimes(&$shape) {
		$shapeSize = count($shape);	
		$currentIndex = 0;
		$distanceBetweenStops = 0;
		$lastStopTime = 0;
		$previousPoint = null;
		$lastStopDepartureTime = 0;
		$nextStopArrivalTime = 0;
		$distanceFromLastStop = 0;
		foreach($shape as $point) {
			if(!empty($point["stop"])) {
				$lastStopDepartureTime = $point["time"];
				$distanceBetweenStops = $this->computeDistanceBetweenStops($shape, $currentIndex, $nextStopArrivalTime);
				$distanceFromLastStop = 0;
				
				// if no more stops
				if($distanceBetweenStops == 0 && $currentIndex > $shapeSize) break;
			} else {
				$distanceFromLastStop += $this->distanceBetweenPoints($previousPoint["lat"], $previousPoint["lng"], $point["lat"], $point["lng"]);
				$time = $nextStopArrivalTime - $lastStopDepartureTime;
				
				if($time == 0) break;
				if($distanceBetweenStops != 0) {
					$shape[$currentIndex]["time"] = $shape[$currentIndex]["time"] = $lastStopDepartureTime + round($time / $distanceBetweenStops * $distanceFromLastStop);
				} else {
					$shape[$currentIndex]["time"] = $shape[$currentIndex - 1]["time"];
				}
			}
			$previousPoint = $point;
			$currentIndex++;
		}
	}
	
	function computeDistanceBetweenStops(&$shape, $startingStopIndex, &$stopArrivalTime) {
		$i = $startingStopIndex + 1;
		$totalDistance = 0;
		while(empty($shape[$i]["stop"]) && !empty($shape[$i])) {
			$totalDistance += $this->distanceBetweenPoints($shape[$i-1]["lat"], $shape[$i-1]["lng"], $shape[$i]["lat"], $shape[$i]["lng"]);
			$i++;
		}
		
		// Add distance between last point and stop
		if(!empty($shape[$i])) {
			$totalDistance += $this->distanceBetweenPoints($shape[$i-1]["lat"], $shape[$i-1]["lng"], $shape[$i]["lat"], $shape[$i]["lng"]);
		}
		
		if(!empty($shape[$i]["stop"]))
			$stopArrivalTime = $shape[$i]["time"];
		
		return $totalDistance;
	}

	function setShapeResult(&$shape, &$shapePointer, &$results, &$resultPointer) {
		$results[$resultPointer]["shape"] = true;
		$results[$resultPointer]["lat"] = $shape[$shapePointer]["Shape"]["shape_pt_lat"];
		$results[$resultPointer]["lng"] = $shape[$shapePointer]["Shape"]["shape_pt_lon"];
		$shapePointer++;
		$resultPointer++;
	}
	
	function setStopResult(&$stops, &$stopPointer, &$results, &$resultPointer) {
		$results[$resultPointer]["stop"] = true;
		$results[$resultPointer]["lat"] = $stops[$stopPointer]["lat"];
		$results[$resultPointer]["lng"] = $stops[$stopPointer]["lng"];
		$results[$resultPointer]["time"] = $stops[$stopPointer]["time"];
		//$results[$resultPointer]["arrival_time_seconds"] = $stops[$stopPointer]["arrival_time_seconds"];
		$stopPointer++;
		$resultPointer++;
	}
	
	/**
	* Distance in meters between two points in lat/lng coordinates
	* Complexity: O(1)
	* TODO Accuracy unit test
	**/
	function distanceBetweenPoints($lat1, $lng1, $lat2, $lng2) {
		if($lat1 == $lat2 && $lng1 == $lng2) {
			return 0;
		}
		$distance =  111189.57696*rad2deg(acos(sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($lng1-$lng2))));
		return $distance;
	}
}
?>

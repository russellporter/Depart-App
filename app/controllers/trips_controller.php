<?php
class TripsController extends AppController {

	var $name = 'Trips';
	var $components = array('RequestHandler');

	function inProgress($offset = 0) 
	{
		$timestamp = time()+$offset;
		
		$baselineTimestamp = $this->Trip->baselineTimestamp($timestamp);
		// get start time (every 15 minutes)
		$startFileTime = $baselineTimestamp + 3600*((int)date("H",$timestamp)) + 15*60*floor(((int)date("i",$timestamp))/(int)15);			
		
		$fileTarget = Configure::read('TripCache.prefix').($startFileTime).'.json';
		$webTarget = Configure::read('TripCache.webPrefix').($startFileTime).'.json';
		if(file_exists($fileTarget)) {	
	$trips = file_get_contents($fileTarget);
$this->set('trips', $trips);
			//$this->redirect($webTarget);
    		/*$this->view = 'Media';
			$params = array(
				'id' => ($startFileTime).'.json',
				'name' => 'inProgress.json',
				'extension' => 'json',
				'mimeType' => array('json' => 'application/json'),
				'path' => Configure::read('TripCache.prefix')
			);
  			$this->set($params);*/

		} else {
			if(empty($startTime) || empty($duration)) {
				$startTime = $timestamp-1000;
				$duration = 2500;
			}
			if(empty($north)) {
				$north = $south = $west = $east = 0;
			}
			$trips = $this->Trip->getTripsInAreaAtTime($startTime, $startTime+$duration, $north, $south, $east, $west);
			$jsonEncodedTrips = json_encode($trips);
			
			$this->set('trips', $jsonEncodedTrips);
		}
	}
	
	function view($id,$timeShift = 0)
	{
		$trip = $this->Trip->find('first',array('conditions' => array('Trip.id' => $id),'contain'=>array('StopTime','StopTime.Stop','Route.short_name','Route.name')));
		$shapeData = $this->Trip->Shape->findById($trip["Trip"]["shape_id"]);
		$shape = array();

		// Debug output
		if(Configure::read('debug') >= 1) {
			$this->set('debug',true);
			
			// Convert to scheduled shape for debugging algorithm
			foreach($shapeData as $point) {
				$convertedPoint = array("Shape" => $point["ShapePoint"]);
				array_push($shape, $convertedPoint);
			}

			$stops = array();
			foreach($trip["StopTime"] as $stop) {
				$pointData["lat"] = $stop["Stop"]["stop_lat"];
				$pointData["lng"] = $stop["Stop"]["stop_lon"];
				$pointData["time"] = $stop["departure_time_seconds"];
				array_push($stops, $pointData);
			}
			$results = $this->Trip->shapeToScheduledShape($shape, $stops);
			$this->Trip->computeShapeTimes($results);

			$shapeData = array();
			$i=0;
			foreach($results as $result) {
				$convertedBack = null;
				$convertedBack["ShapePoint"]["shape_pt_lat"] = $result["lat"];
				$convertedBack["ShapePoint"]["shape_pt_lon"] = $result["lng"];
				if(!empty($result["time"])) {
					$convertedBack["ShapePoint"]["computed_time"] = $result["time"];
				}
				if(!empty($result["stop"])) {
					$convertedBack["ShapePoint"]["type"] = "stop";
				}
				if(!empty($result["shape"])) {
					$convertedBack["ShapePoint"]["type"] = "shape";
				}
				
				$convertedBack["ShapePoint"]["shape_pt_sequence"] = $i;

				array_push($shapeData, $convertedBack);
				$i++;
			}
		}
		
		foreach($shapeData as $point) 
		{
			$shapePoint = array();
			$trip["Shape"][$point["ShapePoint"]["shape_pt_sequence"]] = $point["ShapePoint"];
		}
		$this->set('trip',$trip);
		$this->set('time_shift',$timeShift);
		if ($this->RequestHandler->isAjax()) {
			$this->layout = null;
		}
	}
	
	function cacheTrip($reload = false, $yesterday = false) {
		$timeStart = microtime(true);
		if($reload) {
			echo '<head><meta http-equiv="REFRESH" content="0;url=/Trips/cacheTrip/true/'.$yesterday.'"></head>';
		}
		if($yesterday) {
			$dayOffset = -86400;
		} else {
			$dayOffset = 0;
		}
		$this->Trip->cacheTrip($dayOffset);
		$time = microtime(true) - $timeStart;
		echo " Time $time";
	}
}
?>

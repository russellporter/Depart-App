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
				$startTime = time()-1000;
				$duration = 2500;
			}
			$trips = $this->Trip->getTripsInAreaAtTime($startTime, $startTime+$duration, $north, $south, $east, $west);
			$jsonEncodedTrips = json_encode($trips);
			
			$this->set('trips', $jsonEncodedTrips);
		}
	}
	
	function view($id,$timeShift = 0)
	{
		$trip = $this->Trip->find('first',array('conditions' => array('Trip.id' => $id),'contain'=>array('StopTime','Shape','StopTime.Stop','Route.short_name','Route.name')));
		
		$this->set('trip',$trip);
		$this->set('time_shift',$timeShift);
		if ($this->RequestHandler->isAjax()) {
			$this->layout = null;
		}
	}
	
	function cacheTrip($reload = false) {
		$timeStart = microtime(true);
		if($reload) {
			echo '<head><meta http-equiv="REFRESH" content="30;url=http://www.departapp.com/Trips/cacheTrip/true"></head>';
		}
		$this->Trip->cacheTrip();
		$time = microtime(true) - $timeStart;
		echo " Time $time";
		
	}
}
?>
<?php
class StopsController extends AppController {

	var $name = 'Stops';
	var $components = array('RequestHandler');

	function view($id) {
		$stop = $this->Stop->find('first',array('conditions' => array('Stop.id' => $id)));
		$this->set('stop',$stop);
	}
	
	function viewByCode($code) {
		$stop = $this->Stop->find('first',array('conditions' => array('Stop.code' => $code)));
		$this->set('stop',$stop);
	}
	
	function upcomingDepartures($id, $time=NULL) {
		if(empty($time)) {
			// set time to now
			$time = time();
		}
		$departures = $this->Stop->findUpcomingDepartures($id, $time);
		
		$this->set('departures',$departures);
	}
}
?>
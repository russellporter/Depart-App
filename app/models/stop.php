<?php
class Stop extends AppModel {
	var $name = 'Stop';
	var $displayField = 'name';
	//The Associations below have been created with all possible keys, those that are not needed can be removed
	var $belongsTo = array(
		'Zone' => array(
			'className' => 'Zone',
			'foreignKey' => 'zone_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);
	
	var $actsAs = array('Containable');
	
	var $primaryKey = 'stop_id';
	var $useTable = 'stops';
	
	var $virtualFields = array(
		'id' => 'Stop.stop_id',
		'code' => 'Stop.stop_code',
		'name' => 'Stop.stop_name',
		'description' => 'Stop.stop_desc',
		'latitude' => 'Stop.stop_lat',
		'longitude' => 'Stop.stop_lon',
		'url' => 'Stop.stop_url',
		
	);

	var $hasMany = array(
		'StopTime' => array(
			'className' => 'StopTime',
			'foreignKey' => 'stop_id',
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
	
	function findUpcomingDepartures($id, $time, $timeDuration = 1800) {
		// load time library
		App::import('Vendor', 'time_management');
		
		$currentService = $this->StopTime->Trip->Service->currentServiceId(TimeManagement::timestampToDate($time));
		$startTimePastMidnight = $time - TimeManagement::baselineTimestamp($time);
		$endTimePastMidnight = $startTimePastMidnight + $timeDuration; 
		$conditions = array("StopTime.stop_id"=>$id,"StopTime.departure_time_seconds >="=>$startTimePastMidnight,"StopTime.departure_time_seconds <="=>$endTimePastMidnight,'Trip.service_id'=>$currentService);
		$upcomingStopTimes = $this->StopTime->find('all',array('conditions'=>$conditions,'contain'=>array('Trip'),'order'=>array('StopTime.departure_time_seconds ASC')));
		return $upcomingStopTimes;
	}
}
?>
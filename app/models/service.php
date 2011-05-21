<?php
class Service extends AppModel {
	var $name = 'Service';
	var $displayField = 'start_date';
	//The Associations below have been created with all possible keys, those that are not needed can be removed
	var $primaryKey = 'service_id';
	var $useTable = 'calendar';
	
	var $virtualFields = array(
		'id' => 'Service.service_id',
	);
	
	var $hasMany = array(
		'ServiceException' => array(
			'className' => 'ServiceException',
			'foreignKey' => 'service_id',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		),
		'Trip' => array(
			'className' => 'Trip',
			'foreignKey' => 'service_id',
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

	function currentServiceId($date)
	{	
		$dayOfWeek = $this->dateToDayOfTheWeek($date);
		
		$conditions = array('Service.start_date <='=>$date, 'Service.end_date >='=>$date, 'Service.'.$dayOfWeek => 1);
		$currentService = $this->find('first',array('conditions'=>$conditions,'recursive'=>0));
		$serviceID = $currentService['Service']['id'];
		
		// Check for an exception in service
		$exceptionConditions = array('date'=>$date,'exception_type'=>1);
		$currentServiceException = $this->ServiceException->find('first',array('conditions'=>$exceptionConditions));
		if(!empty($currentServiceException)) {
			$serviceID = $currentServiceException["Service"]["id"];
		}
		return $serviceID;
	}
	
	// Converts a date of form: 20111231 to the corresponding date of the week
	function dateToDayOfTheWeek($date) 
	{
		$year = substr($date,0,4);
		$month = substr($date,4,2);
		$day = substr($date,6,2);
		
		$timestamp = strtotime($year."-".$month."-".$day);
		
		$day = strtolower(date('l',$timestamp));
		return $day;
	}
	/* Legacy code below
	
	function GetCurrentService() {
		$time = getTime('ymd');
		$dayOfWeek = getTime('day');
		$db = Database::obtain();
		$query = "SELECT * FROM ".TABLE_CALENDAR." WHERE start_date <= $time AND end_date >= $time AND $dayOfWeek = 1";
		$calendarResult = $db->query_first($query);
		$serviceID = $calendarResult["service_id"];
		//if the day is an exception, override service ID for time period
		$queryException = "SELECT * FROM ".TABLE_CALENDAR_DATES." WHERE date=$time AND exception_type=1";
		$calendarExceptionResult = $db->query_first($queryException);
		if(!empty($calendarExceptionResult["service_id"])) $serviceID = $calendarExceptionResult["service_id"];
		return $serviceID;
	}
	function getTime($type = '',$offset=0) {
		$time = time()+TIME_OFFSET+$offset;
		if($type === "ymd") {
			return(date('Ymd',$time));
		} elseif($type === "hms") {
			return(date('H:i:s',$time));
		} elseif($type === "day") {
			return(date('l',$time));
		} else return($time);
	}*/
	
	
}
?>
<?php
class StopTime extends AppModel {
	var $name = 'StopTime';
	var $displayField = 'departure_time';
	//The Associations below have been created with all possible keys, those that are not needed can be removed

	var $belongsTo = array(
		'Trip' => array(
			'className' => 'Trip',
			'foreignKey' => 'trip_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
		'Stop' => array(
			'className' => 'Stop',
			'foreignKey' => 'stop_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);
}
?>
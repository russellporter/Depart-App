<?php
class ServiceException extends AppModel {
	var $name = 'ServiceException';
	var $displayField = 'date';
	//The Associations below have been created with all possible keys, those that are not needed can be removed
	
	var $useTable = 'calendar_dates';
	
	var $belongsTo = array(
		'Service' => array(
			'className' => 'Service',
			'foreignKey' => 'service_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);
}
?>
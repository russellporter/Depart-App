<?php
class Zone extends AppModel {
	var $name = 'Zone';
	//The Associations below have been created with all possible keys, those that are not needed can be removed
	var $hasMany = array(
		'Stop' => array(
			'className' => 'Stop',
			'foreignKey' => 'zone_id',
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

}
?>
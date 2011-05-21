<?php
class Agency extends AppModel {
	var $name = 'Agency';
	var $displayField = 'name';
	var $primaryKey = 'agency_id';
	var $validate = array(
		'url' => array(
			'url' => array(
				'rule' => array('url'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
		'language' => array(
			'alphanumeric' => array(
				'rule' => array('alphanumeric'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
	);
	//The Associations below have been created with all possible keys, those that are not needed can be removed
	var $useTable = 'agency';
	
	var $virtualFields = array(
		'id' => 'Agency.agency_id',
		'id_code' => 'Agency.agency_id',
		'name' => 'Agency.agency_name',
		'url' => 'Agency.agency_url',
		'timezone' => 'Agency.agency_timezone',
		'language' => 'Agency.agency_lang'
	);
	var $hasMany = array(
		'Route' => array(
			'className' => 'Route',
			'foreignKey' => 'agency_id',
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

	function getUTCOffset($agencyId) {
		// FIXME More general for other timezones
		$agency = $this->find('first',array('conditions' => array('Agency.id' => $agencyId),'recursive'=>0));
		if($agency["Agency"]["timezone"] == "America/Vancouver") {
			return -25200;
		} else {
			die("Need to add more to getUTCOffset in the Agency model.");
		}
	}
}
?>
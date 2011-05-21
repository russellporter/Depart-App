<?php
class Shape extends AppModel {
	var $name = 'Shape';
	//The Associations below have been created with all possible keys, those that are not needed can be removed
	var $useTable = false;
	var $hasMany = array(
		'ShapePoint' => array(
			'className' => 'ShapePoint',
			'foreignKey' => 'shape_id',
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
			'foreignKey' => 'shape_id',
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

	function findById($id) {
		return $this->ShapePoint->find('all',array('conditions'=>array('ShapePoint.shape_id'=>$id)));
	}
}
?>
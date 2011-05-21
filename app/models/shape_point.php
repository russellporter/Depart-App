<?php
class ShapePoint extends AppModel {
	var $name = 'ShapePoint';
	//The Associations below have been created with all possible keys, those that are not needed can be removed
	var $useTable = 'shapes';
	
	var $virtualFields = array(
		'point_latitude' => 'ShapePoint.shape_pt_lat',
		'point_longitude' => 'ShapePoint.shape_pt_lon',
		'point_sequence' => 'ShapePoint.shape_pt_sequence',
		'distance_traveled' => 'ShapePoint.shape_dist_traveled'
	);
	
	var $belongsTo = array(
		'Shape' => array(
			'className' => 'Shape',
			'foreignKey' => 'shape_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);
}
?>
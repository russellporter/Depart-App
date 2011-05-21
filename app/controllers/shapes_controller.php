<?php
class ShapesController extends AppController {

	var $name = 'Shapes';

	function view($id) {
		$this->set('shapePoints',$this->Shape->findById($id));
	}
}
?>
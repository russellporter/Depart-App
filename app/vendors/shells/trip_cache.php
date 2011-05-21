<?php 
class TripCacheShell extends Shell {
	var $uses = array('Trip');
	/*
	/files/cache/$serviceID_$startTime
	
	where $startTime is first 2 digits are hours, last 2 digits are minutes
	*/
	function main() {
		$this->Trip->cacheTrip();
		
	}
	
}
?>



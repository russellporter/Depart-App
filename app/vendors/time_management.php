<?php 
class TimeManagement {
	/*
		All assumes you are in PHPs local time, support for other timezones needs to be added
	*/
	/*
		Return timestamp corresponding to the local midnight time before the given timestamp
	*/
	function baselineTimestamp($timestamp) {
		$baselineTimestamp = $timestamp - 3600*((int)date("H",$timestamp)) - 60*((int)date("i",$timestamp)) - ((int)date("s",$timestamp));
		return $baselineTimestamp;
	}
	
	/*
		Adds time string of 24hr format hh:mm:ss to the provided midnight timestamp returning the timestamp
	*/
	function timeToTimestamp($time, $baselineTimestamp) 
	{
		$hours = (int)substr($time,0,2);
		$minutes = (int)substr($time,3,2);
		$seconds = (int)substr($time,6,2);
	
		$relativeTimestamp = 0;
		$relativeTimestamp += 3600*$hours;
		$relativeTimestamp += 60*$minutes;
		$relativeTimestamp += $seconds;
		
		return ($baselineTimestamp + $relativeTimestamp);
	}
	
	/*
		Date of format YYYYMMDD converted to string saying the day of the week
	*/
	function dateToDayOfTheWeek($date) 
	{
		$year = substr($date,0,4);
		$month = substr($date,4,2);
		$day = substr($date,6,2);
		
		$timestamp = strtotime($year."-".$month."-".$day);
		
		$day = strtolower(date('l',$timestamp));
		return $day;
	}
	
	function timestampToDate($timestamp) {
		return date('Ymd',$timestamp);
	}
}
?>
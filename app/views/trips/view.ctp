<?php
// necessary for playbook webworks crap as of Mar 20th 2011
$this->layout=null;
// better upper case of first letter in word function
function ucname($string) {
    $string =ucwords(strtolower($string));
	
    foreach (array('-', '/', '\\') as $delimiter) {
      if (strpos($string, $delimiter)!==false) {
        $string =implode($delimiter, array_map('ucfirst', explode($delimiter, $string)));
      }
    }
    return $string;
}

if(empty($time_shift)) {
	$time_shift = 0;
}

function coveredStop($departureTime,$timeShift) {
	$baselineTimestamp = time()+$timeShift;
	$baselineTimestamp = $baselineTimestamp - 3600*date('G',$baselineTimestamp) - 60*date('i',$baselineTimestamp) - date('s',$baselineTimestamp);
	if($departureTime <= (time()+$timeShift-$baselineTimestamp)) {
		return true;
	} else return false;
}

function pad($num, $size) {
    $s = $num."";
    while (strlen($s) < $size) $s = "0".$s;
    return $s;
}
?>
<h1><?= $trip["Route"]["short_name"];?></h1>
<h2><?= ucname($trip["Route"]["name"]);?></h2>
<h3>Schedule</h3>
<p><?php 
foreach($trip['StopTime'] as $stop) { 
if($stop['departure_time_seconds'] >= 24*3600) {
	$stop['departure_time_seconds'] -= 24*3600;
}
$hours = (int)($stop['departure_time_seconds']/3600);
$minutes = (int)($stop['departure_time_seconds']/60 - $hours*60);
$seconds = (int)($stop['departure_time_seconds'] - $hours*3600 - $minutes*60);
$stop['departure_time'] = $hours.':'.pad($minutes,2).':'.pad($seconds,2);
?>
<? if(!coveredStop($stop['departure_time_seconds'],$time_shift)) { ?><b><? } ?><?= $stop['departure_time']; ?> - <?= ucname($stop['Stop']['description']); ?><? if(!coveredStop($stop['departure_time_seconds'],$time_shift)) { ?></b><? } ?><br/>
<? } ?></p>
<input id="showStreetView" onclick="javascript:streetViewOpened=true;" type="submit" value="Street View (alpha)">

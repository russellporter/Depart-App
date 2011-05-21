<?php
$apiKey = Configure::read('CloudMade.apiKey');

$i = 0;
$transit_points = '';
$arraySize = count($shapePoints);
foreach($shapePoints as $point) {
	$transit_points .= $point["ShapePoint"]["point_latitude"].",".$point["ShapePoint"]["point_longitude"].",";
}
$transit_points = substr($transit_points,0,-1);
$requestUrl = "http://routes.cloudmade.com/".$apiKey."/api/0.3/".$transit_points."/car/shortest.js?lang=en&units=km";

App::import('Core', 'HttpSocket');
$HttpSocket = new HttpSocket();
$results = $HttpSocket->get($requestUrl);
echo(stripslashes($results)); ?>





<?
$routingData = json_decode(stripslashes($results),true);
//echo($routingData->version);
var_dump($routingData);
?>
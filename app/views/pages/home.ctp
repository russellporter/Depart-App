<?php 
// check if we are at transit.russellporter.com, redirect if necessary


echo $this->set('title_for_layout','Depart');
echo $this->Html->script('transit',array('inline'=>false)); ?> 

<div id="map_canvas" style="width:100%; height:100%"></div>
<a href="https://github.com/russellporter/Depart-App"><img style="position: absolute; top: 0; left: 0; border: 0; z-index:3;" src="https://d3nwyuy0nl342s.cloudfront.net/img/edc6dae7a1079163caf7f17c60495bbb6d027c93/687474703a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f6c6566745f677265656e5f3030373230302e706e67" alt="Fork me on GitHub"></a>
<div id="streetView"></div>
<div id="sidebar">
	
	<div id="tripInfo">
		<br/>
		<h3>Depart App</h3>
		<p>Depart App shows you the real-time positions of all buses/skytrains/seabuses/WCE vehicles in Greater Vancouver! Click on a vehicle to see its route and schedule.</p>
	</div>
	<div id="timeWarping">
	<h4>Time Warp!</h4>
	<p>Use the slider below to shift up to 24 hours forward or backwards in time.</p>
	<input id="timeShift" name="timeShift" type="range" min="-288" max="288" value="0">
	<p>Current Time: <span id="currentTimeDisplay"></span></p>
	<input id="timeShiftGo" type="submit" value="Warp" disabled="true">
	
	<? /*
	<p>Speed up time</p>
	<input id="speedUpTime" type="submit" value="Speed Up">
	<input id="slowDownTime" type="submit" value="Slow down" disabled="true">
	*/ ?>
	</div>
	<div id="legal">
	<p>Route and arrival data used in this product or service is provided by permission of TransLink.  TransLink assumes no responsibility for the accuracy or currency of the Data used in this product or service.</p>
	</div>
	<br/>
	<div id="openstreetmapAttribution">
	<p>Mapnik/Transport layers &copy;<a href=\"http://www.openstreetmap.org/\">OpenStreetMap</a>
				and contributors under <a
				href="http://creativecommons.org/licenses/by-sa/2.0/"
				>CC-bySA</a> license.</p>
				
				<br/>
	<p><a href="http://www.gravitystorm.co.uk/">Transport layer created and hosted by Andy Alan (OpenCycleMap)</a>
				</p>
				
	</div>
</div>

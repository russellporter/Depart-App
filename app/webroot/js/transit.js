var busData = {};
var currentPosition = {};
var timeZoneOffset=0;
var map;
var markersArray = [];
var readyToShow = 0;
var lastClickedMarker = 0;
var currentTime = 0;
var timeServerUpdate = 0;
var lastTripPath;
var urlPrefix = "http://www.departapp.com/";
var timeShift = 0;
var streetViewOpened = false;
var panorama;
var updateStreetViewThreshold = 0;
var timeRate = 1;
var disableTimeSync = false;
var directionDisplay;
var directionsService = new google.maps.DirectionsService();

try {
	if(blackberry.system.hasDataCoverage()) {
		// ready to go.
	} else alert("Please connect to the internet to use this app.");
}  catch(err) {
	
}

$(document).ready(function(){
	// preload graphics
	$(['/img/skytrain.png','/img/skytrain_selected.png','/img/seabus.png','/img/seabus_selected.png','/img/bus.png','/img/bus_selected.png']).preload();
	// DEPRECIATED fix PNG graphics just for IE6 
	$(document).pngFix(); 
	// get the approximate current time
	getCurrentTime();
	// load the google map
	initializeGoogleMap();
	
	// hide time warping if html5 range isn't supported.. this doesn't work on iOS as of April 2011 since iOS leaves it as range.
	if(!checkForHtml5Range()){
		$('#timeWarping').hide();
	}
	
	// if user wants to shift their time..
	$('#timeShiftGo').click(function() {
		// remove old time shift value..
		currentTime -= timeShift;
		// add new time shift value
		timeShift = getTimeShiftInput();
		currentTime += timeShift;
		reloadData();
		$('#timeShiftGo').attr("disabled", true);
	});
	
	// speed up/slow down code (currently disabled because of bugginess)
	$('#speedUpTime').click(function() {
		timeRate++;
		disableTimeSync = true;
		$('#slowDownTime').attr("disabled", false);
	});
	$('#slowDownTime').click(function() {
		if(timeRate > 1) {
			timeRate--;
			$('#slowDownTime').attr("disabled", false);
		} else {
			$('#slowDownTime').attr("disabled", true);
		}
	});
		
	$('#timeShift').change(function() {
		displayTime = currentTime - timeShift;
		displayTime += getTimeShiftInput();
		updateTimeDisplay(displayTime);
		$('#timeShiftGo').attr("disabled", false);
	});
});

function getTimeShiftInput() {
	return 300*$('#timeShift').val();
}

$(window).unload( function () { 
	// save current state (HTML5)
	localStorage["latitude"] = map.getCenter().lat();
	localStorage["longitude"] = map.getCenter().lng();
	localStorage["zoom"] = map.getZoom();
});


$.fn.preload = function() {
    this.each(function(){
        $('<img/>')[0].src = this;
    });
}

function pad(num, size) {
    var s = num+"";
    while (s.length < size) s = "0" + s;
    return s;
}

function updateTimeDisplay(timestamp) {
	date = new Date(timestamp*1000);
	hours = date.getHours();
	minutes = date.getMinutes();
	seconds = date.getSeconds();
	
	$('#currentTimeDisplay').html(hours+":"+pad(minutes,2)+":"+pad(seconds,2));
}

function getCurrentTime() {
	$.get(urlPrefix+'pages/currentTime', { } ,function(data) {
		currentTime = parseInt(data)+timeShift;
		currentTiming = setInterval("updateTime()",'1000');
	});
}

function updateTime() {
	i = timeRate;
	//if(getTimeShiftInput() != timeShift) {
		updateTimeDisplay(currentTime+getTimeShiftInput()-timeShift);
	//} else updateTimeDisplay(currentTime);
	// iterative code so if the display rate is > 1s it will still know when to refresh the data. Not working right now.
	do {
		currentTime++;
		
		date = new Date(currentTime*1000);
		hours = date.getHours();
		minutes = date.getMinutes();
		seconds = date.getSeconds();
		
		if(minutes%15 == 0 && seconds == 0) {
			readyToShow = 0;
			reloadData();
		}
		i--;
	} while(i > 0);
	
	timeServerUpdate++;
	if(timeServerUpdate == 60 && !disableTimeSync) {
		timeServerUpdate = 0;
		$.get(urlPrefix+'pages/currentTime', {language: "php", version: 5} ,function(data) {
			currentTime = parseInt(data)+timeShift;
		});
	}
}

function initializeGoogleMap() 
{
	var transportmap = new google.maps.ImageMapType({
		getTileUrl: function(ll, z) {
		  var X = ll.x % (1 << z);
		  if(Math.random() > 0.5) {
		  	if(Math.random() > 0.5) {
		  		return "http://c.tile2.opencyclemap.org/transport/" + z + "/" + X + "/" + ll.y + ".png";
		  	} else {
		  		return "http://b.tile2.opencyclemap.org/transport/" + z + "/" + X + "/" + ll.y + ".png";
		  	}
		  } else {
		  	return "http://a.tile2.opencyclemap.org/transport/" + z + "/" + X + "/" + ll.y + ".png";
		  }
		  
		},
		tileSize: new google.maps.Size(256, 256),
		isPng: true,
		maxZoom: 18,
		name: "Transport",
		alt: "Transport Map"
	});
	
	var osmmapnik = new google.maps.ImageMapType({
		getTileUrl: function(ll, z) {
		  var X = ll.x % (1 << z);
		  if(Math.random() > 0.5) {
		  	if(Math.random() > 0.5) {
		  		return "http://a.tile.openstreetmap.org/" + z + "/" + X + "/" + ll.y + ".png";
		  	} else {
		  		return "http://b.tile.openstreetmap.org/" + z + "/" + X + "/" + ll.y + ".png";
		  	}
		  } else {
		  	return "http://a.osm.virtualearth.net/" + z + "/" + X + "/" + ll.y + ".png";
		  }
		  
		},
		tileSize: new google.maps.Size(256, 256),
		isPng: true,
		maxZoom: 18,
		name: "Mapnik",
		alt: "OpenStreetMap Mapnik"
	});
	
	directionsDisplay = new google.maps.DirectionsRenderer();
	var latlng = new google.maps.LatLng(49.2, -122.9);
	var zoomLevel = 11;
	// load state
	try {
		if(localStorage["latitude"]) {
			zoomLevel = parseInt(localStorage["zoom"]);
			latlng = new google.maps.LatLng(localStorage["latitude"], localStorage["longitude"]);
		}
	} catch(err) {
		latlng = new google.maps.LatLng(49.2, -122.9);
		zoomLevel = 11;
	}
	
	
	var myOptions = {
		zoom: zoomLevel,
		center: latlng,
		mapTypeControl: true,
		mapTypeId: google.maps.MapTypeId.ROADMAP
	};
	
    map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
    
    map.mapTypes.set('transportmap',transportmap);
    map.mapTypes.set('osm', osmmapnik);
    
	var optionsUpdate = {
		mapTypeControlOptions: {
			mapTypeIds: ['transportmap','osm',google.maps.MapTypeId.ROADMAP,google.maps.MapTypeId.HYBRID,google.maps.MapTypeId.TERRAIN],
			style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
			position: google.maps.ControlPosition.BOTTOM_LEFT
		}
	};
	map.setMapTypeId('transportmap');
	map.setOptions(optionsUpdate);
	
	directionsDisplay.setMap(map);
	reloadData();
	updatePositions();
	initializePositionUpdating();
}

function checkForHtml5Range() {
	if(navigator.userAgent.match(/iPhone/i) ||
 	navigator.userAgent.match(/iPod/i) ||
 	navigator.userAgent.match(/iPad/i) || 
 	$('#timeShift').attr('type') != 'range') {
 		return false;
 	}
 	return true;
}

function initializePositionUpdating() {
	if( navigator.userAgent.match(/Android/i) ||
 	navigator.userAgent.match(/webOS/i) ||
 	navigator.userAgent.match(/iPhone/i) ||
 	navigator.userAgent.match(/iPod/i)
 	){
 		updateRate = '10000';
 	} else if(navigator.userAgent.match(/iPad/i)) {
 		updateRate = '5000';
 	} else if(navigator.userAgent.match(/Chrome/i) || navigator.userAgent.match(/Safari/i)) {
 		updateRate = '1000';
 	} else {
 		updateRate = '2000';
 	}
 	interval = setInterval("updatePositions()",updateRate);
}

function getTripIcon(routeCode,large) {
	var trainRouteCodes = [999,997,996,980,979];
	var ferryRouteCodes = [998];
	if(jQuery.inArray(routeCode, trainRouteCodes) != -1) {
		tripIcon = "/img/skytrain.png";
		if(large) { tripIcon = "/img/skytrain_selected.png"; }
	} else if(jQuery.inArray(routeCode, ferryRouteCodes) != -1) {
		tripIcon = "/img/seabus.png";
		if(large) { tripIcon = "/img/seabus_selected.png"; }
  	} else {
  		tripIcon = "/img/bus.png";
  		if(large) { tripIcon = "/img/bus_selected.png"; }
  	}
  	return tripIcon;
}

function interpolatePosition(lastStop, nextStop, currentTime) {
	deltaLat = parseFloat(nextStop["lat"]) - parseFloat(lastStop["lat"]);
	deltaLng = parseFloat(nextStop["lng"]) - parseFloat(lastStop["lng"]);
	mag = Math.sqrt(deltaLat*deltaLat+deltaLng*deltaLng);
	timeDiff = parseInt(nextStop["time"]) - parseInt(lastStop["time"]);
	
	timeDeltaLat =  deltaLat/timeDiff*(currentTime-parseInt(lastStop["time"]));
	timeDeltaLng = deltaLng/timeDiff*(currentTime-parseInt(lastStop["time"]));
	computedLat = parseFloat(lastStop['lat']) + timeDeltaLat;
	computedLng = parseFloat(lastStop['lng']) + timeDeltaLng;
	position = new google.maps.LatLng(computedLat, computedLng);
	return position;
}

function interpolateAngle(lastStop, nextStop) {
	deltaLat = parseFloat(nextStop["lat"]) - parseFloat(lastStop["lat"]);
	deltaLng = parseFloat(nextStop["lng"]) - parseFloat(lastStop["lng"]);
	
	angle = 180/Math.PI*Math.atan2(deltaLng,deltaLat);
	//if(angle >= 360) { angle -= 360; }
	if(angle < 0) { angle += 360; }

	return angle;
}

function showTripPath(tripId) {
	$.getJSON(urlPrefix+'Trips/view/'+tripId+'.json', function(tripData) {
		
		var panoramaOptions = {};
		// load route polyline
		//alert(tripData['StopTime'][0]['stop_id']);
		if(lastTripPath) {
			lastTripPath.setMap(null);
		}
		var tripPathCoordinates = [
		];
		var stops = tripData["StopTime"];
		for(var i = 0; i < stops.length; i++) {
			tripPathCoordinates.push(new google.maps.LatLng(parseFloat(stops[i]['Stop']['latitude']),parseFloat(stops[i] ['Stop']['longitude'])));
		}
		var tripPath = new google.maps.Polyline({
			path: tripPathCoordinates,
			strokeColor: "#FF0000",
			strokeOpacity: 1.0,
			strokeWeight: 2
		});
		tripPath.setMap(map);
		lastTripPath = tripPath;
		/*var waypoints = [];
		
		for(var i=1; i<stops.length-1; i++) {
			//alert(parseFloat(stops[i]['Stop']['latitude']).toFixed(5));
			lng = parseFloat(stops[i]['Stop']['longitude']).toFixed(0);
			alert(lng);
			waypoints.push({
				location: new google.maps.LatLng(parseFloat(stops[i]['Stop']['latitude']).toFixed(5),lng),
				stopover: false
			});
		}
		
		var request = {
			origin:new google.maps.LatLng(parseFloat(stops[0]['Stop']['latitude']).toFixed(5),parseFloat(stops[0]['Stop']['longitude']).toFixed(5)), 
			waypoints: waypoints,
			destination:new google.maps.LatLng(parseFloat(stops[stops.length-1]['Stop']['latitude']).toFixed(5),parseFloat(stops[stops.length-1]['Stop']['longitude']).toFixed(5)),
			travelMode: google.maps.DirectionsTravelMode.DRIVING
		};
		
		
		
		directionsService.route(request, function(result, status) {
			if (status == google.maps.DirectionsStatus.OK) {
				directionsDisplay.setDirections(result);
			}
		});*/
		
		// show street view
		/*$('#streetView').css("width", "100%");
		$('#streetView').css("height", "50%");
		$('#map_canvas').css("height", "50%");*/
		
	});
}

function roundNumber(num, dec) {
    return Math.round(num * Math.pow(10, dec)) / Math.pow(10, dec);
}

function showStreetView(tripId,heading) {
	markerPosition = markersArray[tripId].getPosition();
	if(!panorama) {
		
		panoramaOptions = {
			position: markerPosition,
			pov: {
				heading: heading,
				pitch: 0,
				zoom: 1
			},
			enableCloseButton: true
		};
		panorama = new google.maps.StreetViewPanorama(document.getElementById("map_canvas"), panoramaOptions);
		map.setStreetView(panorama);
		
		google.maps.event.addListener(panorama,'closeclick', function() {
			streetViewOpened = false;
			panorama = null;
		});
		
	} else {
		// update position
		if(updateStreetViewThreshold == 3) {
			panorama.setPosition(markerPosition);
			pov = {
				heading: heading,
				pitch: 0,
				zoom: 1
			};
			panorama.setPov(pov);
			map.setStreetView(panorama);
			updateStreetViewThreshold = 0;
		}
		updateStreetViewThreshold++;
	}
}



function showTripInfo(tripId) {
	if(lastClickedMarker) {
		// If the trip hasn't finished, set icon back. 
		if(markersArray[lastClickedMarker]) {
			markersArray[lastClickedMarker].setIcon(getTripIcon(parseInt(busData[lastClickedMarker]["route"]["short_name"]),false));
		}
	}
	lastClickedMarker = tripId;
	markersArray[tripId].setIcon(getTripIcon(parseInt(busData[tripId]["route"]["short_name"]),true));
	$('#tripInfo').html('<div id="#loading_info_spinner"><br/><img src="/img/loading_spinner.gif" /></div>');
	$('#tripInfo').load(urlPrefix+'Trips/view/'+tripId+'/'+timeShift);
	
	$('#showStreetView').click(function() {
		streetViewOpened = true;
		alert('test');
	});
			
	showTripPath(tripId);
}

function updatePositions() {
	if(readyToShow == 1) {
	//var currentTime = Math.round(((new Date()).getTime()-Date.UTC(1970,0,1))/1000);
	//alert("test"+currentTime);
	northBound = map.getBounds().getNorthEast().lat();
	southBound = map.getBounds().getSouthWest().lat();
	eastBound = map.getBounds().getNorthEast().lng();
	westBound = map.getBounds().getSouthWest().lng();
	
	for(var tripID in busData) {
		trip = busData[tripID]["stops"];
		route = busData[tripID]["route"];
		for(i=currentPosition[tripID];i<trip.length;i++) {
			lastStop=trip[i];
			
			if(i < trip.length -1) { // if not at the last stop
				nextStop=trip[i+1];
				currentPosition[tripID] = i;
				if(lastStop["time"] <= currentTime && nextStop["time"] >= currentTime) {
					//this is the current segment, so interpolate and update marker
					
					if(!markersArray[tripID]) {
	  					routeCode = parseInt(route["short_name"]);
	  					tripIcon = getTripIcon(routeCode,false);
						markersArray[tripID] = new google.maps.Marker({
	      					position: new google.maps.LatLng(0, 0), 
	      					title: route["short_name"],
	      					icon: tripIcon 
	      				});
	      				markersArray[tripID].id = tripID;
	      				// marker event listener
	      				google.maps.event.addListener(markersArray[tripID],  "click", function() { showTripInfo(this.id); });

      				}
      				newMarkerPosition = interpolatePosition(lastStop,nextStop,currentTime);
      				// in bounds, or is being used for streetview
      				if((newMarkerPosition.lat() <= northBound && newMarkerPosition.lat() >= southBound && newMarkerPosition.lng() >= westBound && newMarkerPosition.lng() <= eastBound) || lastClickedMarker == tripID) {
						markersArray[tripID].setPosition(newMarkerPosition);
					}
					
					if(!markersArray[tripID].getMap()) {
						markersArray[tripID].setMap(map);
					}
					
					if(lastClickedMarker == tripID && streetViewOpened) {
						// currently selected marker..
		
						heading = interpolateAngle(lastStop, nextStop);
						showStreetView(tripID,heading);
					}
					
					break; // we have updated this trip marker, so break out of the loop
				} else {
					
					if(lastStop["time"] > currentTime && i == 0) { 
						break; // trip hasn't started yet
					}
					
				}
			} else {
				// Arrived at the last stop on the route, so delete the marker
				if(markersArray[tripID]) {
  					markersArray[tripID].setMap(null);
  					delete markersArray[tripID]; 
  					delete busData[tripID]; // remove trip so updatePositions isn't attempted later..
  					break; // we are at the end of the route, so break out of the loop
  				}
			}
		}
		}
	}
}

function blockUI() {
	$.blockUI({ message: '<h1><img src="/img/loading_spinner.gif" /> Loading...</h1>' });
}

function unblockUI() {
	$.unblockUI();
}

  function reloadData() {
  	blockUI();
  	
  	readyToShow = 0;
	$.getJSON(urlPrefix+'Trips/inProgress/'+timeShift+'.json', function(data) {
  		busData = data;
  		
  		for(var tripID in busData) {
  			currentPosition[tripID]=0;
  		}
  		for(var tripID in markersArray) {
  			markersArray[tripID].setMap(null);
  			delete markersArray[tripID];
  		}
  		
  		readyToShow = 1;
  		unblockUI();
	});
	
  }
  
  

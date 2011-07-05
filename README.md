# Depart App

## Description

Depart App maps the real time positions of transit vehicles based on GTFS data.

A running instance of the app can be found at departapp.com (For Vancouver, Canada)

## Todo

1. Fix stack overflow issue that is breaking the app in Chrome
2. Algorithm to route buses along shapes instead of between stops
	* Will be challenging, as the shape data is not related to the timetable data, so the relationship will have to be computed by looking at the proximity of stops to the trip shape
3. Add GTFS for Downtown Historic Railway, False Creek Ferries (when available)
4. Add support for live data feeds (perhaps using Web Sockets)
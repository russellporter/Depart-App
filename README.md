# Depart App

## Description

Depart App maps the real time positions of transit vehicles based on GTFS data.

A running instance of the app can be found at departapp.com (For Vancouver, Canada)

## Features

* Efficiency: Pre-caching of transit data in advance allows many users to use the service without overloading the server
* Merging shape and stop time data to create a "scheduled shape" which allows vehicles to follow curvy roads while runnning on time

## Todo

2. Bug: Closing street view
3. Bug: Right around midnight there is no data
4. Add GTFS for BC Transit (need to make a stop_times to shape conversion)
5. Add GTFS for Downtown Historic Railway, False Creek Ferries (when available)
6. Add support for live data feeds (perhaps using Web Sockets)
7. Database table for picking the icon to use for the route

### Translink Data Issues
* Rupert and Renfrew Skytrain stations too far off, causing issues
* Expo Line shape downtown is 225 meters away from Waterfront Skytrain station

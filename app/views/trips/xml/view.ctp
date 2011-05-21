
<trip id="<?= $trip["Trip"]["id"] ?>">
	<headsign><?= $trip["Trip"]["headsign"] ?></headsign>
	<route id="<?= $trip["Trip"]["route_id"] ?>" />
	<service id="<?= $trip["Trip"]["service_id"] ?>" />
	<direction id="<?= $trip["Trip"]["direction_id"] ?>" />
	<block id="<?= $trip["Trip"]["block_id"] ?>" />
	<shape id="<?= $trip["Trip"]["shape_id"] ?>" />
	<stopTimes>
		<?php foreach($trip["StopTime"] as $stop) { ?><stopTime id="<?= $stop["id"] ?>">
			<departureTime seconds="<?= $stop["departure_time_seconds"] ?>"><?= $stop["departure_time"] ?></departureTime>
			<arrivalTime seconds="<?= $stop["arrival_time_seconds"] ?>"><?= $stop["arrival_time"] ?></arrivalTime>
			<stop id="<?= $stop["stop_id"] ?>" />
		</stopTime>
		<?php } ?>
	</stopTimes>
</trip>

<stopTimes>
<?php foreach($departures as $departure) { ?>
	<stopTime id="<?= $departure["StopTime"]["id"] ?>">
		<departureTime seconds="<?= $departure["StopTime"]["departure_time_seconds"] ?>"><?= $departure["StopTime"]["departure_time"] ?></departureTime>
		<arrivalTime seconds="<?= $departure["StopTime"]["arrival_time_seconds"] ?>"><?= $departure["StopTime"]["arrival_time"] ?></arrivalTime>
		<stop id="<?= $departure["StopTime"]["stop_id"] ?>" />
		<trip id="<?= $departure["Trip"]["id"] ?>">
			<headsign><?= $departure["Trip"]["headsign"] ?></headsign>
			<service id="<?= $departure["Trip"]["service_id"] ?>" />
			<shape id="<?= $departure["Trip"]["shape_id"] ?>" />
			<block id="<?= $departure["Trip"]["block_id"] ?>" />
			<direction id="<?= $departure["Trip"]["direction_id"] ?>" />
			<route id="<?= $departure["Trip"]["route_id"] ?>" />
		</trip>
	</stopTime>
<?php } ?>
</stopTimes>
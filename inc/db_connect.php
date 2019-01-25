<?php

function db_connect($servers, $type = TYPE_SERVERS_ARR)
{
	if ($type == TYPE_SERVERS_ARR) {		
		foreach ($servers as $id => $server) {
			$link = mysqli_connect($server['host'], $server['username'], $server['password'], $server['dbname'], $server['port']);
			if (!$link) {
                var_dump($server);
				echo "Error: Unable to connect to MySQL." . PHP_EOL;
				echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
				echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
				echo json_encode($server) . PHP_EOL;
				exit;
			}
			$connects[$id] = $link;
		}
		return $connects;
	}
	else{		
		$link = mysqli_connect($servers['host'], $servers['username'], $servers['password'], $servers['dbname'], $servers['port']);
		if (!$link) {
			echo "Error: Unable to connect to MySQL." . PHP_EOL;
			echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
			echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
			exit;
		}
		return $link;
	}
}

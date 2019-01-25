<?php
define('__ROOT__', dirname(__FILE__));
require_once(__ROOT__ . "/inc/helper.php");
require_once(__ROOT__ . '/class/SQL.php');
require_once(__ROOT__ . '/class/Colors.php');
require_once(__ROOT__ . '/inc/db_connect.php');
require_once(__ROOT__ . "/inc/config.php");

require_once(__ROOT__ . "/class/CheckErrors.php");

/**
 * $servers from config.php
 */
$executionStartTime = microtime(true);
$connects = db_connect($servers);
$phpRoute = isset($argv[1]) ? $argv[1] : $phpRoute;
$checkErrors = new CheckErrors($connects, $phpRoute,$checkTables);
$result = $checkErrors->checkAllErrors();
$executionEndTime = microtime(true);
$seconds = $executionEndTime - $executionStartTime;

if ($result) {
	echo "\r\n---------------------------\r\n\r\n";
	echo implode("\r\n---------------------------\r\n\r\n", $result);	
	echo "\r\n---------------------------\r\n\r\n";
	$date = date("Ymdhis");
	if (!file_exists('csv')) {
		mkdir('csv', 0777, true);
	}
	if (!file_exists('csv/error')) {
		mkdir('csv/error', 0777, true);
	}
	if (!file_exists('csv/tables')) {
		mkdir('csv/tables', 0777, true);
	}
	$fp = fopen('csv/error/file_' . $date . '.csv', 'w');
	fputcsv($fp, $result);
	fclose($fp);
//	
//	$fp = fopen('csv/tables/file_' . $date . '.csv', 'w');
//	fputcsv($fp, $checkErrors->getFaultTables());	
//	fclose($fp);
	
	foreach($checkErrors->getFaultTables() as $server => $tables){		
		$result="$server ".implode(" ",$tables['tables']);
		file_put_contents('csv/tables/file_' . $date . '.csv', $result.PHP_EOL , FILE_APPEND | LOCK_EX);
		
	}	
	
} else {
	echo ("MINDEN OK\r\n");
}
echo "\nFutási idő: $seconds\n";
exit;

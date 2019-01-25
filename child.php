<?php
define('__ROOT__', dirname(__FILE__));
require_once(__ROOT__ . '/class/SQL.php');
require_once(__ROOT__ . "/inc/helper.php");
require_once(__ROOT__ . "/inc/config.php");
require_once(__ROOT__ . '/inc/db_connect.php');

$table = $argv[1];
$serverId = $argv[2];

$counted = 0;
$results = [];
$checkArr = json_decode($argv[3], true);
foreach($checkArr as $id => $value){
	$checkArr[$value]=$value;
}
$connect = db_connect($servers[$serverId], TYPE_SERVERS_STRING);
if ($connect) {
	if(isset($checkArr[TYPE_AUTOINCREMENT]) || isset($checkArr[TYPE_DATA_LENGTH])){
		$row = SQL::query("SHOW TABLE STATUS LIKE '{$table}'", $connect)->fetchArray();
	}
	foreach ($checkArr as $checkType) {
		switch ($checkType) {
			case TYPE_COUNT: {
					$results[TYPE_COUNT] = SQL::query("SELECT count(*) FROM {$table}", $connect)->fetchValue(0);
				}
				break;
			case TYPE_AUTOINCREMENT: {					
					$results[TYPE_AUTOINCREMENT] = $row['Auto_increment'];					
				}
				break;
			case TYPE_DATA_LENGTH: {
				$results[TYPE_DATA_LENGTH]=$row['Data_length']+$row['Index_length'];
			}
			break;
			case TYPE_SHOW: {
					$describe = SQL::query("SHOW CREATE TABLE {$table}", $connect)->fetchSimpleData();
					$results[TYPE_SHOW] = md5(serialize($describe));					
				}
				break;
			case TYPE_CHECKSUM: {
					$checksum = SQL::query("CHECKSUM TABLE {$table}", $connect)->fetchArray();
					$results[TYPE_CHECKSUM] = $checksum['Checksum'];
				}
				break;			
		}
	}
}

echo json_encode($results);


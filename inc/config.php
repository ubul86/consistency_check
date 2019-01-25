<?php


$servers = [
	1 => [
		"host" => "localhost",
		"port" => 3306,
		"username" => "test",
		"password" => "",
		"dbname" => "db_1"
	],
	2 => [
		"host" => "localhost",
		"port" => 3306,
		"username" => "test",
		"password" => "",
		"dbname" => "db_2"
	]    
];

$checkTables=[
	TYPE_COUNT,
	TYPE_AUTOINCREMENT,
	TYPE_SHOW,
	TYPE_CHECKSUM,	
	TYPE_DATA_LENGTH
];

$phpRoute="php";
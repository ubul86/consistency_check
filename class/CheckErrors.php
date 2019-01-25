<?php

require_once(__ROOT__ . "/inc/config.php");

/**
 * Description of CheckErrors
 *
 * @author ubul
 */
class CheckErrors
{

	public $connects = null;
	public $tablesInConnect = [];
	public $secondsBehindMaster = 0;
	public $secondsBehindMasterReplicaId = 0;
	private $phpRoute;
	private $checkAll;
	private $colors = null;
	private $errorMsg = "";
	private $faultTables=[];
	private $allErrorMsg = [];
	private $checkFunctions = [
		"checkSlavesStatus",
		"checkShowTablesError",
		"checkTableErrors",
	];

	public function __construct($connects, $phpRoute = "php", $checkTables = [])
	{
		$this->connects = $connects;
		$this->phpRoute = $phpRoute;
		$this->checkArr = $checkTables;
		$this->colors = new Colors();
		if (!$connects) {
			die("Rossz csatlakozás");
			exit;
		}
	}

	public function checkAllErrors()
	{
		foreach ($this->checkFunctions as $function) {
			$this->$function();
//			if ($this->errorMsg) {
//				break;
//			}
		}
		return $this->allErrorMsg;
	}

	/**
	 * SHOW TABLES check in every servers
	 */
	public function checkShowTablesError()
	{
		foreach ($this->connects as $id => $connect) {
			$tables = SQL::query("SHOW tables", $connect)->fetchListData();
			$tables = md5(implode(",", $tables));
			$this->tablesInConnect[$id] = $tables;
		}
		$this->getResult($this->tablesInConnect);
	}

	/**
	 * SLAVE STATUS Seconds_Behind_Master check, várakozik -e írásra még a master-től
	 */
	public function checkSlavesStatus()
	{
		foreach ($this->connects as $id => $connect) {
			$slaves = SQL::query("SHOW SLAVE STATUS", $connect)->fetchArray();
			if ($slaves) {
				if ($slaves['Seconds_Behind_Master'] === null) {
					if ($slaves['Last_Error']) {
						$this->allErrorMsg[] = PHP_EOL . $this->getErrorMsg(TYPE_SLAVES_ERR_IO, $slaves['Last_Error']);
					}
				} else if ($slaves['Seconds_Behind_Master'] > $this->secondsBehindMaster) {
					$this->secondsBehindMaster = $slaves['Seconds_Behind_Master'];
					$this->secondsBehindMasterReplicaId = $id;
					$this->allErrorMsg[] = PHP_EOL . $this->getErrorMsg(TYPE_SLAVES_ERR);
				}
			}
		}
	}

	/**
	 * Táblákra vonatkozó error-ok kiszűrése
	 */
	public function checkTableErrors()
	{
		GLOBAL $servers;		
		if ($this->checkArr) {
			$tables = SQL::query("SHOW tables", current($this->connects))->fetchListData();
			foreach ($tables as $table) {
				$this->errorMsg=false;
				echo "TABLE: " . $table;
				$errorMsg = "";
				$executionStartTime = microtime(true);
				$results = [];

				$this->table = $table;

				$this->lockTables();

				$responseArr = $this->getResponseArrFromChildPhp($this->connects, $table);
				foreach ($responseArr as $response) {
					$resultArr = json_decode($response, true);
					foreach ($resultArr as $id => $result) {
						$results[$id][] = $result;
					}
				}

				$this->unlockTables();

				$errorMsg = $this->setErrorMsg($results);
				$executionEndTime = microtime(true);
				$seconds = $executionEndTime - $executionStartTime;

				echo $this->colors->getColoredString(" | CHECKTIME:" . $seconds . "\r\n", $errorMsg ? "red" : "green");
				if ($this->errorMsg) {
					$serverId=$this->getFaultTableErrorsByServers($results);		
					$this->faultTables[$servers[$serverId]['host']]['tables'][]=$this->table;
				}
			}
		}
	}

	private function getFaultTableErrorsByServers($results){		
		GLOBAL $servers;		
		foreach ($results as $type => $result) {
			$firstValue= current($result);
			foreach($result as $serverId => $value){				
				if($firstValue!=$value){	
					return $serverId;
					
				}
			}		
		}		
		return false;
	}
	
	public function getFaultTables(){
		return $this->faultTables;
	}
	
	private function setErrorMsg($results)
	{
		foreach ($results as $type => $result) {
			$result = $this->getErrorResult($type, $result);
			if ($result) {
				return $result;
				break;
			}
		}
	}

	private function lockTables()
	{
		foreach ($this->connects as $connect) {
			SQL::query("LOCK TABLE {$this->table} READ", $connect);
		}
	}

	private function unlockTables()
	{
		foreach ($this->connects as $connect) {
			SQL::query("UNLOCK TABLES", $connect);
		}
	}

	private function getResponseArrFromChildPhp()
	{
		$responseArr = [];
		$checkJson = json_encode($this->checkArr);
		foreach ($this->connects as $id => $connect) {
			$childArr[$id] = popen("{$this->phpRoute} child.php $this->table $id $checkJson", "r");
		}

		foreach ($childArr as $child) {
			$responseArr[] = stream_get_contents($child);
		}
		return $responseArr;
	}

	/**
	 * Megnézi hogy egyeznek -e a táblák
	 * @param array $arr
	 * @return boolean
	 */
	public function getResult($arr)
	{
		$firstValue = current($arr);
		$msg = [];
		$error = 0;
		foreach ($arr as $id => $val) {
			$msg[] = "{$id}. server tábla hash: {$val}\r\n";
			if ($firstValue !== $val) {
				$error = 1;
			}
		}
		if ($error) {
			$this->allErrorMsg[] = PHP_EOL . "Nem egyeznek a szerver tábláinak adatai!\r\n\r\n" . implode("", $msg);
			return true;
		}
		return false;
	}

	public function getErrorResult($type, $arr)
	{
		$firstValue = current($arr);
		$msg = [];
		$error = 0;
		foreach ($arr as $id => $val) {
			$msg[] = $this->getMessage($type, $id, $val);
			if ($firstValue !== $val) {
				$error = 1;
			}
		}
		if ($error) {
			$this->allErrorMsg[] = PHP_EOL . $this->getErrorMsg($type, $msg);
			$this->errorMsg=true;
			return true;
		}
		return false;
	}

	private function getMessage($type, $id, $val)
	{
		$msg = "";
		switch ($type) {
			case TYPE_COUNT: {
					$msg = "{$id}. server {$this->table} tábla count(*): {$val}\r\n";
				}
				break;
			case TYPE_SHOW: {
					$msg = "{$id}. server {$this->table} tábla hash: {$val}\r\n";
				}
				break;
			case TYPE_CHECKSUM: {
					$msg = "{$id}. server {$this->table} tábla checksum: {$val}\r\n";
				}
				break;
			case TYPE_AUTOINCREMENT: {
					$msg = "{$id}. server {$this->table} tábla autoincrement: {$val}\r\n";
				}
				break;
			case TYPE_DATA_LENGTH: {				
				$msg = "{$id}. server {$this->table} data+index length: {$val}\r\n";				
			}
			break;
		}
		return $msg;
	}

	private function getErrorMsg($type, $msg = "")
	{
		$errormsg = "";
		switch ($type) {
			case TYPE_COUNT: {
					$errormsg = "Nem egyeznek a(z) {$this->table} tábla count adatok!\r\n\r\n" . implode("", $msg);
				}
				break;
			case TYPE_SHOW: {
					$errormsg = "Nem egyeznek a(z) {$this->table} tábla show create table adatok!\r\n\r\n" . implode("", $msg);
				}
				break;
			case TYPE_CHECKSUM: {
					$errormsg = "Nem egyeznek a(z) {$this->table} tábla checksum adatok!\r\n\r\n" . implode("", $msg);
				}
				break;
			case TYPE_AUTOINCREMENT: {
					$errormsg = "Nem egyeznek a(z) {$this->table} autoincrement adatok!\r\n\r\n" . implode("", $msg);
				}
				break;
			case TYPE_SLAVES_ERR: {
					$errormsg = "Replica Seconds_Behind_Master értéke {$this->secondsBehindMaster} a {$this->secondsBehindMasterReplicaId} replikánál \r\n";
				}
				break;
			case TYPE_SLAVES_ERR_IO: {
					$errormsg = $msg;
				}
				break;
			case TYPE_DATA_LENGTH:{
				$errormsg = "Nem egyeznek a(z) {$this->table} data length adatok!\r\n\r\n" . implode("", $msg);
			}
			break;
		}
		return $errormsg;
	}

}

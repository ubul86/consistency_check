<?php
class SQL
{

	/**
     * Levédi a változót a mysql_real_escape_string függvénnyel, hogy az SQL utasításba
     * ne lehessen nem várt eseményeket létrehozni. (SQL inject)
     *
     * @param mixed $val A változó amit le akarunk védeni.
     * @param string $db A kapcsolatazonosító amely alapján a mysql_real_escape_string függvény dolgozik
     * @return mixed A konvertált változó.
     */
    public static function escape($array) {
        return $array;
    }
	
	/**
	 * Végrehajt egy SQL utasítást.
	 *
	 * @param string $sql
	 * @param string $db
	 * @param boolean $log  Ezzel a paraméterrel a lekérdezés időtartamára felül lehet definiálni a Log-ban lévő
	 * SQL log beállítást. (enableSQL(), disableSQL()) Természetesen, ez a beállítás nem írja felül a Log
	 * ki- vagy bekapcsolt állapotát, tehát Log::disableLog() állapotban nem fog megjelenni a lekérdezés a Log-ban!
	 * Ha null az értéke akkor a Log-ban lévő beállítás szerint jelennek meg a dolgok, ha true, akkor az SQL loggolódik
	 * ha false akkor viszont nem jelenik meg a lekérdezés a logban.
	 * @return SQLResult 
	 */
	public static function query($sql, $db)
	{
		$pre = microtime(true);
		$result = $db->query($sql);		
		return new SQLResult($result, $db);
	}

}

class SQLResult
{

	//private $result=false;
	/**
	 * @var MySQLi_Result
	 */
	public $result = false;

	/**
	 * @var MySQLi
	 */
	private $db = '';
	private $affected_rows = 0;
	private $insert_id = 0;

	/**
	 *
	 * @param mysqli_reult $result
	 * @param mysqli $db
	 */
	public function __construct($result, $db)
	{
		$this->result = $result;
		$this->db = $db;
		$this->affected_rows = $db->affected_rows;
		$this->insert_id = $db->insert_id;
	}

	/**
	 * Megadja, hogy hány sor van az eredményében.
	 *
	 * @return integer Sorok száma
	 */
	public function numRows()
	{
		if ($this->result === false)
			return 0;
		return($this->result->num_rows);
	}

	/**
	 * Megadja, hogy a query hány sort érintett.
	 *
	 * @return integer
	 */
	public function affectedRows()
	{
		return ($this->affected_rows);
	}

	/**
	 * Felszabadítja a $result query által lefoglalt területet.
	 *
	 * @return boolean
	 */
	public function freeResult()
	{
		if ($this->result === false)
			return;
		$this->result->free_result();
		$this->result = false;
	}

	/**
	 * A lekérdezés egy sorával tér vissza numerikus indexekkel (0,1,2,...).
	 * <br>
	 * SQL::query("SELECT userid, username,... FROM users")->fetchRow();<br>
	 * <br>
	 * array( 0=>13, 1=>"Gipsz Jakab",....)
	 *
	 * @return array
	 */
	public function fetchRow()
	{
		if ($this->result === false)
			return false;
		$array = $this->result->fetch_row();
		return ($array == null ? null : SQL::escape($array));
	}

	/**
	 * A lekérdezés egy sorával tér vissza asszociatív tömb formájában,
	 * ahol a kulcsok a mezőnevek.<br>
	 * <br>
	 * SQL::query("SELECT userid, username,... FROM users")->fetchArray();<br>
	 * <br>
	 * array( "id"=>13, "username"=>"Gipsz Jakab",....)<br>
	 *
	 * @param int $resulttype
	 * @return array
	 */
	public function fetchArray($resulttype = MYSQLI_ASSOC)
	{
		if ($this->result === false)
			return false;
		$array = $this->result->fetch_array($resulttype);
		return ($array == null ? null : SQL::escape($array));
	}

	/**
	 * Az AutoIncrement mező értékével tér vissza.
	 *
	 * @return int
	 */
	public function lastInsertId()
	{
		return $this->insert_id;
	}

	/**
	 * Kiveszi az eredmény teljes tartalmát egy tömbbe a megadott mezővel indexelve.<br>
	 * <br>
	 * SQL::query("SELECT userid, username,... FROM users")->fetchData("id");<br>
	 * <br>
	 * array(<br>
	 *    13 => array("id" => 13, "username"=>"Gipsz Jakab",...)<br>
	 *    25 => array("id" => 25, "username"=>"Gipsz Jakabné",...)<br>
	 *    ...<br>
	 * )<br>
	 *
	 * @param string|integer $column Annak az oszlopnak az azonosítója, amelyik szerint szeretnénk
	 * a visszatérési tömböt indexelni. Ha nincs megadva, akkor numerikus index.
	 * @param integer $type
	 * @return array
	 */
	public function fetchData($column = '')
	{
		if ($this->result === false)
			return array();

		$ret = array();
		while ($v = $this->fetchArray()) {
			if (isset($v[$column]))
				$ret[$v[$column]] = $v;
			else
				$ret[] = $v;
		}
		return $ret;
	}

	/**
	 * kiveszi az eredmény teljes tartalmát egy tömbbe a megadott mezőkkel indexelve<br>
	 * <br>
	 * SQL::query("SELECT userid, username,... FROM users")->fetchData("id");<br>
	 * <br>
	 * array(<br>
	 *    13 => array("id" => 13, "username"=>"Gipsz Jakab",...)<br>
	 *    25 => array("id" => 25, "username"=>"Gipsz Jakabné",...)<br>
	 *    ...<br>
	 * )<br>
	 * @param string|integer $column Annak az oszlopnak az azonosítója, amelyik szerint szeretnénk
	 * a visszatérési tömböt indexelni. Ha nincs megadva, akkor numerikus index.
	 * @param string|integer $column2 Annak az oszlopnak az azonosítója, amelyik szerint szeretnénk
	 * a visszatérési tömb altömbjét indexelni. Ha nincs megadva, akkor numerikus index.
	 * @param integer $type
	 * @return array
	 */
//    public function fetchMultiData($column='',$collumn2='',$column3='',$type=MYSQL_ASSOC,$escape=true) {  
	public function fetchMultiData($column = '', $collumn2 = '', $type = MYSQL_ASSOC, $escape = true)
	{
		if ($this->result === false)
			return array();

		$mret = true;
		$result_arr = array();
		while ($mret == true) {
			$mret = $this->fetchArray();
			if (is_array($mret)) {
				if (!empty($column) && isset($mret[$column])) {
					$key = $mret[$column];
					if (!empty($collumn2) && isset($mret[$collumn2])) {
						$subkey = $mret[$collumn2];
						$result_arr[$key][$subkey] = $mret;
					} else {
						$result_arr[$key] = $mret;
						$mret = true;
					}
				} else {
					$result_arr[] = $mret;
					$mret = true;
				}
			} else
				$mret = false;
		}

		return $result_arr;
	}

	/**
	 * Visszaadja az eredményt egy tömbbe, ahol a kulcs a $column-nal definiált oszlop, az érték meg a fennmaradó
	 * oszlop. Ez csak "2 változós" lekérdezéseknél alkalmazható! Ha nincs a lekérdezésben $column nevű oszlop vagy
	 * csak abból az egy oszlopból áll a lekérdezés akkor üres tömbbel tér vissza.<br>
	 * <br>
	 * SQL::query("SELECT userid, username FROM users")->fetchSimpleData("userid");<br>
	 * <br>
	 *  array{<br>
	 *      13 => "Gipsz Jakab",<br>
	 *      25 => "Gipsz Jakabné",<br>
	 *      ...<br>
	 *  }<br>
	 *
	 * @param string $column A visszatérési tömb kulcsát azonosítja.
	 * @return array
	 */
	public function fetchSimpleData($column = '')
	{
		if ($this->result === false)
			return array();

		$ret = array();
		$first = true;
		$value = "";

		while ($v = $this->fetchArray()) {
			if ($first) {
				//Megállapítjuk a kulcsokat
				foreach ($v as $key => $val) {
					if (empty($column))
						$column = $key;   //Ha nincs column, akkor az első oszlop a kulcs
					elseif ($column == $key)
						continue;	//A kulcs mező rendben
					else if (empty($value))
						$value = $key;	//Az első nem $column mezők lesznek a visszatérési tömb értékei
				}

				if (empty($column) || empty($value)) {
					return array();
				}

				$first = false;
			}

			$ret[$v[$column]] = $v[$value];
		}

		return $ret;
	}

	/**
	 * A lekérdezés eredményét (1 mező) visszaadja egy tömbbe.<br>
	 * <br>
	 * SQL::query("SELECT userid FROM users")->fetchListData();<br>
	 * <br>
	 * array(13,25,46,...)<br>
	 *
	 * @return array 
	 */
	public function fetchListData()
	{
		if ($this->result === false)
			return array();

		$ret = array();
		while (list($v) = $this->fetchRow()) {
			$ret[] = $v;
		}
		return $ret;
	}

	/**
	 * @deprecated Használd a fetchListData() függvényt.
	 */
	public function fetchList()
	{
		return $this->fetchListData();
	}

	/**
	 * Egyetlen értékkel tér vissza. Hasznos pl: SELECT COUNT(*) FROM ... lekérdezéseknél
	 * @return mixed
	 */
	public function fetchValue($default = null)
	{
		list($v) = $this->fetchRow();
		if (empty($v))
			return $default;
		return $v;
	}

	/**
	 *  Visszaadja a MySQLi_Result osztályt
	 * @return MySQLi_Result
	 */
	public function getMySQLi_Result()
	{
		return $this->result;
	}

	public function isValid()
	{
		return !empty($this->result);
	}

}

class SQLException extends Exception
{

	private $query;

	public function __construct($query, $message = '', $code = 0)
	{
		$this->query = $query;
		$message .= "\n{" . $query . "}";
		parent::__construct($message, $code);
	}

	public function getQuery()
	{
		return $this->query;
	}
}
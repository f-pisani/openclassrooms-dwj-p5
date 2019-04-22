<?php
namespace App;

/***********************************************************************************************************************
 * Class DatabaseException
 *
 * Exceptions throws by DB.
 */
class DatabaseException extends \Exception {};

/***********************************************************************************************************************
 * Class DB
 */
class DB
{
	const FETCH_BOTH=0x1; // NUM & ASSOC
	const FETCH_NUM=0x2;
	const FETCH_ASSOC=0x4;
	const FETCH_ARRAY=0x8; // ASSOC
	const FETCH_OBJECT=0x10;
	const FETCH_ROW=0x20; // NUM

	static private $instance=null;
	private $db;
	private $dbname;
	private $connect_errno;
	private $connect_error;
	private $warning_count;
	private $errors;
	private $sqlstate;
	private $info;
	private $num_rows;
	private $field_count;
	private $fields;
	private $insert_id;
	private $affected_rows;

	/*******************************************************************************************************************
	 * SINGLETON !! CALL DB::get()
	 ******************************************************************************************************************/
	private function __construct()
	{
		$this->db = null;
		$this->dbname = '';
		$this->connect_errno = 0;
		$this->connect_error = '';
		$this->warning_count = 0;
		$this->errors = [];
		$this->sqlstate = '';
		$this->info = '';
		$this->num_rows = 0;
		$this->field_count = 0;
		$this->fields = [];
		$this->insert_id = 0;
		$this->affected_rows = 0;
	}

 	public function __destruct()
 	{
 		if($this->db && !$this->connect_errno())
         	$this->db->close();
    }

	/*******************************************************************************************************************
	 * public static function get()
	 *
	 * Returns singleton instance for DB
	 */
	public static function get()
	{
		if (!self::$instance) self::$instance = new DB();

		return self::$instance;
	}

	/*******************************************************************************************************************
	 * public static function esc($str)
	 *
	 * Returns $str escaped
	 */
	public static function esc($str)
	{
		$DB = self::get();

		return $DB->real_escape_string($str);
	}

	/*******************************************************************************************************************
	 * query($query)
	 *     $query: SQL $query
	 *
	 * Returns $query results
	 */
	public function query($query)
	{
		$this->init();

		if (!$this->connect_errno())
		{
			$result = $this->db->query($query, MYSQLI_STORE_RESULT);

			$this->warning_count = $this->db->warning_count;
			$this->errors = $this->db->error_list;
			$this->sqlstate = $this->db->sqlstate;

			if ($result instanceof \mysqli_result)
			{
				$this->num_rows = $result->num_rows;
				$this->field_count = $result->field_count;
				$this->fields = $this->parseFields($result->fetch_fields());
				$this->affected_rows = 0;
			}
			else
			{
				$this->info = $this->db->info;
				$this->num_rows = 0;
				$this->field_count = 0;
				$this->fields = [];
				$this->insert_id = $this->db->insert_id;
				$this->affected_rows = $this->db->affected_rows;
			}

			if ($this->errors) throw new DatabaseException($this->error());

			return $result;
		}

		return false;
	}

	/*******************************************************************************************************************
	 * fetch($query, $resultType=self::FETCH_BOTH, $className="stdClass", $classParams=[])
	 *     $query: SQL $query
	 *	   $resultType: Format results
	 *     $className: Used with FETCH_OBJECT; By default stdClass is Used
	 *     $classParams: Parameters for $className ctor
	 *
	 * Returns results for $query
	 */
	public function fetch($query, $resultType=self::FETCH_BOTH, $className="stdClass", $classParams=[])
	{
		$results = $this->query($query);

		$rows = [];
		if ($results && $results instanceof \mysqli_result && $results->num_rows)
		{
			switch ($resultType)
			{
				case self::FETCH_ROW:
				case self::FETCH_NUM: $rows = $results->fetch_all(MYSQLI_NUM); break;
				case self::FETCH_ARRAY:
				case self::FETCH_ASSOC: $rows = $results->fetch_all(MYSQLI_ASSOC); break;
				case self::FETCH_OBJECT:
					while($row = ($classParams) ? $results->fetch_object($className, $classParams) : $results->fetch_object($className))
						$rows[] = $row;
				break;
				default: $rows = $results->fetch_all(MYSQLI_BOTH); break;
			}
		}
		else if(!$results instanceof \mysqli_result)
			$rows = $results;

		return $rows;
	}

	/*******************************************************************************************************************
	 * public function real_escape_string($str)
	 *
	 * Returns $str espaced
	 */
	public function real_escape_string($str)
	{
		$this->init();

		if (!$this->connect_errno()) return $this->db->real_escape_string($str);
		else return addslashes($str);
	}

	/*******************************************************************************************************************
	 * Getters / Setters
	 */
	public function num_rows() { return $this->num_rows; }
	public function insert_id() { return $this->insert_id; }
	public function affected_rows() { return $this->affected_rows; }
	public function field_count() { return $this->field_count; }
	public function fields() { return $this->fields; }
	public function warnings()
	{
		$warnings = [];
		if ($this->warning_count)
		{
			$e = $this->db->get_warnings();
		   	do
		   	{
			   	$warnings[] = "Warning: $e->errno: $e->message";
		   	} while ($e->next());
		}

		return $warnings;
	}
	public function errors() { return $this->errors; }
	public function error()
	{
		if (!$this->connect_errno()) return ($this->db->errno) ? "(".$this->sqlstate.":".$this->db->errno.") ".$this->db->error : '';
		else if ($this->connect_errno()) return $this->connect_error();
		else return '';

	}
	public function connect_errno() { return $this->connect_errno; }
	public function connect_error() { return $this->connect_error; }
	public function info() { return $this->info; }
	public function get_charset() { return ($this->db && !$this->connect_errno()) ? $this->db->get_charset() : stdClass(); }
	public function set_charset($charset) { return ($this->db && !$this->connect_errno()) ? $this->db->set_charset($charset) : false; }
	public function get_connection_stats() { return ($this->db && !$this->connect_errno()) ? $this->db->get_connection_stats() : ''; }
	public function options($option, $value) { return ($this->db && !$this->connect_errno()) ? $this->db->options($option, $value) : false; }
	public function ping() { return ($this->db && !$this->connect_errno()) ? $this->db->ping() : false; }
	public function select_db($dbname)
	{
		$res = false;
		if ($this->db && !$this->connect_errno())
		{
			$res = $this->db->select_db($dbname);
			if ($res) $this->dbname = $dbname;
		}

		return $res;
	}
	public function get_database() { return $this->dbname; }
	public function stat() { return ($this->db && !$this->connect_errno()) ? $this->db->stat() : false; }
	public function autocommit($mode) { return ($this->db && !$this->connect_errno()) ? $this->db->autocommit($mode) : false; }
	public function commit($flags=0,$name="") { return ($this->db && !$this->connect_errno()) ? $this->db->commit($flags, $name) : false; }
	public function client_info() { return ($this->db) ? $this->db->client_info : false; }
	public function client_version() { return ($this->db) ? $this->db->client_version : false; }
	public function host_info() { return ($this->db && !$this->connect_errno()) ? $this->db->host_info : ''; }
	public function protocol_version() { return ($this->db && !$this->connect_errno()) ? $this->db->protocol_version : ''; }
	public function server_info() { return ($this->db && !$this->connect_errno()) ? $this->db->server_info : ''; }
	public function server_version() { return ($this->db && !$this->connect_errno()) ? $this->db->server_version : ''; }


	/*******************************************************************************************************************
	 * private function init()
	 *
	 * Initialize database connection
	 */
	private function init()
	{
		if (!$this->db && !$this->connect_errno())
		{
			$this->db = new \mysqli(Config::get('DB_HOST', 'localhost'),
									Config::get('DB_USER', 'root'),
									Config::get('DB_PWD', ''),
									Config::get('DB_BASE', 'undefined'));

			if ($this->db->connect_errno)
			{
				$this->connect_errno = $this->db->connect_errno;
				$this->connect_error = $this->db->connect_error;
			}
			else
				$this->dbname = DB_BASE;
		}
	}

	/*******************************************************************************************************************
	 * private function parseFields($fields)
	 *     $fields: $result->fetch_fields()
	 *
	 * Returns fields with humans readable types and flags
	 */
	private function parseFields($fields)
	{
		static $types = [];
		static $flags = [];
		static $flags_cache = [];
 		static $constants = [];

		if (!$constants) $constants = get_defined_constants(true);
		if (!$types) foreach ($constants['mysqli'] as $c => $n) if (preg_match('/^MYSQLI_TYPE_(.*)$/', $c, $m)) $types[$n] = $m[1];
		if (!$flags) foreach ($constants['mysqli'] as $c => $n) if (preg_match('/^MYSQLI_(.*)_FLAG$/', $c, $m)) if (!array_key_exists($n, $flags)) $flags[$n] = $m[1];

		foreach ($fields as $field)
		{
		  	$typeId = $field->type;
		  	$field->type = array_key_exists($typeId, $types) ? $types[$typeId] : $field->type;

		  	$flags_num = $field->flags;
			if (!array_key_exists($flags_num, $flags_cache))
			{
				$result = [];
			    foreach ($flags as $n => $t) if ($flags_num & $n) $result[] = $t;
				$flags_cache[$flags_num] = $result;
			}

		    $field->flags = $flags_cache[$flags_num];
			$fields[$field->orgname] = $field;
		}

		return $fields;
	}
}

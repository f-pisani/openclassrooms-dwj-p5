<?php
namespace App;

/***********************************************************************************************************************
 * Class ModelField
 *     public function set($data_raw)
 *     public function name()
 *     public function get()
 *     public function raw()
 *     public function toDatabase()
 *     public function isModified()
 *     public function setModified($modified)
 *     public function reverse()
 *     private function cast()
 *     private function castForDababase()
 *
 * This is a representation of a column from the Model table.
 */
class ModelField
{
	private $name; // Fieldname
	private $data_raw; // Field data
	private $data_typecast; // Field type for cast
	private $data_type; // Field data type
	private $data_casted; // Field casted
	private $data_old; // Previous data
	private $isModified; // Indicates if the field is different from initial value

	public function __construct($name, $data_raw=null, $data_typecast=null, $data_type=null)
	{
		$this->name = $name;
		$this->data_raw = $data_raw;
		$this->data_typecast = $data_typecast;
		$this->data_type = $data_type;
		$this->data_casted = null;
		$this->data_old = null;

		$this->isModified = false;
	}

	/*******************************************************************************************************************
	 * public function set($data_raw)
	 *     $data_raw : Raw datas
	 *
	 * Changes field datas.
	 */
	public function set($data_raw)
	{
		if ($data_raw != $this->data_old) $this->isModified = true;

		$this->data_old = $this->data_raw;
		$this->data_raw = $data_raw;
		$this->data_casted = null;

		return $this;
	}

	/*******************************************************************************************************************
	 * public function name()
	 *
	 * Returns fieldname.
	 */
	public function name() { return $this->name; }

	/*******************************************************************************************************************
	 * public function get()
	 *
	 * Returns casted datas.
	 */
	public function get()
	{
		if (!$this->data_casted) $this->cast();

		return $this->data_casted;
	}

	/*******************************************************************************************************************
	 * public function raw()
	 *
	 * Returns raw datas.
	 */
	public function raw() { return $this->data_raw; }

	/*******************************************************************************************************************
	 * public function toDatabase()
	 *
	 * Returns escaped datas for database queries.
	 */
	public function toDatabase()
	{
		return $this->castForDababase();
	}

	/*******************************************************************************************************************
	 * public function isModified()
	 *
	 * Returns true if datas are modified ; False otherwise.
	 */
	public function isModified() { return $this->isModified; }

	/*******************************************************************************************************************
	 * public function setModified($modified)
	 *
	 * Changes internal state of the modified flag.
	 */
	public function setModified($modified) { $this->isModified = $modified; }

	/*******************************************************************************************************************
	 * public function reverse()
	 *
	 * Rollback data to previous value.
	 */
	public function reverse()
	{
		$data_raw = $this->data_raw;

		$this->data_raw = $this->data_old;
		$this->data_old = $data_raw;

		if ($this->isModified) $this->isModified = false;

		return $this;
	}

	/*******************************************************************************************************************
	 * public function cast()
	 *
	 * Cast internal datas.
	 */
	private function cast()
	{
		$value = $this->data_raw;

		switch ($this->data_typecast)
		{
			case 'str':
			case 'string': 		$value = (string)$value; break;
			case 'bool':
			case 'boolean': 	$value = boolval($value); break;
			case 'date': 		$value = date("d-m-Y", strtotime($value)); break;
			case 'datetime': 	$value = date("d-m-Y H:i:s", strtotime($value)); break;
			case (preg_match("/^(?:date|datetime):([a-zA-Z\/\: ]+)$/", $this->data_typecast, $matches) ? true : false):
				$value = date($matches[1], strtotime($value));
			break;
			case 'int':
			case 'integer': 	$value = intval($value); break;
			case 'double': 		$value = doubleval($value); break;
			case 'float': 		$value = floatval($value); break;
			case 'json':
				$tmp = json_decode($value, true);
				if (json_last_error() == JSON_ERROR_NONE) $value = $tmp;
			break;
			case 'array': 		$value = (array)$value; break;
			case 'obj':
			case 'stdClass':
			case 'object': 		$value = (object)$value; break;
			default: ;
		}

		$this->data_casted = $value;
	}

	/*******************************************************************************************************************
	 * public function castForDababase()
	 *
	 * Escape datas for database queries.
	 */
	private function castForDababase()
	{
		$value = $this->data_raw;

		if ($value === null) return null;

		switch ($this->data_type)
		{
			case 'str':
			case 'string': 		$value = addslashes($value); break;
			case 'int':
			case 'integer': 	$value = intval($value); break;
			case 'double': 		$value = doubleval($value); break;
			case 'float': 		$value = floatval($value); break;
			default: $value = DB::esc($value);
		}

		return $value;
	}
}

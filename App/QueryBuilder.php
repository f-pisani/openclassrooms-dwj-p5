<?php
namespace App;

/***********************************************************************************************************************
 * Class QueryBuilder
 *     public function select($columns='*')
 *     public function count($column, $alias="")
 *     public function max($column, $alias="")
 *     public function min($column, $alias="")
 *     public function avg($column, $alias="")
 *     public function sum($column, $alias="")
 *     public function update($fields)
 *     public function increment($col, $amount=1, $fields=[])
 *     public function decrement($col, $amount=1, $fields=[])
 *     public function insert($fields)
 *     public function insertOrUpdate($where, $fields)
 *     public function delete()
 *     public function table($tables)
 *     public function crossJoin($table)
 *     public function on($col1, $operator, $col2="")
 *     public function orOn($col1, $operator, $col2="")
 *     public function join($table, $col1, $operator="", $col2="", $type="inner")
 *     public function leftJoin($table, $col1, $operator="", $col2="")
 *     public function rightJoin($table, $col1, $operator="", $col2="")
 *     public function where(...$where)
 *     public function whereIn(...$where)
 *     public function whereNotIn(...$where)
 *     public function whereBetween(...$where)
 *     public function whereNotBetween(...$where)
 *     public function whereDate(...$where)
 *     public function whereDay(...$where)
 *     public function whereMonth(...$where)
 *     public function whereYear(...$where)
 *     public function whereTime(...$where)
 *     public function whereNull(...$where)
 *     public function whereNotNull(...$where)
 *     public function whereColumn(...$where)
 *     public function orWhere(...$where)
 *     public function orWhereIn(...$where)
 *     public function orWhereNotIn(...$where)
 *     public function orWhereBetween(...$where)
 *     public function orWhereNotBetween(...$where)
 *     public function orWhereDate(...$where)
 *     public function orWhereDay(...$where)
 *     public function orWhereMonth(...$where)
 *     public function orWhereYear(...$where)
 *     public function orWhereTime(...$where)
 *     public function orWhereNull(...$where)
 *     public function orWhereNotNull(...$where)
 *     public function orWhereColumn(...$where)
 *     public function groupBy(...$columns)
 *     public function having(...$having)
 *     public function orHaving(...$having)
 *     public function orderBy($column, $dir="asc")
 *     public function latest($column="created_at")
 *     public function oldest($column="created_at")
 *     public function skip($amount)
 *     public function take($amount)
 *     public function distinct()
 *     public function cache()
 *     public function nocache()
 *     public function union(QueryBuilder $query)
 *     public function intersect(QueryBuilder $query)
 *     public function query()
 *     public function exists()
 *     public function doesntExists()
 *     protected function createComparison($andOr, $where)
 *     protected function sqlQuotes($str)
 *
 * SQL Queries builder.
 *     $columns accept array or string with comma separated columns
 *     $fields accept assoc array ['fieldname' => 'value']
 *     Some methods accepts Closure (ex: where clauses with parentheses, join clauses with parentheses, having clauses with parentheses)
 */
class QueryBuilder
{
	public static $comparisonOperators = ["=", "<>", ">", ">=", "<", "<="];
	protected $select;
	protected $insert;
	protected $update;
	protected $insertOrUpdate;
	protected $delete;
	protected $from;
	protected $join;
	protected $where;
	protected $groupBy;
	protected $having;
	protected $orderBy;
	protected $skip;
	protected $take;
	protected $distinct;
	protected $nocache;
	protected $cache;

	public function __construct()
	{
		$this->select = "";
		$this->insert = "";
		$this->update = "";
		$this->insertOrUpdate = false;
		$this->delete = false;
		$this->from = "";
		$this->join = "";
		$this->where = "";
		$this->groupBy = "";
		$this->having = "";
		$this->skip = "";
		$this->take = "";
		$this->orderBy = "";
		$this->distinct = false;
		$this->nocache = false;
		$this->cache = false;
	}

	public function __toString(){ return $this->query(); }

	public function select($columns='*')
	{
		if (is_string($columns) && preg_match("/[,]{1}/", $columns)) $columns = explode(",", $columns);
		else if (is_string($columns)) $columns = [$columns];

		if (is_array($columns))
		{
			$select = [];
			foreach($columns as $col) $select[] = $this->sqlQuotes($col);
			if ($this->select) $this->select .= ",";
			$this->select .= implode(",", $select);
		}
		else
		{
			if ($this->select) $this->select .= ",";
			$this->select .= $this->sqlQuotes($columns);
		}

		return $this;
	}

	public function count($column, $alias="")
	{
		$this->select("count(".$this->sqlQuotes($column).") ".$alias);

		return $this;
	}

	public function max($column, $alias="")
	{
		$this->select("max(".$this->sqlQuotes($column).") ".$alias);

		return $this;
	}

	public function min($column, $alias="")
	{
		$this->select("min(".$this->sqlQuotes($column).") ".$alias);

		return $this;
	}

	public function avg($column, $alias="")
	{
		$this->select("avg(".$this->sqlQuotes($column).") ".$alias);

		return $this;
	}

	public function sum($column, $alias="")
	{
		$this->select("sum(".$this->sqlQuotes($column).") ".$alias);

		return $this;
	}

	public function update($fields)
	{
		if (is_string($fields) && preg_match("/[=|,]{1}/", $fields))
		{
			$setList = explode(',', $fields);
			$fields = [];
			foreach ($setList as $set)
			{
				$set = explode('=', $set);
				if (is_array($set) && count($set) == 2)
				{
					$fields[$set[0]] = $set[1];
				}
			}
		}

		if (is_array($fields))
		{
			$update = [];
			foreach($fields as $fieldname => $value)
			{
				if ($value === null) $update[] = $this->sqlQuotes($fieldname)."=null";
				else $update[] = $this->sqlQuotes($fieldname)."='".$value."'";
			}

			if ($update)
			{
				if ($this->update) $this->update .= ',';
				$this->update .= implode(',', $update);
			}
		}

		return $this;
	}

	public function increment($col, $amount=1, $fields=[])
	{
		if (is_string($col))
		{
			if ($this->update) $this->update .= ",";
			$this->update .= $this->sqlQuotes($col)."+=".intval($amount);

			if ($fields) $this->update($fields);
		}

		return $this;
	}

	public function decrement($col, $amount=1, $fields=[])
	{
		if (is_string($col))
		{
			if ($this->update) $this->update .= ",";
			$this->update .= $this->sqlQuotes($col)."-=".intval($amount);

			if ($fields) $this->update($fields);
		}

		return $this;
	}

	public function insert($fields)
	{
		if (is_array($fields) && count($fields) > 0)
		{
			if (array_keys($fields) == range(0, count($fields)-1))
			{
				$this->insert = "";
				$keys = [];
				$values = [];
				foreach ($fields as $data)
				{
					if (!$keys) $keys = array_keys($data);
					if (array_keys($data) == $keys)
					{
						$insert_values = [];
						$data = array_values($data);
						foreach ($data as $v)
						{
							if ($v === null) $insert_values[] = "null";
							else $insert_values[] = "'".$v."'";
						}

						$values[] = "(".implode(',', $insert_values).")";
					}
				}
				if ($keys && $values) $this->insert = "(`".implode("`,`", $keys)."`) values ".implode(',', $values);
			}
			else
			{
				$this->insert = "";
				$keys = array_keys($fields);
				$values = array_values($fields);
				$insert_values = [];
				foreach ($values as $v)
				{
					if ($v === null) $insert_values[] = "null";
					else $insert_values[] = "'".$v."'";
				}

				if ($keys && $values) $this->insert = "(`".implode("`,`", $keys)."`) values (".implode(',', $insert_values).")";
			}
		}

		return $this;
	}

	public function insertOrUpdate($where, $fields)
	{
		if (is_array($where) && is_array($fields))
		{
			$this->insertOrUpdate = true;
			$this->insert(array_merge($where, $fields));
			$this->update($fields);
		}

		return $this;
	}

	public function delete()
	{
		$this->delete = true;

		return $this;
	}

	public function table($tables)
	{
		if (is_string($tables) && preg_match("/[,]{1}/", $tables)) $tables = explode(",", $tables);

		if (is_array($tables))
		{
			$this->from = [];
			foreach($tables as $table) $this->from[] = $this->sqlQuotes($table);
			$this->from = implode(',', $this->from);
		}
		else $this->from = $this->sqlQuotes($tables);

		return $this;
	}

	public function crossJoin($table)
	{
		$this->join .= " cross join ".$this->sqlQuotes($table);

		return $this;
	}

	public function on($col1, $operator, $col2="")
	{
		if (in_array($operator, QueryBuilder::$comparisonOperators))
			$this->join .= " and ".$this->sqlQuotes($col1).$operator.$this->sqlQuotes($col2);
		else
			$this->join .= " and ".$this->sqlQuotes($col1).'='.$this->sqlQuotes($operator);

		return $this;
	}

	public function orOn($col1, $operator, $col2="")
	{
		if (in_array($operator, QueryBuilder::$comparisonOperators))
			$this->join .= " or ".$this->sqlQuotes($col1).$operator.$this->sqlQuotes($col2);
		else
			$this->join .= " or ".$this->sqlQuotes($col1).'='.$this->sqlQuotes($operator);

		return $this;
	}

	public function join($table, $col1, $operator="", $col2="", $type="inner")
	{
		if ($col1 instanceof \Closure)
		{
			if ($operator) $type = $operator;

			$this->join .= " ".$type." join ".$this->sqlQuotes($table)." on (".$col1(new QueryBuilder()).")";
		}
		else if (in_array($operator, QueryBuilder::$comparisonOperators))
			$this->join .= " ".$type." join ".$this->sqlQuotes($table)." on ".$this->sqlQuotes($col1).$operator.$this->sqlQuotes($col2);
		else
			$this->join .= " ".$type." join ".$this->sqlQuotes($table)." on ".$this->sqlQuotes($col1)."=".$this->sqlQuotes($operator);

		return $this;
	}

	public function leftJoin($table, $col1, $operator="", $col2="")
	{
		$this->join($table, $col1, $operator, $col2, "left");

		return $this;
	}

	public function rightJoin($table, $col1, $operator="", $col2="")
	{
		$this->join($table, $col1, $operator, $col2, "right");

		return $this;
	}

	public function where(...$where)
	{
		if ($where[0] instanceof \Closure)
		{
			if ($this->where) $this->where .= " and ";
			$this->where .= "(".$where[0](new QueryBuilder())->query().")";
		}
		else
			$this->where .= $this->createComparison("and", $where);

		return $this;
	}

	public function whereIn(...$where)
	{
		if (count($where) == 2)
		{
			if ($this->where) $this->where .= " and ";
			$this->where .= $this->sqlQuotes($where[0])." in ('".implode("','",$where[1])."')";
		}

		return $this;
	}

	public function whereNotIn(...$where)
	{
		if (count($where) == 2)
		{
			if ($this->where) $this->where .= " and ";
			$this->where .= $this->sqlQuotes($where[0])." not in ('".implode("','",$where[1])."')";
		}

		return $this;
	}

	public function whereBetween(...$where)
	{
		if (count($where) == 2 && is_array($where[1]) && count($where[1]) == 2)
		{
			if ($this->where) $this->where .= " and ";
			$this->where .= $this->sqlQuotes($where[0])." between '".$where[1][0]."' and '".$where[1][1]."'";
		}

		return $this;
	}

	public function whereNotBetween(...$where)
	{
		if (count($where) == 2 && is_array($where[1]) && count($where[1]) == 2)
		{
			if ($this->where) $this->where .= " and ";
			$this->where .= $this->sqlQuotes($where[0])." not between '".$where[1][0]."' and '".$where[1][1]."'";
		}

		return $this;
	}

	public function whereDate(...$where)
	{
		if (count($where) == 2)
		{
			if ($this->where) $this->where .= " and ";
			$where[1] = str_replace('-', '', $where[1]);
			$this->where .= "concat(extract(YEAR_MONTH from ".$this->sqlQuotes($where[0])."),extract(DAY from ".$this->sqlQuotes($where[0])."))='".$where[1]."'";
		}
		else if (count($where) == 3 && in_array($where[1], QueryBuilder::$comparisonOperators))
		{
			if ($this->where) $this->where .= " and ";
			$where[2] = str_replace('-', '', $where[2]);
			$this->where .= "concat(extract(YEAR_MONTH from ".$this->sqlQuotes($where[0])."),extract(DAY from ".$this->sqlQuotes($where[0])."))".$where[1]."'".$where[2]."'";
		}

		return $this;
	}

	public function whereDay(...$where)
	{
		if (count($where) == 2)
		{
			if ($this->where) $this->where .= " and ";
			$this->where .= "extract(DAY from ".$this->sqlQuotes($where[0]).")='".$where[1]."'";
		}
		else if (count($where) == 3 && in_array($where[1], QueryBuilder::$comparisonOperators))
		{
			if ($this->where) $this->where .= " and ";
			$this->where .= "extract(DAY from ".$this->sqlQuotes($where[0]).")".$where[1]."'".$where[2]."'";
		}

		return $this;
	}

	public function whereMonth(...$where)
	{
		if (count($where) == 2)
		{
			if ($this->where) $this->where .= " and ";
			$this->where .= "extract(MONTH from ".$this->sqlQuotes($where[0]).")='".$where[1]."'";
		}
		else if (count($where) == 3 && in_array($where[1], QueryBuilder::$comparisonOperators))
		{
			if ($this->where) $this->where .= " and ";
			$this->where .= "extract(MONTH from ".$this->sqlQuotes($where[0]).")".$where[1]."'".$where[2]."'";
		}

		return $this;
	}

	public function whereYear(...$where)
	{
		if (count($where) == 2)
		{
			if ($this->where) $this->where .= " and ";
			$this->where .= "extract(YEAR from ".$this->sqlQuotes($where[0]).")='".$where[1]."'";
		}
		else if (count($where) == 3 && in_array($where[1], QueryBuilder::$comparisonOperators))
		{
			if ($this->where) $this->where .= " and ";
			$this->where .= "extract(YEAR from ".$this->sqlQuotes($where[0]).")".$where[1]."'".$where[2]."'";
		}

		return $this;
	}

	public function whereTime(...$where)
	{
		if (count($where) == 2)
		{
			if ($this->where) $this->where .= " and ";
			$where[1] = explode(':', $where[1]);
			foreach ($where[1] as &$t)
			{
				$matches = [];
				if (preg_match("/^[0]([0-9]{1})/", $t, $matches)) $t = $matches[1];
			}
			$where[1] = implode('', $where[1]);
			$this->where .= "concat(extract(HOUR from ".$this->sqlQuotes($where[0])."),extract(MINUTE_SECOND from ".$this->sqlQuotes($where[0])."))='".$where[1]."'";
		}
		else if (count($where) == 3 && in_array($where[1], QueryBuilder::$comparisonOperators))
		{
			if ($this->where) $this->where .= " and ";
			$where[2] = explode(':', $where[2]);
			foreach ($where[2] as &$t)
			{
				$matches = [];
				if (preg_match("/^[0]([0-9]{1})/", $t, $matches)) $t = $matches[1];
			}
			$where[2] = implode('', $where[2]);
			$this->where .= "concat(extract(HOUR from ".$this->sqlQuotes($where[0])."),extract(MINUTE_SECOND from ".$this->sqlQuotes($where[0])."))".$where[1]."'".$where[2]."'";
		}

		return $this;
	}

	public function whereNull(...$where)
	{
		if (count($where) == 1)
		{
			if ($this->where) $this->where .= " and ";
			$this->where .= $this->sqlQuotes($where[0])." is null";
		}

		return $this;
	}

	public function whereNotNull(...$where)
	{
		if (count($where) == 1)
		{
			if ($this->where) $this->where .= " and ";
			$this->where .= $this->sqlQuotes($where[0])." is not null";
		}

		return $this;
	}

	public function whereColumn(...$where)
	{
		if (count($where) == 2)
		{
			if ($this->where) $this->where .= " and ";
			$this->where .= $this->sqlQuotes($where[0])."=".$this->sqlQuotes($where[1]);
		}
		else if (count($where) == 3 && in_array($where[1], QueryBuilder::$comparisonOperators))
		{
			if ($this->where) $this->where .= " and ";
			$this->where .= $this->sqlQuotes($where[0]).$where[1].$this->sqlQuotes($where[2]);
		}

		return $this;
	}

	public function orWhere(...$where)
	{
		if ($where[0] instanceof \Closure)
		{
			if ($this->where) $this->where .= " or ";
			$this->where .= "(".$where[0](new QueryBuilder())->query().")";
		}
		else
			$this->where .= $this->createComparison("or", $where);

		return $this;
	}

	public function orWhereIn(...$where)
	{
		if (count($where) == 2)
		{
			if ($this->where) $this->where .= " or ";
			$this->where .= $this->sqlQuotes($where[0])." in ('".implode("','",$where[1])."')";
		}

		return $this;
	}

	public function orWhereNotIn(...$where)
	{
		if (count($where) == 2)
		{
			if ($this->where) $this->where .= " or ";
			$this->where .= $this->sqlQuotes($where[0])."not in ('".implode("','",$where[1])."')";
		}

		return $this;
	}

	public function orWhereBetween(...$where)
	{
		if (count($where) == 2 && is_array($where[1]) && count($where[1]) == 2)
		{
			if ($this->where) $this->where .= " or ";
			$this->where .= $this->sqlQuotes($where[0])." between '".$where[1][0]."' and '".$where[1][1]."'";
		}

		return $this;
	}

	public function orWhereNotBetween(...$where)
	{
		if (count($where) == 2 && is_array($where[1]) && count($where[1]) == 2)
		{
			if ($this->where) $this->where .= " or ";
			$this->where .= $this->sqlQuotes($where[0])." not between '".$where[1][0]."' and '".$where[1][1]."'";
		}

		return $this;
	}

	public function orWhereDate(...$where)
	{
		if (count($where) == 2)
		{
			if ($this->where) $this->where .= " or ";
			$where[1] = str_replace('-', '', $where[1]);
			$this->where .= "concat(extract(YEAR_MONTH from ".$this->sqlQuotes($where[0])."),extract(DAY from ".$this->sqlQuotes($where[0])."))='".$where[1]."'";
		}
		else if (count($where) == 3 && in_array($where[1], QueryBuilder::$comparisonOperators))
		{
			if ($this->where) $this->where .= " or ";
			$where[2] = str_replace('-', '', $where[2]);
			$this->where .= "concat(extract(YEAR_MONTH from ".$this->sqlQuotes($where[0])."),extract(DAY from ".$this->sqlQuotes($where[0])."))".$where[1]."'".$where[2]."'";
		}

		return $this;
	}

	public function orWhereDay(...$where)
	{
		if (count($where) == 2)
		{
			if ($this->where) $this->where .= " or ";
			$this->where .= "extract(DAY from ".$this->sqlQuotes($where[0]).")='".$where[1]."'";
		}
		else if (count($where) == 3 && in_array($where[1], QueryBuilder::$comparisonOperators))
		{
			if ($this->where) $this->where .= " or ";
			$this->where .= "extract(DAY from ".$this->sqlQuotes($where[0]).")".$where[1]."'".$where[2]."'";
		}

		return $this;
	}

	public function orWhereMonth(...$where)
	{
		if (count($where) == 2)
		{
			if ($this->where) $this->where .= " or ";
			$this->where .= "extract(MONTH from ".$this->sqlQuotes($where[0]).")='".$where[1]."'";
		}
		else if (count($where) == 3 && in_array($where[1], QueryBuilder::$comparisonOperators))
		{
			if ($this->where) $this->where .= " or ";
			$this->where .= "extract(MONTH from ".$this->sqlQuotes($where[0]).")".$where[1]."'".$where[2]."'";
		}

		return $this;
	}

	public function orWhereYear(...$where)
	{
		if (count($where) == 2)
		{
			if ($this->where) $this->where .= " or ";
			$this->where .= "extract(YEAR from ".$this->sqlQuotes($where[0]).")='".$where[1]."'";
		}
		else if (count($where) == 3 && in_array($where[1], QueryBuilder::$comparisonOperators))
		{
			if ($this->where) $this->where .= " or ";
			$this->where .= "extract(YEAR from ".$this->sqlQuotes($where[0]).")".$where[1]."'".$where[2]."'";
		}

		return $this;
	}

	public function orWhereTime(...$where)
	{
		if (count($where) == 2)
		{
			if ($this->where) $this->where .= " or ";
			$where[1] = explode(':', $where[1]);
			foreach ($where[1] as &$t)
			{
				$matches = [];
				if (preg_match("/^[0]([0-9]{1})/", $t, $matches)) $t = $matches[1];
			}
			$where[1] = implode('', $where[1]);
			$this->where .= "concat(extract(HOUR from ".$this->sqlQuotes($where[0])."),extract(MINUTE_SECOND from ".$this->sqlQuotes($where[0])."))='".$where[1]."'";
		}
		else if (count($where) == 3 && in_array($where[1], QueryBuilder::$comparisonOperators))
		{
			if ($this->where) $this->where .= " or ";
			$where[2] = explode(':', $where[2]);
			foreach ($where[2] as &$t)
			{
				$matches = [];
				if (preg_match("/^[0]([0-9]{1})/", $t, $matches)) $t = $matches[1];
			}
			$where[2] = implode('', $where[2]);
			$this->where .= "concat(extract(HOUR from ".$this->sqlQuotes($where[0])."),extract(MINUTE_SECOND from ".$this->sqlQuotes($where[0])."))".$where[1]."'".$where[2]."'";
		}

		return $this;
	}

	public function orWhereNull(...$where)
	{
		if (count($where) == 1)
		{
			if ($this->where) $this->where .= " or ";
			$this->where .= $this->sqlQuotes($where[0])." is null";
		}

		return $this;
	}

	public function orWhereNotNull(...$where)
	{
		if (count($where) == 1)
		{
			if ($this->where) $this->where .= " or ";
			$this->where .= $this->sqlQuotes($where[0])." is not null";
		}

		return $this;
	}

	public function orWhereColumn(...$where)
	{
		if (count($where) == 2)
		{
			if ($this->where) $this->where .= " or ";
			$this->where .= $this->sqlQuotes($where[0])."=".$this->sqlQuotes($where[1]);
		}
		else if (count($where) == 3 && in_array($where[1], QueryBuilder::$comparisonOperators))
		{
			if ($this->where) $this->where .= " or ";
			$this->where .= $this->sqlQuotes($where[0]).$where[1].$this->sqlQuotes($where[2]);
		}

		return $this;
	}

	public function groupBy(...$columns)
	{
		if (count($columns) == 1) $columns = explode(",", $columns[0]);

		foreach ($columns as $colname)
		{
			if ($this->groupBy) $this->groupBy .= ",";
			$this->groupBy .= $this->sqlQuotes($colname);
		}

		return $this;
	}

	public function having(...$having)
	{
		if ($having[0] instanceof \Closure)
		{
			if ($this->having) $this->having .= " and ";
			$this->having .= "(".$having[0](new QueryBuilder())->query().")";
		}
		else
		{
			$argsCount = count($having);
			if ($argsCount == 3)
			{
				if (in_array($having[1], QueryBuilder::$comparisonOperators))
				{
					if ($this->having) $this->having .= " and ";
					$this->having .= $this->sqlQuotes($having[0]).$having[1]."'".$having[2]."'";
				}
			}
			else if ($argsCount == 2)
			{
				if ($this->having) $this->having .= " and ";
				$this->having .= $this->sqlQuotes($having[0])."='".$having[1]."'";
			}
		}

		return $this;
	}

	public function orHaving(...$having)
	{
		if ($having[0] instanceof \Closure)
		{
			if ($this->having) $this->having .= " or ";
			$this->having .= "(".$having[0](new QueryBuilder())->query().")";
		}
		else
		{
			$argsCount = count($having);
			if ($argsCount == 3)
			{
				if (in_array($having[1], QueryBuilder::$comparisonOperators))
				{
					if ($this->having) $this->having .= " or ";
					$this->having .= $this->sqlQuotes($having[0]).$having[1]."'".$having[2]."'";
				}
			}
			else if ($argsCount == 2)
			{
				if ($this->having) $this->having .= " or ";
				$this->having .= $this->sqlQuotes($having[0])."='".$having[1]."'";
			}
		}

		return $this;
	}

	public function orderBy($column, $dir="asc")
	{
		if (is_array($column))
		{
			foreach ($column as $colname => $orderDir)
			{
				if ($this->orderBy) $this->orderBy .= ",";
				$this->orderBy .= $this->sqlQuotes($colname)." ".$orderDir;
			}
		}
		else
		{
			if ($this->orderBy) $this->orderBy .= ",";
			$this->orderBy .= $this->sqlQuotes($column)." ".$dir;
		}

		return $this;
	}

	public function latest($column="created_at")
	{
		return $this->orderBy($column, "desc");
	}

	public function oldest($column="created_at")
	{
		return $this->orderBy($column, "asc");
	}

	public function skip($amount)
	{
		$this->skip = intval($amount);

		return $this;
	}

	public function take($amount)
	{
		$this->take = intval($amount);

		return $this;
	}

	public function distinct()
	{
		$this->distinct = true;

		return $this;
	}

	public function cache()
	{
		$this->cache = true;

		return $this;
	}

	public function nocache()
	{
		$this->nocache = true;

		return $this;
	}

	public function union(QueryBuilder $query)
	{
		return $this->query()." union ".$query->query();
	}

	public function intersect(QueryBuilder $query)
	{
		return $this->query()." intersect ".$query->query();
	}

	public function query()
	{
		$query = "";

		// select
		if ($this->select && $this->from)
		{
			$query = "select ";
			if ($this->nocache) $query .= "sql_no_cache ";
			else if ($this->cache) $query .= "sql_cache ";
			if ($this->distinct) $query .= "distinct ";

			$query .= $this->select." from ".$this->from.$this->join;
		}
		// insert
		if ($this->from && $this->insert) $query = "insert into ".$this->from." ".$this->insert;
		// update
		if ($this->from && $this->update && !$this->insertOrUpdate) $query = "update ".$this->from." set ".$this->update;
		// delete
		if ($this->from && $this->delete)
		{
		 $query = "delete from ".$this->from;
		}
		if ($this->from && $this->insertOrUpdate && $query && $this->update)
		{
			$query .= " on duplicate key update ".$this->update;
		}

		// table() shortcut
		if (!$query && $this->from)
		{
			$query = "select ";
			if ($this->nocache) $query .= "sql_no_cache ";
			else if ($this->cache) $query .= "sql_cache ";
			if ($this->distinct) $query .= "distinct ";

			$query .= "* from ".$this->from.$this->join;
		}
		if (!$query && $this->join)
		{
			$this->join = preg_replace("/^ and (.+)/", "$1", $this->join);
			$this->join = preg_replace("/^ or (.+)/", "$1", $this->join);
			$query = $this->join;
		}

		// where
		if (!$this->insertOrUpdate && $this->where)
		{
			if ($query) $query .=  " where ";
			$query .= $this->where;
		}
		// group by
		if ($this->groupBy) $query .= " group by ".$this->groupBy;
		// having
		if ($this->having)
		{
			if ($query) $query .= " having ";
			$query .= $this->having;
		}
		// orderBy
		if ($this->orderBy) $query .= " order by ".$this->orderBy;
		// limit
		if ($this->skip || $this->take)
		{
			if ($this->skip && $this->take) $query .= " limit ".$this->take.",".$this->skip;
			else if($this->take) $query .= " limit ".$this->take;
		}

		return $query;
	}

	public function exists()
	{
		$query = "select exists(".$this.")";

		$DB = DB::get();
		$res = $DB->fetch($query);

		return (bool)$res;
	}

	public function doesntExists()
	{
		return !$this->exists();
	}

	protected function createComparison($andOr, $where)
	{
		$argsCount = count($where);
		if ($argsCount == 3)
		{
			if (in_array($where[1], QueryBuilder::$comparisonOperators))
			{
				if ($this->where) $this->where .= " $andOr ";
				return $this->sqlQuotes($where[0]).$where[1]."'".$where[2]."'";
			}
		}
		else if ($argsCount == 2)
		{
			if ($this->where) $this->where .= " $andOr ";
			return $this->sqlQuotes($where[0])."='".$where[1]."'";
		}

		return '';
	}

	protected function sqlQuotes($str)
	{
		$str = trim(preg_replace("/  +/"," ", $str));
		$str = preg_replace("/^([A-Za-z0-9_\-]+)[\.]([A-Za-z0-9_\-]+)$/","`$1`.`$2`", $str);
		$str = preg_replace("/^([A-Za-z0-9_\-]+)$/","`$1`", $str);
		$str = preg_replace("/^([A-Za-z0-9_\-]+) AS (.+)$/i","`$1` as `$2`", $str);
		$str = preg_replace("/^([A-Za-z0-9_\-]+) (.+)$/i","`$1` `$2`", $str);

		return $str;
	}
}

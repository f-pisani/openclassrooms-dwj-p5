<?php
namespace App;

/***********************************************************************************************************************
 * Class Model
 *     Implements : JsonSerializable, ArrayAccess, Iterator, Serializable
 *     Traits : Jsonable, Arrayable
 *
 *     public function jsonSerialize()
 *     public function __debugInfo()
 *     public function toJson($options=0)
 *     public function toArray()
 *     public function offsetSet($offset, $value)
 *     public function offsetExists($offset)
 *     public function offsetUnset($offset)
 *     public function offsetGet($offset)
 *     public function rewind()
 *     public function current()
 *     public function key()
 *     public function next()
 *     public function valid()
 *     public function serialize()
 *     public function unserialize($datas)
 *     public static function creating(\Closure $function)
 *     public static function created(\Closure $function)
 *     public static function updating(\Closure $function)
 *     public static function updated(\Closure $function)
 *     public static function deleting(\Closure $function)
 *     public static function deleted(\Closure $function)
 *     public static function restoring(\Closure $function)
 *     public static function restored(\Closure $function)
 *     public static function observer(ModelObserver $observer)
 *     public function __set($fieldname, $value)
 *     public function __get($fieldname)
 *     public function reset()
 *     public function create()
 *     public function load($id, $activeOnly=true)
 *     public function loadFromArray($datas)
 *     public function loadAsArray($ids=[], $activeOnly=true)
 *     public function save()
 *     public function delete()
 *     public function restore()
 *     public function fill($fields)
 *     public function associate(Model $Model)
 *     public function associatePivot(Model $Model)
 *     public function dissociate($Model)
 *     public function dissociatePivot($Model)
 *     public function with($relation, $activeOnly=1)
 *     public function withPivot($relation, $activeOnly=1)
 *     public function isPivotRelationActive($Model)
 *     public static function table()
 *     public static function pk()
 *     public static function timestamps()
 *     public static function softDelete()
 *     public function pkValue()
 *     public function getModifiedFields()
 *     public function getGuardedFields()
 *     public function getCustomFields()
 *     public function isFresh()
 *     public function isModified()
 *     public function isDeleted()
 *     public function isSoftDeleted()
 *     public function wasRecentlyCreated()
 *     public function exists()
 *     protected function isInsertable()
 *     protected function isUpdatable()
 *     protected function isDeletable()
 *     protected function isRestorable()
 *     protected function cleanRelationDuplicates()
 *     protected function prepareInsert($mode=0)
 *     protected function prepareUpdate($mode=0)
 *     protected function prepareDelete($mode=0)
 *     protected function prepareRestore($mode=0)
 *
 * Model implementation.
 */
class Model implements \JsonSerializable, \ArrayAccess, \Iterator, \Serializable
{
	use traits\Jsonable, traits\Arrayable;

	protected $db = null;
	protected static $table = '';
	protected static $primaryKey = 'id';

	protected static $timestamps = true; // Automatically manage timestamps fields (created_at, updated_at, [deleted_at if soft delete is enabled])
	protected static $softDelete = false; // Use field 'active' as state management for model deletion instead of deleting model from database

	protected static $with = []; // Assoc array with [RelationName => ['class' => ModelClass, 'fk' => RelationFk]] (ex: ['Phones' => ['class' => 'Models\Phone', 'fk' => 'user_id']])
	protected static $withPivot = []; // Assoc array with [RelationName => ['class' => ModelClass, 'fk' => RelationFk]] (ex: ['Phones' => ['class' => 'Models\Phone', 'fk' => 'user_id']])
	protected static $relations = []; // Assoc array with [ModelName => internal_fk] (ex: ['User' => 'user_id'])
	protected static $relationsPivot = []; // Assoc array with [RelationName => ['class' => ModelClass, 'fk' => RelationFk]] (ex: ['Phones' => ['class' => 'Models\Phone', 'fk' => 'user_id']])

	protected static $accessors = []; // Accessors methods to call on fieldname __get() : getFieldname($value) -> return value
	protected static $mutators = []; // Mutators methods to call on fieldname __set() : setFieldname($value) -> return value
	protected static $callbacks = []; // Callbacks on model life cycle (creating & created; updating & updated; deleting & deleted; restoring & restored)
	protected static $observers = []; // Observers for model life cycle

	protected $fields = []; // Available fields from the database
	protected $guarded = []; // List of fields that can't be moddified using __set() (nb: internal methods can still access these fields)
	protected $customFields = []; // Custom fields availables during script execution

	protected $isFresh = true; // True: Model is not created or loaded; False otherwise
	protected $isModified = false; // True: Model attributes has been modified; False otherwise
	protected $isDeleted = false; // True: Model was deleted; False otherwise
	protected $wasRecentlyCreated = false; // True: Model was created during script execution; False otherwise
	protected $exists = false; // True: Model exists in database; False otherwise

	protected $iterator = 0;
	protected $iteratorMap = [];

	// JsonSerializable
	public function jsonSerialize()
	{
		$vars = get_object_vars($this);
		foreach ($vars as $k => &$v)
		{
			if (is_array($v))
			{
				foreach ($v as $k1 => &$v1)
				{
					if ($v1 instanceof ModelField)
					{
						$vars[$k][$k1] = $v1->get();
					}
				}
			}
			else if ($v instanceof Database) unset($vars[$k]);
		}

		return $vars;
	}

	// var_dump
	public function __debugInfo(){ return $this->jsonSerialize(); }
	// Jsonable
	public function toJson($options=0){ return json_encode($this, $options); }
	//Arrayable
	public function toArray() { return $this->jsonSerialize(); }
	// ArrayAccess
	public function offsetSet($offset, $value) { $this->$offset = $value; }
    public function offsetExists($offset) { return (array_key_exists($offset, $this->fields) || array_key_exists($offset, $this->customFields)); }
    public function offsetUnset($offset) { if (array_key_exists($offset, $this->customFields)) unset($this->customFields[$offset]); }
    public function offsetGet($offset) { return ($this->offsetExists($offset)) ? $this->$offset : null; }
	// Iterator
	public function rewind() { $this->iterator = 0; }
	public function current() { $fieldname = $this->iteratorMap[$this->iterator]; return $this->$fieldname; }
	public function key() { return $this->iteratorMap[$this->iterator]; }
	public function next() { ++$this->iterator; }
	public function valid() { return (array_key_exists($this->iterator, $this->iteratorMap) && (array_key_exists($this->iteratorMap[$this->iterator], $this->fields) || array_key_exists($this->iteratorMap[$this->iterator], $this->customFields))); }
	// Serializable
	public function serialize()
	{
        return serialize([$this->fields,
						  $this->guarded,
						  $this->customFields,
						  $this->isFresh,
						  $this->isModified,
						  $this->isDeleted,
						  $this->wasRecentlyCreated,
						  $this->exists,
						  $this->iterator,
						  $this->iteratorMap,
						]);
    }
    public function unserialize($datas)
	{
		$this->db = DB::get(); // Database

		list(
            $this->fields,
			$this->guarded,
			$this->customFields,
			$this->isFresh,
			$this->isModified,
			$this->isDeleted,
			$this->wasRecentlyCreated,
			$this->exists,
			$this->iterator,
			$this->iteratorMap
        ) = unserialize($datas);

		// Callbacks
		if (!array_key_exists(static::class, self::$callbacks)) self::$callbacks[static::class] = [];
		$modelLifecycle = ['creating',  'created',
					  	   'updating',  'updated',
					  	   'deleting',  'deleted',
					  	   'restoring', 'restored'];

		foreach ($modelLifecycle as $step)
		{
			$methodCallback = 'on'.ucfirst($step);
			if (!array_key_exists($step, self::$callbacks[static::class])) self::$callbacks[static::class][$step] = [];

			if (method_exists(static::class, $methodCallback)) self::$callbacks[static::class][$step][] = $methodCallback;
		}

		// Observers
		self::$observers[static::class] = [];
    }

	/*******************************************************************************************************************
	 *  __construct()
	 * Ctor
	 */
	public function __construct()
	{
		$this->db = DB::get(); // Database

		// Fields
		foreach ($this->fields as $k => $field)
		{
			if (is_array($field))
			{
				if (!array_key_exists(1, $field)) $field[1] = null; // Default typecast is null
				if (!array_key_exists(2, $field)) $field[2] = null; // Default database type is null

				unset($this->fields[$k]);
				$this->fields[$field[0]] = new ModelField($field[0], null, $field[1], $field[2]);
				$fieldname = $field[0];
			}
			else if ($field instanceof App\ModelField)
			{
				$this->fields[$field->name()] = $field; // Assoc
				$fieldname = $field->name();
			}

			if ($fieldname) $this->iteratorMap[] = $fieldname;

			unset($fieldname);
		}

		// Guarded fields
		if (!in_array(static::pk(), $this->guarded)) $this->guarded[] = static::pk();
		if (static::timestamps() && !in_array(static::pk(), ['created_at', 'updated_at'])) $this->guarded = array_merge($this->guarded, ['created_at', 'updated_at']);
		if (static::softDelete()) $this->guarded[] = 'active';
		if (static::softDelete() && static::timestamps()) $this->guarded[] = 'deleted_at';

		// Callbacks
		if (!array_key_exists(static::class, self::$callbacks)) self::$callbacks[static::class] = [];
		$modelLifecycle = ['creating',  'created',
					  	   'updating',  'updated',
					  	   'deleting',  'deleted',
					  	   'restoring', 'restored'];

		foreach ($modelLifecycle as $step)
		{
			$methodCallback = 'on'.ucfirst($step);
			if (!array_key_exists($step, self::$callbacks[static::class])) self::$callbacks[static::class][$step] = [];

			if (method_exists(static::class, $methodCallback)) self::$callbacks[static::class][$step][] = $methodCallback;
		}

		// Observers
		self::$observers[static::class] = [];
	}

	public static function creating(\Closure $function){  self::$callbacks[static::class]['creating'][]  = $function; }
	public static function created(\Closure $function){   self::$callbacks[static::class]['created'][]   = $function; }
	public static function updating(\Closure $function){  self::$callbacks[static::class]['updating'][]  = $function; }
	public static function updated(\Closure $function){   self::$callbacks[static::class]['updated'][]   = $function; }
	public static function deleting(\Closure $function){  self::$callbacks[static::class]['deleting'][]  = $function; }
	public static function deleted(\Closure $function){   self::$callbacks[static::class]['deleted'][]   = $function; }
	public static function restoring(\Closure $function){ self::$callbacks[static::class]['restoring'][] = $function; }
	public static function restored(\Closure $function){  self::$callbacks[static::class]['restored'][]  = $function; }
	public static function observer(ModelObserver $observer){ self::$observers[static::class][] = $observer; }

	/*******************************************************************************************************************
	 * __set($fieldname, $value)
	 *     $fieldname: fieldname
	 *	   $value: field value
	 * Set $fieldname to $value
	 */
	public function __set($fieldname, $value)
    {
		$guarded = $this->getGuardedFields();

		if ((!$guarded || !in_array($fieldname, $guarded)))
		{
			if (array_key_exists($fieldname, $this->fields))
			{
				/***********************************************************************************************************
				 * Mutators
				 */
				if (!array_key_exists($fieldname, static::$mutators))
				{
					$matches = [];
					$mutator = "set";
					if (preg_match_all("/([a-zA-Z0-9]+)(?:_?)/", $fieldname, $matches))
					{
						if ($matches[1])
						{
							foreach ($matches[1] as $v)
								$mutator .= ucfirst($v);
						}
					}

					if (method_exists(static::class, $mutator)) static::$mutators[$fieldname] = $mutator;
					else static::$mutators[$fieldname] = null;
				}

				if (method_exists(static::class, static::$mutators[$fieldname]))
				{
					$mutator = static::$mutators[$fieldname];
					$value = $this->$mutator($value);
				}

				$this->fields[$fieldname]->set($value);
				$this->isModified = true;
			}
			else
				$this->customFields[$fieldname] = $value;
		}
    }

	/*******************************************************************************************************************
	 * __get($fieldname)
	 *     $fieldname: fieldname
	 * Return $fieldname value
	 */
	public function __get($fieldname)
    {
		if (array_key_exists($fieldname, $this->fields))
		{
			$value = $this->fields[$fieldname]->get();

			/***********************************************************************************************************
			 * Accessors
			 */
			if (!array_key_exists($fieldname, static::$accessors))
			{
				$matches = [];
				$accessor = "get";
				if (preg_match_all("/([a-zA-Z0-9]+)(?:_?)/", $fieldname, $matches))
				{
					if ($matches[1])
					{
						foreach ($matches[1] as $v)
							$accessor .= ucfirst($v);
					}
				}

				if (method_exists(static::class, $accessor)) static::$accessors[$fieldname] = $accessor;
				else static::$accessors[$fieldname] = null;
			}

			if (method_exists(static::class, static::$accessors[$fieldname]))
			{
				$accessor = static::$accessors[$fieldname];
				return $this->$accessor($value);
			}

			/***********************************************************************************************************
			 * Default accessors (cast & return)
			 */
			return $value;
		}
		else if (array_key_exists($fieldname, $this->customFields))
			return $this->customFields[$fieldname];

		return null;
    }

	/*******************************************************************************************************************
	 * reset()
	 * Reset model data (fresh)
	 */
	public function reset()
	{
		// Reset fields
		foreach ($this->fields as $field) $field->set(null);
		$this->customFields = [];

		// Reset internal flags
		$this->isFresh = true;
		$this->isModified = false;
		$this->isDeleted = false;
		$this->wasRecentlyCreated = false;
		$this->exists = false;
	}

	/*******************************************************************************************************************
	 * create()
	 * Insert model in database
	 * Return true if success false otherwise
	 */
	public function create()
	{
		if ($this->isInsertable())
		{
			// Observers : Creating
			foreach (self::$observers[static::class] as $observer)
			{
				if (!$observer->creating($this)) return false;
			}

			// Callbacks : Creating
			foreach (self::$callbacks[static::class]['creating'] as $callback)
			{
				if (is_string($callback) && !$this->$callback()) return false;
				else if (is_callable($callback) && !$callback($this)) return false;
			}

			$this->prepareInsert();

			$datas = [];
			foreach ($this->fields as $field)
			{
				if ($field->name() != static::pk()) $datas[$field->name()] = $field->toDatabase();
			}

			$Query = new QueryBuilder();
			$Query->table(static::table())->insert($datas);

			$result = $this->db->query($Query);
			if($result)
			{
				$this->fields[static::pk()]->set($this->db->insert_id());
				foreach ($this->fields as $field) $field->setModified(false);

				// Update internal flags
				$this->isFresh = false;
				$this->isModified = false;
				$this->isDeleted = false;
				$this->wasRecentlyCreated = true;
				$this->exists = true;

				// Observers : Created
				foreach (self::$observers[static::class] as $observer) $observer->created($this);

				// Callbacks : Created
				foreach (self::$callbacks[static::class]['created'] as $callback)
				{
					if (is_string($callback)) $this->$callback();
					else if (is_callable($callback)) $callback($this);
				}
			}
			else $this->prepareInsert(1); // Reverse

			return ($result) ? true : false;
		}

		return false;
	}

	/*******************************************************************************************************************
	 * load($id, $activeOnly=true)
	 *     $id: id to load
	 *	   $activeOnly: used in softDelete mode only; if true it will load only $id if is not deleted
	 * Initialize model with value for $id
	 * Return true if success false otherwise
	 */
	public function load($id, $activeOnly=true)
	{
		$Query = new QueryBuilder();
		$Query->table(static::table())->where(static::pk(), DB::esc($id));
		if (static::softDelete() && $activeOnly) $Query->where('active', 1);

		$datas = $this->db->fetchmono($Query, DB::FETCH_ASSOC);

		return $this->loadFromArray($datas);
	}

	/*******************************************************************************************************************
	 * loadFromArray($datas)
	 *     $datas: Assoc array with fieldname => value
	 * Initialize model with value of $datas
	 */
	public function loadFromArray($datas)
	{
		$this->reset();

		foreach ($datas as $fieldname => $value)
		{
			if (array_key_exists($fieldname, $this->fields))
			{
				$this->fields[$fieldname]->set($value);
				$this->fields[$fieldname]->setModified(false);
			}
		}

		if ($this->fields[static::pk()]->raw())
		{
			// Update internal flags
			$this->isFresh = false;
			$this->isModified = false;
			if (static::softDelete() && !$this->fields['active']->raw()) $this->isDeleted = true;
			$this->wasRecentlyCreated = false;
			$this->exists = true;
		}

		return $this;
	}

	/*******************************************************************************************************************
	 * loadAsArray($ids=[], $activeOnly=true)
	 *     $ids: list of ids to load; could be an array or a comma separated list of ids
	 *	   $activeOnly: used in softDelete mode only; if true it will load only model if is not softdeleted
	 * Load multiple into an array models and return it
	 */
	public function loadAsArray($ids=[], $activeOnly=true)
	{
		if (is_string($ids)) $ids = explode(',', $ids);

		$Query = new QueryBuilder();
		$Query->table(static::table());
		if ($ids) $Query->whereIn(static::pk(), $ids);
		if (static::softDelete() && $activeOnly) $Query->where('active', 1);

		$RawModels = $this->db->fetchall($Query, DB::FETCH_ASSOC);
		$Models = [];
		foreach ($RawModels as $datas)
		{
			$class = get_class($this);
			$Model = new $class();
			if ($Model->loadFromArray($datas)) $Models[] = $Model;
		}

		return $Models;
	}

	/*******************************************************************************************************************
	 * save()
	 * Update model in database
	 * Return true if success false otherwise
	 */
	public function save()
	{
		if ($this->isUpdatable())
		{
			// Observers : Updating
			foreach (self::$observers[static::class] as $observer)
			{
				if (!$observer->updating($this)) return false;
			}

			// Callbacks : Updating
			foreach (self::$callbacks[static::class]['updating'] as $callback)
			{
				if (is_string($callback) && !$this->$callback()) return false;
				else if (is_callable($callback) && !$callback($this)) return false;
			}

			$this->prepareUpdate();

			$datas = [];
			foreach ($this->fields as $field)
			{
				if (static::pk() != $field->name() && $field->isModified())
				{
					$datas[$field->name()] = $field->toDatabase();
				}
			}

			$Query = new QueryBuilder();
			$Query->table(static::table())->update($datas)->where(static::pk(), $this->fields[static::pk()]->toDatabase());

			$result = $this->db->query($Query);
			if($result)
			{
				foreach ($this->fields as $field) $field->setModified(false);

				// Update internal flags
				$this->isModified = false;

				// Observers : Updated
				foreach (self::$observers[static::class] as $observer) $observer->updated($this);

				// Callbacks : Updated
				foreach (self::$callbacks[static::class]['updated'] as $callback)
				{
					if (is_string($callback)) $this->$callback();
					else if (is_callable($callback)) $callback($this);
				}
			}
			else
				$this->prepareUpdate(1); // Reverse

			return ($result) ? true : false;
		}
		else $this->create();

		return false;
	}

	/*******************************************************************************************************************
	 * delete()
	 * Delete current model from database; if softDelete is enabled, model will be deactivated
	 * Return true if success false otherwise
	 */
	public function delete()
	{
		if ($this->isDeletable())
		{
			// Observers : Deleting
			foreach (self::$observers[static::class] as $observer)
			{
				if (!$observer->deleting($this)) return false;
			}

			// Callbacks : Deleting
			foreach (self::$callbacks[static::class]['deleting'] as $callback)
			{
				if (is_string($callback) && !$this->$callback()) return false;
				else if (is_callable($callback) && !$callback($this)) return false;
			}

			$this->prepareDelete();

			$Query = new QueryBuilder();
			$Query->table(static::table());
			$datas = [];
			if (static::softDelete())
			{
				$datas['active'] = $this->fields['active']->toDatabase();
				if (static::timestamps()) $datas['deleted_at'] = $this->fields['deleted_at']->toDatabase();

				$Query->update($datas);
			}
			else $Query->delete();

			$Query->where(static::pk(), $this->fields[static::pk()]->toDatabase());

			$result = $this->db->query($Query);
			if($result)
			{
				$this->fields['active']->setModified(false);
				$this->fields['deleted_at']->setModified(false);

				// Update internal flags
				$this->isFresh = false;
				$this->isModified = false;
				$this->isDeleted = true;
				$this->exists = (static::softDelete()) ? true : false;

				// Observers : Deleted
				foreach (self::$observers[static::class] as $observer) $observer->deleted($this);

				// Callbacks : Deleted
				foreach (self::$callbacks[static::class]['deleted'] as $callback)
				{
					if (is_string($callback)) $this->$callback();
					else if (is_callable($callback)) $callback($this);
				}
			}
			else
				$this->prepareDelete(1); // Reverse

			return ($result) ? true : false;
		}

		return false;
	}

	/*******************************************************************************************************************
	 * restore()
	 * If model is in softDelete mode and is actually deleted, it will restore it
	 * Return true if success false otherwise
	 */
	public function restore()
	{
		if ($this->isRestorable())
		{
			// Observers : Restoring
			foreach (self::$observers[static::class] as $observer)
			{
				if (!$observer->restoring($this)) return false;
			}

			// Callbacks : Restoring
			foreach (self::$callbacks[static::class]['restoring'] as $callback)
			{
				if (is_string($callback) && !$this->$callback()) return false;
				else if (is_callable($callback) && !$callback($this)) return false;
			}

			$this->prepareRestore();

			$Query = new QueryBuilder();
			$Query->table(static::table())->update(['active' => $this->fields['active']->toDatabase()]);
			if (static::timestamps()) $Query->update(['deleted_at' => null]);
			$Query->where(static::pk(), $this->fields[static::pk()]->toDatabase());

			$result = $this->db->query($Query);
			if($result)
			{
				$this->fields['active']->setModified(false);
				$this->fields['deleted_at']->setModified(false);

				// Update internal flags
				$this->isFresh = false;
				$this->isModified = false;
				$this->isDeleted = false;
				$this->exists = true;

				// Observers : Restored
				foreach (self::$observers[static::class] as $observer) $observer->restored($this);

				// Callbacks : Restored
				foreach (self::$callbacks[static::class]['restored'] as $callback)
				{
					if (is_string($callback)) $this->$callback();
					else if (is_callable($callback)) $callback($this);
				}
			}
			else
				$this->prepareRestore(1); // Reverse

			return ($result) ? true : false;
		}

		return false;
	}

	/*******************************************************************************************************************
	 * fill($fields)
	 * Fill model attributes using $fields (fieldname => value)
	 */
	public function fill($fields)
	{
		if (!$this->isDeleted())
		{
			foreach($fields as $fieldname => $value) $this->$fieldname = $value;
		}

		return $this;
	}

	/*******************************************************************************************************************
	 * associate(Model $Model)
	 *     $Model: Associate a $Model with the current model
	 * Save current model with $Model primarykey as foreignkey for the relation
	 */
	public function associate(Model $Model)
	{
		if ($this->exists() && $Model->exists())
		{
			$class = get_class($Model);
			foreach (static::$relations as $relationName => $relation)
			{
				if ($relation['class'] == $class)
				{
					$fk = $relation['fk'];
					$Model->$fk = $this->pkValue();

					if($Model->save())
					{
						if (is_array($this->$relationName)) $this->$relationName = array_merge($this->$relationName, [$Model]);
						else
						{
							$this->$relationName = [$Model];
							if (!in_array($relationName, $this->iteratorMap)) $this->iteratorMap[] = $relationName;
						}

						$this->cleanRelationDuplicates();
					}

					break;
				}
			}
		}

		return $this;
	}

	/*******************************************************************************************************************
	 * associatePivot(Model $Model)
	 *     $Model: Associate a $Model with the current model using pivot table
	 * Save current model with $Model primarykey as foreignkey for the relation
	 */
	public function associatePivot(Model $Model)
	{
		if ($this->exists() && $Model->exists())
		{
			$class = get_class($Model);
			foreach (static::$relationsPivot as $relationName => $pivot)
			{
				if ($pivot['class'] == $class)
				{
					$keys = [$pivot['pk'] => $this->pkValue(), $pivot['fk'] => $Model->pkValue()];
					$datas = [];
					if (array_key_exists('timestamps', $pivot) && $pivot['timestamps']) $datas = array_merge($datas, ['created_at' => date("Y-m-d H:i:s"), 'updated_at' => date("Y-m-d H:i:s")]);
					if (array_key_exists('softDelete', $pivot) && $pivot['softDelete']) $datas = array_merge($datas, ['active' => '1']);
					if (array_key_exists('softDelete', $pivot) && $pivot['softDelete'] &&
						array_key_exists('timestamps', $pivot) && $pivot['timestamps']) $datas = array_merge($datas, ['deleted_at' => null]);

					$QueryBuilder = (new QueryBuilder)->table($pivot['table'])->insertOrUpdate($keys, $datas);

					if ($this->db->query($QueryBuilder))
					{
						if (is_array($this->$relationName)) $this->$relationName = array_merge($this->$relationName, [$Model]);
						else
						{
							$this->$relationName = [$Model];
							if (!in_array($relationName, $this->iteratorMap)) $this->iteratorMap[] = $relationName;
						}

						$this->cleanRelationDuplicates();
					}

					break;
				}
			}
		}

		return $this;
	}

	/*******************************************************************************************************************
	 * dissociate($Model)
	 *     $Model: Name of the relation to dissociate with the current model
	 * Remove a relation between two models
	 */
	public function dissociate($Model)
	{

		if ($this->exists() && $Model->exists())
		{
			$class = get_class($Model);
			foreach (static::$relations as $relationName => $relation)
			{
				if ($relation['class'] == $class)
				{
					$fk = $relation['fk'];
					$Model->$fk = null;
					if ($Model->save())
					{
						if (is_array($this->$relationName))
						{
							$this->$relationName = array_filter($this->$relationName, function($obj) use($Model){
								return ($obj->pkValue() !== $Model->pkValue());
							});
						}
					}

					break;
				}
			}
		}

		return $this;
	}

	/*******************************************************************************************************************
	 * dissociatePivot($relation)
	 *     $relation: Name of the relation to dissociate with the current model using pivot
	 * Remove a relation between two models
	 */
	public function dissociatePivot($Model)
	{
		if ($this->exists() && $Model->exists())
		{
			$class = get_class($Model);
			foreach (static::$relationsPivot as $relationName => $pivot)
			{
				if ($pivot['class'] == $class)
				{
					$QueryBuilder = (new QueryBuilder)->table($pivot['table']);
					if ((array_key_exists('softDelete', $pivot) && !$pivot['softDelete']) || (!array_key_exists('softDelete', $pivot)))
						$QueryBuilder->delete()->where($pivot['pk'], $this->pkValue())->where($pivot['fk'], $Model->pkValue());
					else
					{
						$datas = [];
						if (array_key_exists('timestamps', $pivot) && $pivot['timestamps']) $datas = array_merge($datas, ['updated_at' => date("Y-m-d H:i:s")]);
						if (array_key_exists('softDelete', $pivot) && $pivot['softDelete']) $datas = array_merge($datas, ['active' => '0']);
						if (array_key_exists('softDelete', $pivot) && $pivot['softDelete'] &&
							array_key_exists('timestamps', $pivot) && $pivot['timestamps']) $datas = array_merge($datas, ['deleted_at' => date("Y-m-d H:i:s")]);

						$QueryBuilder->update($datas)->where($pivot['pk'], $this->pkValue())->where($pivot['fk'], $Model->pkValue());
					}

					if($this->db->query($QueryBuilder))
					{
						if (is_array($this->$relationName))
						{
							$this->$relationName = array_filter($this->$relationName, function($obj) use($Model){
								return ($obj->pkValue() !== $Model->pkValue());
							});
						}
					}

					break;
				}
			}
		}

		return $this;
	}

	/*******************************************************************************************************************
	 * with($relation, $activeOnly=1)
	 *     $relation: Name of the relation to load
	 *	   $activeOnly: For models with softdelete mode enabled. (1=load active models only; 0=load every models even softdeleted)
	 * Store models into $Model->$relation (ex: $User->with('Phones') will load Phones into $User->Phones as an array of Phone models)
	 */
	public function with($relation, $activeOnly=1)
	{
		if (!is_array($relation)) $withs = explode(',', $relation);
		else $withs = $relation;

		foreach ($withs as $relationName)
		{
			if ($this->exists() && array_key_exists($relationName, static::$relations))
			{
				$ModelType = new static::$relations[$relationName]['class']();

				$Query = $ModelType->table($ModelType::table())->select($ModelType::pk())->distinct()->where(static::$relations[$relationName]['fk'], $this->pkValue());
				$Models = $this->db->fetch($Query, DB::FETCH_ASSOC);

				if (count($Models)) $Models = $ModelType->loadAsArray($Models, $activeOnly);

				if (is_array($this->$relationName)) $this->$relationName = array_merge($this->$relationName, $Models);
				else $this->$relationName = $Models;

				if (!in_array($relation, $this->iteratorMap)) $this->iteratorMap[] = $relationName;
			}

			$this->cleanRelationDuplicates();
		}
		return $this;
	}

	/*******************************************************************************************************************
	 * withPivot($relation, $activeOnly=1)
	 *     $relation: Name of the relation to load from a pivot relation
	 *	   $activeOnly: For models with softdelete mode enabled. (1=load active models only; 0=load every models even softdeleted)
	 * Store models into $Model->$relation (ex: $User->with('Phones') will load Phones into $User->Phones as an array of Phone models)
	 */
	public function withPivot($relation, $activeOnly=1)
	{
		if (!is_array($relation)) $withs = explode(',', $relation);
		else $withs = $relation;

		foreach ($withs as $relationName)
		{
			if ($this->exists() && array_key_exists($relationName, static::$relationsPivot))
			{
				$pivot = static::$relationsPivot[$relationName];
				$ModelType = new $pivot['class']();

				$Query = (new QueryBuilder)->table($pivot['table'])->select($pivot['fk']." ".$ModelType::pk())->distinct()->where($pivot['pk'], $this->pkValue());
				if (array_key_exists('softDelete', $pivot) && $activeOnly) $Query->where('active', 1);

				$Models = $this->db->fetch($Query, DB::FETCH_ASSOC);

				if (count($Models)) $Models = $ModelType->loadAsArray($Models, $activeOnly);

				if (is_array($this->$relationName)) $this->$relationName = array_merge($this->$relationName, $Models);
				else $this->$relationName = $Models;

				if (!in_array($relationName, $this->iteratorMap)) $this->iteratorMap[] = $relationName;
			}
		}

		$this->cleanRelationDuplicates();

		return $this;
	}

	/*******************************************************************************************************************
	 * isPivotRelationActive($Model)
	 * Return true whenever an active pivot relation exists between current Model and $Model; False otherwise
	 */
	public function isPivotRelationActive($Model)
	{
		if ($this->exists() && $Model->exists())
		{
			$class = get_class($Model);
			foreach (static::$relationsPivot as $relationName => $pivot)
			{
				if ($pivot['class'] == $class)
				{
					$Query = (new QueryBuilder)->table($pivot['table'])->count($pivot['fk'], $Model::pk())->distinct()->where($pivot['pk'], $this->pkValue());
					if (array_key_exists('softDelete', $pivot) && $pivot['softDelete']) $Query->where('active', 1);

					return (bool)$this->db->fetch($Query, DB::FETCH_ASSOC);
				}
			}
		}

		return false;
	}

	// Model getters
	public static function table(){ return static::$table; }
	public static function pk(){ return static::$primaryKey; }
	public static function timestamps(){ return static::$timestamps; }
	public static function softDelete(){ return static::$softDelete; }
	public function pkValue(){ $pk = static::pk(); return $this->$pk; }
	public function getModifiedFields() { return array_filter($this->fields, function($field){ if ($field->isModified()) return true;}); }
	public function getGuardedFields() { return $this->guarded; }
	public function getCustomFields() { return $this->customFields; }

	// Model states getters
	public function isFresh() { return $this->isFresh; }
	public function isModified() { return $this->isModified; }
	public function isDeleted() { return $this->isDeleted; }
	public function isSoftDeleted() { return (static::softDelete() && $this->exists && $this->isDeleted) ? true : false; }
	public function wasRecentlyCreated() { return $this->wasRecentlyCreated; }
	public function exists() { return $this->exists; }
	protected function isInsertable() { return (static::table() && static::pk() && $this->isFresh()); }
	protected function isUpdatable() { return (static::table() && static::pk() && $this->exists() && $this->isModified() && !$this->isDeleted()); }
	protected function isDeletable() { return (static::table() && static::pk() && $this->exists() && !$this->isDeleted()); }
	protected function isRestorable() { return (static::table() && static::pk() && $this->isSoftDeleted()); }

	/*******************************************************************************************************************
	 * cleanRelationDuplicates()
	 * Clean loaded relations duplicate (Models with the same primary key)
	 */
	protected function cleanRelationDuplicates()
	{
		foreach (static::$relations as $relationName => $relation)
		{
			$relationsLoaded = $this->$relationName;
			$relationsNoDuplicates = [];
			$relationsIds = [];
			if (is_array($relationsLoaded))
			{
				foreach ($relationsLoaded as $model)
				{
					if (!in_array($model->pkValue(), $relationsIds))
					{
						$relationsNoDuplicates[] = $model;
						$relationsIds[] = $model->pkValue();
					}
				}

				$this->$relationName = $relationsNoDuplicates;
			}
		}

		foreach (static::$relationsPivot as $relationName => $relation)
		{
			$relationsLoaded = $this->$relationName;
			$relationsNoDuplicates = [];
			$relationsIds = [];
			if (is_array($relationsLoaded))
			{
				foreach ($relationsLoaded as $model)
				{
					if (!in_array($model->pkValue(), $relationsIds))
					{
						$relationsNoDuplicates[] = $model;
						$relationsIds[] = $model->pkValue();
					}
				}

				$this->$relationName = $relationsNoDuplicates;
			}
		}
	}

	/*******************************************************************************************************************
	 * prepareInsert()
	 * Prepare fields before insert
	 */
	protected function prepareInsert($mode=0)
	{
		// Init PK
		if (!$mode) $this->fields[static::pk()]->set(null);
		else if($mode) $this->fields[static::pk()]->reverse();

		// Init timestamps fields
		if (static::timestamps() && !$mode)
		{
			$this->fields['created_at']->set(date("Y-m-d H:i:s"));
			$this->fields['updated_at']->set($this->fields['created_at']->raw());
		}
		else if (static::timestamps() && $mode)
		{
			$this->fields['created_at']->reverse();
			$this->fields['updated_at']->reverse();
		}

		// Init softDelete fields
		if (static::softDelete() && !$mode)
		{
			if (static::timestamps()) $this->fields['deleted_at']->set(null);
			$this->fields['active']->set(1);
		}
		else if (static::softDelete() && $mode)
		{
			if (static::timestamps()) $this->fields['deleted_at']->reverse();
			$this->fields['active']->reverse();
		}
	}

	/*******************************************************************************************************************
	 * prepareUpdate($mode=0)
	 *     $mode: 0=before update; 1=after update fail
	 * Prepare fields before update
	 */
	protected function prepareUpdate($mode=0)
	{
		if (static::timestamps() && !$mode) $this->fields['updated_at']->set(date("Y-m-d H:i:s"));
		else if(static::timestamps() && $mode) $this->fields['updated_at']->reverse();
	}

	/*******************************************************************************************************************
	 * prepareDelete($mode=0)
	 *     $mode: 0=before delete; 1=after delete fail
	 * Prepare fields before deletion
	 */
	protected function prepareDelete($mode=0)
	{
		if (static::softDelete() && static::timestamps() && !$mode) $this->fields['updated_at']->set(date("Y-m-d H:i:s"));
		else if (static::softDelete() && static::timestamps() && $mode) $this->fields['updated_at']->reverse();

		if (static::softDelete() && static::timestamps() && !$mode) $this->fields['deleted_at']->set(date("Y-m-d H:i:s"));
		else if (static::softDelete() && static::timestamps() && $mode) $this->fields['deleted_at']->reverse();

		if (static::softDelete() && !$mode) $this->fields['active']->set(0);
		else if (static::softDelete() && $mode) $this->fields['active']->reverse();
	}

	/*******************************************************************************************************************
	 * prepareRestore($mode=0)
	 *     $mode: 0=before restore; 1=after restore fail
	 * Prepare fields before restoration
	 */
	protected function prepareRestore($mode=0)
	{
		if (static::timestamps() && !$mode) $this->fields['deleted_at']->set(null);
		else if (static::timestamps() && $mode) $this->fields['deleted_at']->reverse();

		if (static::softDelete() && !$mode) $this->fields['active']->set(1);
		else if (static::softDelete() && $mode) $this->fields['active']->reverse();
	}
}

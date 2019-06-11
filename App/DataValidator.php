<?php
namespace App;

/***********************************************************************************************************************
 * Class DataValidatorRuleException
 *
 * Exceptions throws by DataValidator rules check.
 */
class DataValidatorRuleException extends \Exception {};

/***********************************************************************************************************************
 * Class DataValidator
 *     public function __construct(array $data)
 *     public function addFieldRules(string $key, array $rules) : bool
 *     public function addRules(array $rules) : bool
 *     public static function addRuleset(string $name, array $rules) : bool
 *     public function validate(array $data=null, array $rules=null) : bool
 *     public function errorToArray() : array
 *     public function errorToJson() : string
 *     protected static function checkRules(array &$rules) : bool
 *     protected static function generateErrorMsg($key, $msg, $ruleType, $param)
 *     private static function checkRecursiveRuleset($name, $usedRulesets=[])
 *
 * This class apply a set of constraints on a defined set of $data and display errors messages.
 * Available constraints :
 *     exec : execute a callback or a function with $data as parameter ; the returns must be true or it will fail to validate
 *     set : $data must be one of the values defined (array or comma separated string)
 *     regex : $data must validate the regex
 *     length : $data must be between min|max characters (for string)
 *     range : $data must be between min|max (for integer & float)
 *     empty : whenever $data can be evaluate to empty or not
 *     required : whenever $data must exist to validate
 *     match-field : $data must match an other field value
 *     ruleset : apply a nammed set of rules
 *
 * Sample :
 * DataValidator::addRuleset('nickname', [['regex'=>'/^([A-Za-z0-9\-_ ]+)$/', 'msg'=>'Le pseudo ne peut contenir que des caractères alphanumériques non accentués, des tirets et des espaces.'],
 *									      ['ruleset'=>'varchar']]);
 * DataValidator::addRuleset('varchar', [['length'=> '1|5', 'msg' => 'La taille du champ doit être comprise entre #LengthMin# et #LengthMax# caractères.']]);
 *
 * $datas = ['nickname' => '12345%'];
 * $DataValidator = new DataValidator($datas);
 * $DataValidator->addRules(['nickname' => [['ruleset'=>'nickname']]]);
 * $DataValidator->addRules(['pwd' => [['required'=>true, 'msg'=>'Le champ pwd est obligatoire.']]]);
 * $DataValidator->validate();
 *
 */
class DataValidator
{
	protected static $rulesets = [];
	protected $data;
	protected $rules;
	protected $errors;

	/*******************************************************************************************************************
	 * public function __construct(array $data)
	 *     $data : An assoc array with data to validate
	 */
	public function __construct(array $data)
	{
		$this->data = $data;
		$this->rules = [];
		$this->errors = [];
	}

	/*******************************************************************************************************************
	 * public function addFieldRules(string $key, array $rules) : bool
	 *     $key : Key for the dataset
	 *     $rules : An array of rules to apply for this $key
	 *
	 * Returns true on success; false otherwise
	 */
	public function addFieldRules(string $key, array $rules) : bool
	{
		if (!array_key_exists($key, $this->rules)) $this->rules[$key] = [];

		$old = $this->rules[$key];
		foreach ($rules as $rule)
		{
			if (self::checkRules($rule)) $this->rules[$key][] = $rule;
			else
			{
				$this->rules[$key] = $old;
				return false;
			}
		}

		return true;
	}

	/*******************************************************************************************************************
	 * public function addRules(array $rules) : bool
	 *     $rules : An assoc array of rules. Each key of the assoc is the $key for the dataset
	 *
	 * Returns true on success; false otherwise
	 */
	public function addRules(array $rules) : bool
	{
		foreach ($rules as $key => $rule)
		{
			foreach ($rule as $r)
			{
				if (!array_key_exists($key, $this->rules)) $this->rules[$key] = [];

				$old = $this->rules[$key];
				if (self::checkRules($r)) $this->rules[$key][] = $r;
				else
				{
					$this->rules[$key] = $old;
					return false;
				}
			}
		}

		return true;
	}

	/*******************************************************************************************************************
	 * public static function addRuleset(string $name, array $rules) : bool
	 *     $name : Name for the ruleset
	 *     $rules : Rules to apply
	 *
	 * Returns true on success; false otherwise
	 */
	public static function addRuleset(string $name, array $rules) : bool
	{
		if (!array_key_exists($name, self::$rulesets)) self::$rulesets[$name] = [];

		$old = self::$rulesets[$name];
		foreach ($rules as $rule)
		{
			if (self::checkRules($rule)) self::$rulesets[$name][] = $rule;
			else
			{
				self::$rulesets[$name] = $old;
				return false;
			}
		}

		return true;
	}

	/*******************************************************************************************************************
	 * public function validate(array $data=null, array $rules=null) : bool
	 *     $data : Used only for recursive ruleset ; Assoc array of data
	 *     $rules : Used only for recursive ruleset ; An array of rules
	 *
	 * Returns true if all rules succeed; false otherwise
	 */
	public function validate(array $data=null, array $rules=null) : bool
	{
		$Data = $data ?? $this->data;
		$Rules = $rules ?? $this->rules;
		$validated = true;

		foreach ($Rules as $key => $rules)
		{
			foreach ($rules as $ruleType => $params)
			{
				foreach ($params as $ruleType => $param)
				{
					$error = false;
					if (array_key_exists($key, $Data))
					{
						switch ($ruleType)
						{
							case 'exec': if (!$param($Data[$key])) $error = true; break;
							case 'set': if (!in_array($Data[$key], $param)) $error = true; break;
							case 'regex': if (!preg_match($param, $Data[$key])) $error = true; break;
							case 'length':
							    $length = strlen($Data[$key]);
								if (!$param['min'] && $param['max'] && !($length <= $param['max'])) $error = true;
								elseif ($param['min'] && !$param['max'] && !($length >= $param['min'])) $error = true;
								elseif ($param['min'] && $param['max'] && !($length >= $param['min'] && $length <= $param['max'])) $error = true;
							break;
							case 'range':
								if (!$param['min'] && $param['max'] && !($Data[$key] <= $param['max'])) $error = true;
								elseif ($param['min'] && !$param['max'] && !($Data[$key] >= $param['min'])) $error = true;
								elseif ($param['min'] && $param['max'] && !($Data[$key] >= $param['min'] && $Data[$key] <= $param['max'])) $error = true;
							break;
							case 'empty': if (!$param && empty($Data[$key])) $error = true; break;
							case 'match-field': if (!array_key_exists($param, $Data) || $Data[$key] != $Data[$param]) $error = true; break;
							case 'ruleset': if(!$this->validate([$key => $Data[$key]], [$key => self::$rulesets[$param]])) $error = true; break;
						}
					}
					elseif ($ruleType == 'required' && $param) $error = true;

					if ($error)
					{
						$validated = false;

						if (!array_key_exists($key, $this->errors)) $this->errors[$key] = [];
						$this->errors[$key][] = ['type' => $ruleType,
											   	 'msg' => (array_key_exists('msg', $params)) ? self::generateErrorMsg($key, $params['msg'], $ruleType, $param) : ''];
					}
				}
			}
		}

		return $validated;
	}

	/*******************************************************************************************************************
	 * public function errorToArray() : array
	 *
	 * Returns an assoc array with errors messages.
	 */
	public function errorToArray() : array { return $this->errors; }

	/*******************************************************************************************************************
	 * public function errorToJson() : string
	 *
	 * Returns json encoded errorToArray()
	 */
	public function errorToJson() : string { return json_encode($this->errorToArray()); }

	/*******************************************************************************************************************
	 * protected static function generateErrorMsg($key, $msg, $ruleType, $param)
	 *
	 * Returns message error with dynamics parameters values
	 */
	protected static function generateErrorMsg($key, $msg, $ruleType, $param)
	{
		switch ($ruleType)
		{
			case 'set': $param = "[".implode(',', $param)."]"; break;
			case 'exec': $param = ($param instanceof \Closure) ? "closure" : $param; break;
		}

		$msg = str_replace('#Key#', $key, $msg);
		$msg = str_replace('#RuleType#', $ruleType, $msg);
		if (!in_array($ruleType, ['length', 'range'])) $msg = str_replace('#'.ucfirst($ruleType).'#', $param, $msg);
		else
		{
			$msg = str_replace('#'.ucfirst($ruleType).'Min#', $param['min'], $msg);
			$msg = str_replace('#'.ucfirst($ruleType).'Max#', $param['max'], $msg);
		}

		return $msg;
	}

	/*******************************************************************************************************************
	 * protected static function checkRules(array &$rules) : bool
	 *     $rules : Array of rules
	 *
	 * Returns true if rules are valids; throw a DataValidatorRuleException otherwise.
	 */
	protected static function checkRules(array &$rules) : bool
	{
		foreach ($rules as $ruleType => $param)
		{
			switch ($ruleType)
			{
				case 'exec':
					if (!is_callable($param)) throw new DataValidatorRuleException("'$param' is not callable for RuleType '$ruleType'");
				break;

				case 'set':
					if (is_string($param)) $rules[$ruleType] = explode(',', $param);
					if (!is_array($rules[$ruleType])) throw new DataValidatorRuleException("'$param' is not an array for RuleType '$ruleType'");
					$rules[$ruleType] = array_values($rules[$ruleType]);
					if (!count($rules[$ruleType])) throw new DataValidatorRuleException("'$param' must be an array and contains at least one value for RuleType '$ruleType'");
				break;

				case 'regex':
					if (@preg_match($param, null) === false) throw new DataValidatorRuleException("'$param' is not valid for RuleType '$ruleType'");
				break;

				case 'length':
				case 'range':
					$matches = [];
					preg_match('/^([0-9\.,\-]*)\|([0-9\.,\-]*)$/', $param, $matches);
					if (!$matches) throw new DataValidatorRuleException("'$param' is not well formatted, it should match 'min|max', min or max can be empty for RuleType '$ruleType'");

					$matches = array_map('floatval', [$matches[1], $matches[2]]);
					if ((is_integer($matches[0]) || is_float($matches[0]) || empty($matches[0])) &&
					    (is_integer($matches[1]) || is_float($matches[1]) || empty($matches[1])) &&
						(!empty($matches[0]) || !empty($matches[1])))
					{
						$rules[$ruleType] = ['min' => $matches[0], 'max' => $matches[1]];
					}
					else throw new DataValidatorRuleException("'$param' min and max should be integer, float or one of them can be empty for RuleType '$ruleType'");
				break;

				case 'empty':
				case 'required':
					if (!is_bool($param)) throw new DataValidatorRuleException("'$param' must be a boolean value for RuleType '$ruleType'");
				break;

				case 'match-field':
					if (!is_string($param)) throw new DataValidatorRuleException("'$param' must be a string for RuleType '$ruleType'");
				break;

				case 'ruleset':
					if (!is_string($param)) throw new DataValidatorRuleException("'$param' must be a string for RuleType '$ruleType'");
					if (!array_key_exists($param, self::$rulesets)) self::addRuleset($param, []);

					if (self::checkRecursiveRuleset($param)) throw new DataValidatorRuleException("Recursive loop detected for RuleType '$ruleType' with Ruleset '$param'");
				break;

				case 'msg':
					if (!is_string($param)) throw new DataValidatorRuleException("'$param' must be a string for RuleType '$ruleType'");
				break;

				default:
					throw new DataValidatorRuleException("Unsupported RuleType : '$ruleType'");
			}
		}

		return true;
	}

	/*******************************************************************************************************************
	 * private static function checkRecursiveRuleset($name, $usedRulesets=[])
	 * Check that 'ruleset' ruletype are not recursive. (A calling B; B calling A or A calling A)
	 *
	 * Returns true if their is no infinite loop.
	 */
	private static function checkRecursiveRuleset($name, $usedRulesets=[])
	{
		if (!in_array($name, $usedRulesets)) $usedRulesets[] = $name;
		else return true;

		foreach (self::$rulesets[$name] as $rules)
		{
			foreach ($rules as $ruleType => $param) if ($ruleType == 'ruleset') return self::checkRecursiveRuleset($param, $usedRulesets);
		}

		return false;
	}
}

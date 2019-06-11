<?php
namespace Models;

use App\{Model,QueryBuilder};

class UserLogin extends Model
{
	protected static $table = 'user_logins';
	protected static $softDelete = true;
	protected static $timestamps = true;

	protected $fields = [['id', 'int', 'int'],
					  	 ['nickname', 'str', 'str'],
					  	 ['email', 'str', 'str'],
					  	 ['password', 'str', 'str'],
					  	 ['created_at', 'datetime', 'datetime'],
					  	 ['updated_at', 'datetime', 'datetime'],
					  	 ['deleted_at', 'datetime', 'datetime'],
					  	 ['active', 'bool', 'int']];

	public static function isEmailAvailable($email)
	{
		$Query = new QueryBuilder();
		return \App\DB::get()->fetch($Query->table('user_logins')->count('email')->where('email', $email));
	}
	//protected static $relations = ['Phones' => ['class' => 'Models\Phone', 'fk' => 'user_id']];
	//protected static $relationsPivot = ['Phones' => ['class' => 'Models\Phone', 'table' => 'user_phone', 'pk' => 'user_id', 'fk' => 'phone_id', 'timestamps' => true]];
}

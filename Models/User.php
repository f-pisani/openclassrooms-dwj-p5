<?php
namespace Models;

use App\{Model,ModelObserver,Database};

class User extends Model
{
	protected static $table = 'users';
	protected static $softDelete = true;
	protected static $timestamps = true;

	protected $fields = [['id', 'int', 'int'],
					  	 ['email', 'str', 'str'],
					  	 ['password', 'str', 'str'],
					  	 ['created_at', 'date:d/m/Y H:i:s', 'datetime'],
					  	 ['updated_at', 'datetime', 'datetime'],
					  	 ['deleted_at', 'datetime', 'datetime'],
					  	 ['active', 'bool', 'int']];

	protected static $relations = ['Phones' => ['class' => 'Models\Phone', 'fk' => 'user_id']];
	protected static $relationsPivot = ['Phones' => ['class' => 'Models\Phone', 'table' => 'user_phone', 'pk' => 'user_id', 'fk' => 'phone_id', 'timestamps' => true]];
}

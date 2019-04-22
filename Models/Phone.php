<?php
namespace Models;

use App\{Model};

class Phone extends Model
{
	protected static $table = 'phones';
	protected static $softDelete = false;
	protected static $timestamps = true;

	protected $fields = [['id', 'int', 'int'],
						 ['user_id', 'int', 'int'],
						 ['phone', 'str', 'str'],
						 ['created_at', 'datetime', 'datetime'],
						 ['updated_at', 'datetime', 'datetime']];
}

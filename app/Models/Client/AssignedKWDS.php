<?php

namespace App\Models\Client;

use Illuminate\Database\Eloquent\Model;

class AssignedKWDS extends Model
{
	protected $table = 'assigned_kwds';
	protected $fillable = ['client_id','city_id','parent_cat_id','child_cat_id','kw_id','sold_on_position','sold_on_price'];
}

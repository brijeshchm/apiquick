<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessKeyword extends Model
{
   	protected $guarded = [];
	protected $table = 'business_keyword';
	
	protected $fillable = ['city_id','parent_category_id','child_category_id','keyword_id','category'];		
}

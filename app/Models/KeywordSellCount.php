<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KeywordSellCount extends Model
{
    //
	protected $table = 'keyword_sell_count';
	
	protected $fillable = ['name','count','cat1_price','cat2_price','cat3_price','cat4_price','cat5_price'];	
}

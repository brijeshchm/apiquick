<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignedClientCity extends Model
{
    protected $fillable = ['client_id','city_id'];
}

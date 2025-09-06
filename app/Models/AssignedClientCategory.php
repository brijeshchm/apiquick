<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignedClientCategory extends Model
{
    protected $fillable = ['client_id','client_category_id'];
}

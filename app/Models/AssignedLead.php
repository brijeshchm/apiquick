<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignedLead extends Model
{
    protected $fillable = ['kw_id','client_id','lead_id'];
}

<?php
// app/Models/Area.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    
    protected $guarded = [];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function assignedAreas()
    {
        return $this->hasMany(AssignedArea::class);
    }
}
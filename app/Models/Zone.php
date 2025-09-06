<?php
// app/Models/Zone.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $guarded = [];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function areas()
    {
        return $this->hasMany(Area::class);
    }

    public function assignedZones()
    {
        return $this->hasMany(AssignedZone::class);
    }
}
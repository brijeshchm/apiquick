<?php
// app/Models/City.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class City extends Model
{
    protected $guarded = [];
    protected $table = 'cities';
    public function zones()
    {
        return $this->hasMany(Zone::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function assignedZones()
    {
        return $this->hasMany(AssignedZone::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }
}
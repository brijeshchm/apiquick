<?php
// app/Models/AssignedZone.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignedZone extends Model
{
    protected $table = 'assigned_zones';
    protected $guarded = [];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
<?php
// app/Models/AssignedZone.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignedZone extends Model
{
    protected $table = 'assigned_zones';
    protected $fillable = [
        'client_id',
        'city_id',
        'zone_id',
        'state_id',       
        
    ];

    protected $guarded = [];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    
    public function city()
{
    return $this->belongsTo(City::class, 'city_id');
}

public function zone()
{
    return $this->belongsTo(Zone::class, 'zone_id');
}
}
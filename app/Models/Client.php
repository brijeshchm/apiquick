<?php
// app/Models/Client.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
class Client extends Model
{
    use SoftDeletes,HasApiTokens;
    protected $table = 'clients';
    protected $guarded = [];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function assignedAreas()
    {
        return $this->hasMany(AssignedArea::class);
    }

    public function assignedZones()
    {
        return $this->hasMany(AssignedZone::class);
    }

    public function assignedKeywords()
    {
        return $this->hasMany(AssignedKwd::class);
    }

    public function leads()
    {
        return $this->hasMany(AssignedLead::class);
    }

    public function meetings()
    {
        return $this->hasMany(Meeting::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
<?php
// app/Models/Lead.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $guarded = [];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function keyword()
    {
        return $this->belongsTo(Keyword::class, 'kw_id');
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function assignedLeads()
    {
        return $this->hasMany(AssignedLead::class);
    }

    public function followUps()
    {
        return $this->hasMany(LeadFollowUp::class);
    }
}
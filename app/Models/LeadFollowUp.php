<?php
// app/Models/LeadFollowUp.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadFollowUp extends Model
{
     protected $table = 'lead_follow_ups'; 
    protected $fillable = [
        'status',
        'expected_date_time',
        'remark',
        'lead_id',
        'client_id',
        'remark_by',
    ];
    protected $guarded = [];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
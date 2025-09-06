<?php
// app/Models/AssignedArea.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssigneddArea extends Model
{
	
	
	 protected $table = 'assignedd_areas';
    protected $guarded = [];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}
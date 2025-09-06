<?php
// app/Models/AssignedArea.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignedArea extends Model
{
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
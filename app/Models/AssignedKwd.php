<?php
// app/Models/AssignedKwd.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignedKwd extends Model
{
    protected $guarded = [];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function parentCategory()
    {
        return $this->belongsTo(ParentCategory::class, 'parent_cat_id');
    }

    public function childCategory()
    {
        return $this->belongsTo(ChildCategory::class, 'child_cat_id');
    }

    public function keyword()
    {
        return $this->belongsTo(Keyword::class, 'kw_id');
    }
}
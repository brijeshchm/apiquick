<?php
// app/Models/Keyword.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Keyword extends Model
{
    protected $table = 'keyword';
    protected $guarded = [];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function parentCategory()
    {
        return $this->belongsTo(ParentCategory::class);
    }

    public function childCategory()
    {
        return $this->belongsTo(ChildCategory::class);
    }

    public function assignedKeywords()
    {
        return $this->hasMany(AssignedKwd::class, 'kw_id');
    }

    public function leads()
    {
        return $this->hasMany(Lead::class, 'kw_id');
    }
}
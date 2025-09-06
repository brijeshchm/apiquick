<?php
// app/Models/ChildCategory.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChildCategory extends Model
{
    protected $table = 'child_category';
    protected $guarded = [];

    public function parentCategory()
    {
        return $this->belongsTo(ParentCategory::class);
    }

    public function keywords()
    {
        return $this->hasMany(Keyword::class);
    }
}
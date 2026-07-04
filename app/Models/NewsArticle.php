<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
class NewsArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'author',
        'title',
        'slug',
        'description',
        'meta_title',
        'meta_keywords',
        'meta_description',
        'top_content',
        'top_heading',
        'category_id',
        'category_name',
        'bottom_heading',
        'bottom_content',
        'image_banner',
        'ratingcount',
        'ratingvalue',
        'heading',
        'about_blog',
        'paragraph1',
        'paragraph2',
        'paragraph3',
        'paragraph4',
        'paragraph5',
        'paragraph6',
        'faqq1',
        'faqa1',
        'faqq2',
        'faqa2',
        'faqq3',
        'faqa3',
        'faqq4',
        'faqa4',
        'faqq5',
        'faqa5',
        'image',
        'status',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'ratingcount' => 'integer',
        'ratingvalue' => 'decimal:2',
        'status' => 'boolean',
    ];


     
 
    public function category()
    {
        return $this->belongsTo(ParentCategory::class, 'category_id');
    }
 
    // Usage in Blade: $article->image_url  (never null — falls back to a placeholder)
    public function getImageUrlAttribute(): string
    {
        return $this->image->image->src ?? asset('images/no-image.jpg');
    }
 
    // Usage in Blade: $article->image_alt
    public function getImageAltAttribute(): string
    {
        return $this->image->image->name ?? $this->title;
    }
 
    // Usage in Blade: $article->category_name  (safe even if category relation is missing)
    public function getCategoryNameAttribute(): string
    {
        return $this->category?->parent_category ?? 'General';
    }
 
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }
 
    public function scopeArticles(Builder $query): Builder
    {
        return $query->where('status', 'category_name');
    }
 
    public function scopeVideos(Builder $query): Builder
    {
        return $query->where('status', 'image');
    }
 
    public function scopeInCategorySlug(Builder $query, ?string $slug): Builder
    {
        if (!$slug || $slug === 'latest') {
            return $query;
        }
 
        return $query->whereHas('category', fn (Builder $q) => $q->where('parent_slug', $slug));
    }



}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function news(){
        return $this->hasMany(News::class);
    }

    public function scopeActive($query){
        return $query->where('is_active', true);
    }
    public function scopeWithSlug($query, $slug){
        return $query->where('slug', $slug);
    }
    public function getPublishedNewsCountAttribute(){
        return $this->news()->where('is_published', true)->count();
    }
    public function getLatestNewsAttribute(){
        return $this->news()->where('is_published', true)
        ->orderBy('published_at', 'desc')
        ->limit(5)
        ->get();
    }
    public function getUrlAttribute(){
        return route('category.show', $this->slug);
    }
}

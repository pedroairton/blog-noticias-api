<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'color'
    ];

    public function news(){
        return $this->belongsToMany(News::class)->withTimestamps();
    }
    protected static function boot(){
        parent::boot();
        static::creating(function ($tag) {
            if(empty($tag->slug)){
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    public function scopePopular($query, $limit = 10){
        return $query->withCount('news')->orderBy('news_count', 'desc')->limit($limit);
    }
    public function getPublishedNewsCountAttribute(){
        return $this->news()->where('is_published', true)->count();
    }
    
    public function getLatestNewsAttribute(){
        return $this->news()
        ->where('is_published', true)
        ->orderBy('published_at', 'desc')
        ->limit(5)
        ->get();
    }
    public function getUrlAttribute(){
        return route('tag.show', $this->slug);
    }
    public function getBackgroundColorAttribute(){
        return $this->color ?: '#6b7280';
    }
    public function getTextColorAttribute(){
        return '#fff';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsGallery extends Model
{
    use HasFactory;

    protected $table = 'news_galleries';

    protected $fillable = [
        'news_id',
        'image_path',
        'thumbnail_path',
        'caption',
        'alt_text',
        'position',
        'placeholder_in_content',
        'original_name',
        'mime_type',
        'file_size',
        'dimensions',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'position' => 'integer',
        'dimensions' => 'array',
        'file_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function news(){
        return $this->belongsTo(News::class);
    }
    public function scoreOrderer($query){
        return $query->orderBy('position', 'asc');
    }
    public function getImageUrlAttribute(){
        if($this->image_path){
            return asset('storage/' . $this->image_path);
        }
        return null;
    }
    public function getThumbnailUrlAttribute(){
        if($this->thumbnail_path){
            return asset('storage/' . $this->thumbnail_path);
        }
        return $this->image_url;
    }
    public function getDimensionsFormattedAttribute(){
        if($this->dimensions && isset($this->dimensions['width']) && isset($this->dimensions['height'])){
            return "{$this->dimensions['width']} x {$this->dimensions['height']}";
        }
        return 'N/A';
    }
    public function getFileSizeFormattedAttribute(){
        $bytes = $this->file_size;
        
        if($bytes >= 1024*1024*1024){
            return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
        }
        elseif($bytes >= 1024*1024){
            return round($bytes / (1024 * 1024), 2) . ' MB';
        }
        elseif($bytes >= 1024){
            return round($bytes / 1024, 2) . ' KB';
        } 
        else {
            return $bytes . ' bytes';
        }
    }
}

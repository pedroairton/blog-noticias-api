<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

use function Symfony\Component\Clock\now;

class News extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'subtitle',
        'slug',
        'excerpt',
        'content',
        'main_image',
        'main_image_caption',
        'main_image_alt',
        'is_published',
        'published_at',
        'category_id',
        'author_id',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'view_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $dates = [
        'published_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $appends = ['main_image_url', 'content_with_full_urls', 'reading_time'];

    protected static function boot()
    {
        parent::boot();
        static::creating(
            function ($news) {
                if (empty($news->slug)) {
                    $news->slug = Str::slug($news->title);
                    // slug único
                    $count = 1;
                    while (static::where('slug', $news->slug)->exists()) {
                        $news->slug = Str::slug($news->title . '-' . $count);
                        $count++;
                    }
                }
                if ($news->is_published && !$news->published_at) {
                    $news->published_at = now();
                }
            }
        );
        static::updating(
            function ($news) {
                // atualiza published_at se status mudar para publicado
                if ($news->is_published && !$news->published_at) {
                    $news->published_at = now();
                }
            }
        );
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function author()
    {
        return $this->belongsTo(Admin::class, 'author_id');
    }
    public function gallery(){
        return $this->hasMany(NewsGallery::class, 'news_id')->orderBy('position', 'asc');
    }
    public function tags(){
        return $this->belongsToMany(Tag::class, 'news_tag')->withTimestamps();
    }
    public function scopePublished($query){
        return $query->where('is_published', true)
        ->whereNotNull('published_at')
        ->where('published_at', '<=', now());
    }
    public function scopeFeatured($query){
        return $query->published()->where('is_featured', true);
    }
    public function scopeByCategory($query, $category_id){
        return $query->where('category_id', $category_id);
    }
    public function scopeByAuthor($query, $author_id){
        return $query->where('author_id', $author_id);
    }
    public function scopeSearch($query, $term){
        return $query->where('title', 'like', '%'.$term.'%')
        ->orWhere('description', 'like', '%'.$term.'%')
        ->orWhere('content', 'like', '%'.$term.'%');
    }
    public function scopeRecent($query, $days = 90){
        return $query->where('created_at', '>=', \Carbon\Carbon::now()->subDays($days));
    }
    public function scopeMostViewed($query){
        return $query->published()->recent()->orderBy('view_count', 'desc')->take(3);
    }
    public function getUrlAttribute(){
        return route('news.show', $this->slug);
    }
    public function getMainImageUrlAttribute(){
        if($this->main_image){
            return asset('storage/'.$this->main_image);
        }
        return null;
    }
    public function getContentWithFullUrlsAttribute(){
        if(!$this->content){
            return $this->content;
        }
        $content = $this->content;

        $content = preg_replace_callback(
            '/src="([^"]+)"/',
            function ($matches) {
                $src = $matches[1];
                
                // Se não for URL completa (não começa com http://, https://, data:)
                if (!preg_match('/^(https?:|\/\/|data:)/', $src)) {
                    // Verificar se é um caminho do storage
                    if (strpos($src, 'storage/') !== 0) {
                        $src = 'storage/' . $src;
                    }
                    return 'src="' . asset($src) . '"';
                }
                
                return $matches[0];
            },
            $content
        );

        return $content;
    }
    public function getReadingTimeAttribute(){
        $wordCount = str_word_count(strip_tags($this->content));
        $minutes = ceil($wordCount / 200); // 200 palavras por minuto
        return max(1, $minutes);
    }
    public function getPublishedDateAttribute(){
        if($this->published_at){
            return $this->published_at->format('d/m/Y H:i');
        }
        return null;
    }
    public function getIsRecentAttribute(){
        return $this->published_at && $this->published_at->diffInDays(now()) <= 3;
    }
    public function incrementViews(){
        $this->increment('views_count');
    }
    public function publish(){
        $this->update([
            'published_at' => now(),
            'is_published' => true,
        ]);
    }
    public function unpublish(){
        $this->update([
            'published_at' => null,
            'is_published' => false,
        ]);
    }
    public function addTag($tagId){
        $this->tags()->syncWithoutDetaching([$tagId]);
    }
    public function removeTag($tagId){
        $this->tags()->detach($tagId);
    }
    public function hasTag($tagId){
        return $this->tags()->where('id', $tagId)->exists();
    }
}

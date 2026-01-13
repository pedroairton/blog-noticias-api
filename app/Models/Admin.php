<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Model
{
    //
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'slug',
        'bio',
        'avatar',
        'role',
        'website',
        'social_facebook',
        'social_twitter',
        'social_instagram',
        'social_linkedin',
        'status'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function news()
    {
        return $this->hasMany(News::class, 'author_id');
    }

    public function scopeSuperAdmin($query)
    {
        return $query->where('role', 'super_admin');
    }
    public function scopeAuthors($query)
    {
        return $query->where('role', 'author');
    }
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function isSuperAdmin()
    {
        return $this->role === 'superadmin';
    }
    public function isAuthor()
    {
        return $this->role === 'author';
    }
    public function canModifyNews(News $news)
    {
        return $this->isSuperAdmin() || $this->id === $news->author_id;
    }

    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        $initials = strtoupper(substr($this->name, 0, 2));
        return 'https://ui-avatars.com/api/?name=' . $initials . '&background=random&color=fff';
    }
    public function getAuthorUrlAttribute()
    {
        return route('author.show', $this->slug);
    }
    public function publishedNewsCount()
    {
        return $this->news()->where('is_published', true)->count();
    }
    public function latestNews($limit = 5)
    {
        return $this->news()->where('is_published', true)
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
    }
}

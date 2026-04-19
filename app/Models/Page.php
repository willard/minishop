<?php

namespace App\Models;

use App\Enums\PageTemplate;
use App\Enums\PublishStatus;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'body',
        'excerpt',
        'status',
        'published_at',
        'featured_image_id',
        'meta_title',
        'meta_description',
        'template',
        'author_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => PublishStatus::class,
            'template' => PageTemplate::class,
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Page $page): void {
            if (empty($page->slug)) {
                $slug = Str::slug($page->title);
                $original = $slug;
                $count = 2;

                while (self::query()->where('slug', $slug)->exists()) {
                    $slug = "{$original}-{$count}";
                    $count++;
                }

                $page->slug = $slug;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_image_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', PublishStatus::Published)
            ->where(function (Builder $q): void {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }
}

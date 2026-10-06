<?php

namespace App\Models;

use Database\Factories\TopicFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class Topic extends Model
{
    /** @use HasFactory<TopicFactory> */
    use HasFactory;

    use Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'slug',
        'description',
        'icon',
        'is_published',
        'order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'order' => 'integer',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Topic $topic): void {
            if (empty($topic->slug) && ! empty($topic->title)) {
                $topic->slug = Str::slug($topic->title);
            }
        });
    }

    /**
     * Get the indexable data array for the model in Laravel Scout.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => (string) $this->id,
            'title' => (string) $this->title,
            'description' => (string) ($this->description ?? ''),
            'tags' => (string) $this->tags->pluck('name')->implode(' '),
            'options_titles' => (string) $this->options->pluck('title')->implode(' '),
            'options_contents' => (string) $this->options->pluck('content')->implode(' '),
            'created_at' => (int) ($this->created_at?->timestamp ?? now()->timestamp),
        ];
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        return (bool) $this->is_published;
    }

    /**
     * Get the options/responses for the topic.
     *
     * @return HasMany<TopicOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(TopicOption::class)->orderBy('order');
    }

    /**
     * Get the tags associated with the topic.
     *
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'topic_tag')->withTimestamps();
    }

    /**
     * Scope a query to only include published topics.
     *
     * @param  Builder<Topic>  $query
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope a query to search topics, options, and tags via Eloquent fallback.
     *
     * @param  Builder<Topic>  $query
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term): void {
            $q->where('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhereHas('options', function (Builder $optionQuery) use ($term): void {
                    $optionQuery->where('title', 'like', "%{$term}%")
                        ->orWhere('content', 'like', "%{$term}%");
                })
                ->orWhereHas('tags', function (Builder $tagQuery) use ($term): void {
                    $tagQuery->where('name', 'like', "%{$term}%");
                });
        });
    }
}

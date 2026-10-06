<?php

namespace App\Models;

use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'color',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Tag $tag): void {
            if (empty($tag->slug) && ! empty($tag->name)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    /**
     * Get the topics associated with the tag.
     *
     * @return BelongsToMany<Topic, $this>
     */
    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(Topic::class, 'topic_tag')->withTimestamps();
    }
}

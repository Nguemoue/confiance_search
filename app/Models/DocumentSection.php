<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSection extends Model
{
    protected $fillable = [
        'document_id',
        'page_number',
        'section_title',
        'content',
        'order',
    ];

    /**
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Scope to find sections matching search terms.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);
        if (empty($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term): void {
            $q->where('content', 'like', "%{$term}%")
                ->orWhere('section_title', 'like', "%{$term}%");
        });
    }
}

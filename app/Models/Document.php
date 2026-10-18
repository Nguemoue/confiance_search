<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Document extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'reference_code',
        'description',
        'department',
        'language',
        'file_path',
        'original_filename',
        'file_size',
        'page_count',
        'is_published',
        'raw_text',
    ];

    protected static function booted(): void
    {
        static::creating(function (Document $document): void {
            if (empty($document->slug) && ! empty($document->title)) {
                $document->slug = Str::slug($document->title);
            }
            if (empty($document->reference_code)) {
                $document->reference_code = 'CS-DOC-'.strtoupper(Str::random(6));
            }
            if (empty($document->language)) {
                $document->language = 'fr';
            }
        });
    }

    /**
     * @return HasMany<DocumentSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(DocumentSection::class)->orderBy('order');
    }

    /**
     * Scope for published documents.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope by language.
     */
    public function scopeLanguage(Builder $query, ?string $language): Builder
    {
        if (empty($language) || $language === 'all') {
            return $query;
        }

        return $query->where('language', $language);
    }

    /**
     * Search documents and their sections.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);
        if (empty($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term): void {
            $q->where('title', 'like', "%{$term}%")
                ->orWhere('reference_code', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('department', 'like', "%{$term}%")
                ->orWhere('raw_text', 'like', "%{$term}%")
                ->orWhereHas('sections', function (Builder $sectionQuery) use ($term): void {
                    $sectionQuery->where('content', 'like', "%{$term}%")
                        ->orWhere('section_title', 'like', "%{$term}%")
                        ->orWhere('reference_label', 'like', "%{$term}%");
                });
        });
    }

    /**
     * Formatted file size string (e.g. 2.4 Mo).
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' Mo';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' Ko';
        }

        return $bytes.' o';
    }
}

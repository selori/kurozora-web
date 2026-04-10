<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaTypeCategory extends KModel
{
    // Table name
    const string TABLE_NAME = 'media_type_categories';
    protected $table = self::TABLE_NAME;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'media_type',
        'rating_category_id',
        'display_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array
     */
    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
        ];
    }

    /**
     * Returns the rating category associated with this media type category.
     *
     * @return BelongsTo
     */
    public function ratingCategory(): BelongsTo
    {
        return $this->belongsTo(RatingCategory::class);
    }

    /**
     * Get categories for a specific media type model class.
     *
     * @param string $modelClass
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getCategoriesForMediaType(string $modelClass): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('media_type', $modelClass)
            ->with('ratingCategory')
            ->orderBy('display_order')
            ->get();
    }
}

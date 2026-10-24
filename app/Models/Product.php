<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'sku',
        'brand',
        'description',
        'free_download_url',
        'file_format',
        'compatible_software',
        'file_size',
        'design_dimensions',
        'download_note',
        'tags',
        'price',
        'tax',
        'discount',
        'quantity',
        'minimum_quantity',
        'unit',
        'image',
        'status',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'tags' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->whereHas('category', fn ($category) => $category->where('status', 'active'));
    }
}

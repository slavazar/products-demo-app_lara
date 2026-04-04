<?php

namespace App\Models\User\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use App\Models\User\Product;

class Image extends Model
{
    protected $table = 'user_product_images'; // Specify the table name if it doesn't follow Laravel's naming convention
    
    protected $fillable = [
        'product_id',
        'image_path',
        'image_name',
        'alt_text',
        'sort_order',
        'is_primary',
        'mime_type',
        'file_size',
        'metadata',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Delete the image file when the model is deleted.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($image) {
            // Delete the actual file from storage
            if (Storage::exists($image->image_path)) {
                Storage::delete($image->image_path);
            }
        });
    }

    /**
     * Get the product that owns the image.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the full URL for the image.
     */
    public function getImageUrlAttribute(): string
    {
        return Storage::url($this->image_path);
    }

    /**
     * Get the formatted file size.
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Scope a query to only include primary images.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope a query to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }
}

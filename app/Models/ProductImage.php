<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $table = 'product_image';
    protected $primaryKey = 'image_id';
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'image_url',
        'is_main',
    ];

    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'is_main' => 'boolean',
        ];
    }

    public function product() { return $this->belongsTo(Product::class, 'product_id'); }
}

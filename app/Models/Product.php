<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'product';
    protected $primaryKey = 'product_id';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'gender',
        'category_id',
        'brand_id',
        'base_price',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function category() { return $this->belongsTo(Category::class, 'category_id'); }
    public function brand() { return $this->belongsTo(Brand::class, 'brand_id'); }
    public function images() { return $this->hasMany(ProductImage::class, 'product_id'); }
    public function mainImage() { return $this->hasOne(ProductImage::class, 'product_id')->where('is_main', true); }
    public function variants() { return $this->hasMany(ProductVariant::class, 'product_id'); }
    public function ratings() { return $this->hasMany(Rating::class, 'product_id'); }
    public function reviews() { return $this->hasMany(Review::class, 'product_id'); }
    public function wishlistedBy() { return $this->belongsToMany(Member::class, 'wishlist', 'product_id', 'member_id')->withPivot('added_at'); }
}

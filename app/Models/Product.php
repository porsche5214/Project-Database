<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'product';
    protected $primaryKey = 'product_id';
    public $timestamps = false;
    protected $guarded = [];

    public function category() { return $this->belongsTo(Category::class, 'category_id'); }
    public function brand() { return $this->belongsTo(Brand::class, 'brand_id'); }
    public function images() { return $this->hasMany(ProductImage::class, 'product_id'); }
    public function mainImage() { return $this->hasOne(ProductImage::class, 'product_id')->where('is_main', true); }
    public function variants() { return $this->hasMany(ProductVariant::class, 'product_id'); }
    public function ratings() { return $this->hasMany(Rating::class, 'product_id'); }
    public function reviews() { return $this->hasMany(Review::class, 'product_id'); }
}

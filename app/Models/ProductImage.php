<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $table = 'product_image';
    protected $primaryKey = 'image_id';
    public $timestamps = false;
    protected $guarded = [];

    public function product() { return $this->belongsTo(Product::class, 'product_id'); }
}

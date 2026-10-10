<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $table = 'product_variant';
    protected $primaryKey = 'variant_id';
    public $timestamps = false;
    protected $guarded = [];

    public function product() { return $this->belongsTo(Product::class, 'product_id'); }
}

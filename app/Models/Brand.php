<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $table = 'brand';
    protected $primaryKey = 'brand_id';
    public $timestamps = false;
    protected $guarded = [];

    public function products() { return $this->hasMany(Product::class, 'brand_id'); }
}

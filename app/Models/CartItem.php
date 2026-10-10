<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// PK เป็น composite (cart_id, variant_id): Eloquent รองรับไม่เต็มที่
// insert/อ่านผ่าน model ได้ แต่ update/delete ให้ใช้ $query->where(...)->update()/delete()
class CartItem extends Model
{
    protected $table = 'cart_item';
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    public function cart() { return $this->belongsTo(Cart::class, 'cart_id'); }
    public function variant() { return $this->belongsTo(ProductVariant::class, 'variant_id'); }
}

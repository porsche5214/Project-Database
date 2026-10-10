<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// ใช้แทน User ของ Laravel: Auth::attempt(['email' => ..., 'password' => ...]) จะเช็คกับ password_hash
class Member extends Authenticatable
{
    use Notifiable;

    protected $table = 'member';
    protected $primaryKey = 'member_id';
    public $timestamps = false;

    protected $fillable = ['username', 'email', 'password_hash', 'first_name', 'last_name', 'phone', 'role'];
    protected $hidden = ['password_hash', 'remember_token'];

    protected function casts(): array
    {
        return ['password_hash' => 'hashed', 'created_at' => 'datetime'];
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function addresses() { return $this->hasMany(Address::class, 'member_id'); }
    public function cart() { return $this->hasOne(Cart::class, 'member_id'); }
    public function orders() { return $this->hasMany(Order::class, 'member_id'); }
    public function wishlist() { return $this->belongsToMany(Product::class, 'wishlist', 'member_id', 'product_id')->withPivot('added_at'); }
    public function ratings() { return $this->hasMany(Rating::class, 'member_id'); }
    public function reviews() { return $this->hasMany(Review::class, 'member_id'); }
}

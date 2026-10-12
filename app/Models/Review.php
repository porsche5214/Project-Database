<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $table = 'review';
    protected $primaryKey = 'review_id';
    public $timestamps = false;

    protected $fillable = [
        'member_id',
        'product_id',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'member_id' => 'integer',
            'product_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function member() { return $this->belongsTo(Member::class, 'member_id'); }
    public function product() { return $this->belongsTo(Product::class, 'product_id'); }
}

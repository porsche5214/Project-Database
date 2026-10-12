<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// PK เป็น composite (member_id, product_id): update/delete ให้ใช้ where(...)->update()/delete()
// ให้คะแนนซ้ำ: Rating::updateOrInsert(['member_id' => .., 'product_id' => ..], ['score' => ..])
class Rating extends Model
{
    protected $table = 'rating';
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'member_id',
        'product_id',
        'score',
        'rated_at',
    ];

    protected function casts(): array
    {
        return [
            'member_id' => 'integer',
            'product_id' => 'integer',
            'score' => 'integer',
            'rated_at' => 'datetime',
        ];
    }

    public function member() { return $this->belongsTo(Member::class, 'member_id'); }
    public function product() { return $this->belongsTo(Product::class, 'product_id'); }
}

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

    /**
     * A review with a title is stored as "[title] text" in `comment`.
     *
     * @return array{0: string, 1: string} [title, text]
     */
    public function splitComment(): array
    {
        $comment = (string) $this->comment;
        if (preg_match('/^\[(.*?)\]\s*(.*)$/s', $comment, $matches)) {
            return [$matches[1], $matches[2]];
        }

        return ['', $comment];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $table = 'address';
    protected $primaryKey = 'address_id';
    public $timestamps = false;
    protected $guarded = [];

    public function member() { return $this->belongsTo(Member::class, 'member_id'); }

    public function fullAddress()
    {
        $parts = [
            $this->address_line,
            $this->subdistrict,
            $this->district,
            $this->province,
            $this->postal_code
        ];
        // Filter out empty parts and join with space
        return implode(' ', array_filter($parts));
    }
}

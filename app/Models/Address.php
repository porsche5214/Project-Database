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
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart', function (Blueprint $table) {
            $table->increments('cart_id');
            $table->unsignedInteger('member_id')->nullable()->unique();   // NULL = guest cart
            $table->string('session_token', 100)->nullable()->unique(); // used for guest cart

            $table->foreign('member_id')->references('member_id')->on('member')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart');
    }
};

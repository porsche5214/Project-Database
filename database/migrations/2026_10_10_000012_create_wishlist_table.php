<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlist', function (Blueprint $table) {
            $table->unsignedInteger('member_id');
            $table->unsignedInteger('product_id');
            $table->dateTime('added_at')->useCurrent();

            $table->primary(['member_id', 'product_id']);
            $table->foreign('member_id')->references('member_id')->on('member')->cascadeOnDelete();
            $table->foreign('product_id')->references('product_id')->on('product')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist');
    }
};

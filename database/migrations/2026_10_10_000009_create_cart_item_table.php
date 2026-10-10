<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_item', function (Blueprint $table) {
            $table->unsignedInteger('cart_id');
            $table->unsignedInteger('variant_id');
            $table->integer('quantity')->default(1);

            $table->primary(['cart_id', 'variant_id']);
            $table->foreign('cart_id')->references('cart_id')->on('cart')->cascadeOnDelete();
            $table->foreign('variant_id')->references('variant_id')->on('product_variant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_item');
    }
};

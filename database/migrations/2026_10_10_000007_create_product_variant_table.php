<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variant', function (Blueprint $table) {
            $table->increments('variant_id');
            $table->unsignedInteger('product_id');
            $table->string('color', 50);
            $table->string('size', 20); // S, M, L, 40, 41, ...
            $table->string('sku', 50)->unique();
            $table->decimal('price', 10, 2);
            $table->integer('stock')->default(0);

            $table->unique(['product_id', 'color', 'size']);
            $table->foreign('product_id')->references('product_id')->on('product')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant');
    }
};

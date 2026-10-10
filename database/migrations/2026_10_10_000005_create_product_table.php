<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product', function (Blueprint $table) {
            $table->increments('product_id');
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->enum('gender', ['male', 'female', 'unisex'])->default('unisex');
            $table->unsignedInteger('category_id');
            $table->unsignedInteger('brand_id')->nullable();
            $table->decimal('base_price', 10, 2);
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('category_id')->references('category_id')->on('category');
            $table->foreign('brand_id')->references('brand_id')->on('brand');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product');
    }
};

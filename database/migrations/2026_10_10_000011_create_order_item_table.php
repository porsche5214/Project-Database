<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_item', function (Blueprint $table) {
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('variant_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2); // price at time of purchase

            $table->primary(['order_id', 'variant_id']);
            $table->foreign('order_id')->references('order_id')->on('orders')->cascadeOnDelete();
            $table->foreign('variant_id')->references('variant_id')->on('product_variant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item');
    }
};

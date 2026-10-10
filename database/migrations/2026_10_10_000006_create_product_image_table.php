<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // แยกออกจาก product (DBML เดิมมี PK สองตัวใน product)
        Schema::create('product_image', function (Blueprint $table) {
            $table->increments('image_id');
            $table->unsignedInteger('product_id');
            $table->string('image_url', 255);
            $table->boolean('is_main')->default(false);

            $table->foreign('product_id')->references('product_id')->on('product')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_image');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category', function (Blueprint $table) {
            $table->increments('category_id');
            $table->string('name', 100);
            $table->unsignedInteger('parent_category_id')->nullable(); // NULL = top-level category

            $table->foreign('parent_category_id')->references('category_id')->on('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category');
    }
};

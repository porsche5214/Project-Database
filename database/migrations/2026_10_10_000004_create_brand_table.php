<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand', function (Blueprint $table) {
            $table->increments('brand_id');
            $table->string('name', 100)->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand');
    }
};

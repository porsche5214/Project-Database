<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('address', function (Blueprint $table) {
            $table->increments('address_id');
            $table->unsignedInteger('member_id');
            $table->string('recipient_name', 100);
            $table->string('phone', 20);
            $table->string('address_line', 255);
            $table->string('subdistrict', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('province', 100);
            $table->string('postal_code', 10);
            $table->boolean('is_default')->default(false);

            $table->foreign('member_id')->references('member_id')->on('member')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('address');
    }
};

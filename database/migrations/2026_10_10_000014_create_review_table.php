<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review', function (Blueprint $table) {
            $table->increments('review_id');
            $table->unsignedInteger('member_id');
            $table->unsignedInteger('product_id');
            $table->text('comment');
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('member_id')->references('member_id')->on('member')->cascadeOnDelete();
            $table->foreign('product_id')->references('product_id')->on('product')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review');
    }
};

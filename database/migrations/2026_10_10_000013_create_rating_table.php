<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rating', function (Blueprint $table) {
            $table->unsignedInteger('member_id');
            $table->unsignedInteger('product_id');
            $table->tinyInteger('score'); // 1 to 5
            $table->dateTime('rated_at')->useCurrent();

            $table->primary(['member_id', 'product_id']);
            $table->foreign('member_id')->references('member_id')->on('member')->cascadeOnDelete();
            $table->foreign('product_id')->references('product_id')->on('product')->cascadeOnDelete();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE rating ADD CONSTRAINT chk_rating_score CHECK (score BETWEEN 1 AND 5)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rating');
    }
};

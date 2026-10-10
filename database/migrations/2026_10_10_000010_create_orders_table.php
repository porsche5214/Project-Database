<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('order_id');
            $table->unsignedInteger('member_id')->nullable(); // NULL = guest checkout
            $table->string('contact_email', 100);
            $table->string('ship_name', 100);  // address snapshot at order time
            $table->string('ship_phone', 20);
            $table->text('ship_address');
            $table->dateTime('order_date')->useCurrent();
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['pending', 'paid', 'shipped', 'completed', 'cancelled'])->default('pending');

            $table->foreign('member_id')->references('member_id')->on('member')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

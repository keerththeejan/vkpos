<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_serial_numbers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('business_id')->nullable()->index();
            $table->string('serial_number', 100);
            $table->enum('status', ['available', 'sold', 'returned', 'damaged', 'reserved'])
                ->default('available')
                ->index();
            $table->unsignedInteger('sell_line_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            $table->unique(['product_id', 'serial_number'], 'product_serial_unique');
            $table->index(['product_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_serial_numbers');
    }
};

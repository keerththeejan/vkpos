<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Zebra ZPL layout profiles. The existing barcodes table stores sheet
     * sticker sizes, and printers stores receipt printers, so neither can
     * hold these dot-level column offsets.
     */
    public function up(): void
    {
        Schema::create('label_print_profiles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->string('name', 80);
            $table->string('printer_name', 120);
            $table->unsignedSmallInteger('printer_dpi')->default(203);
            $table->unsignedInteger('width')->default(800);
            $table->unsignedInteger('height')->default(140);
            $table->unsignedTinyInteger('columns')->default(3);
            $table->integer('col1_x')->default(5);
            $table->integer('col2_x')->default(271);
            $table->integer('col3_x')->default(537);
            $table->integer('barcode_x')->default(20);
            $table->integer('barcode_y')->default(15);
            $table->decimal('barcode_width', 4, 2)->default(1.50);
            $table->unsignedSmallInteger('barcode_height')->default(35);
            $table->integer('sku_x')->default(65);
            $table->integer('sku_y')->default(60);
            $table->unsignedSmallInteger('sku_font_size')->default(20);
            $table->string('sku_font_weight', 16)->default('bold');
            $table->integer('price_x')->default(30);
            $table->integer('price_y')->default(85);
            $table->unsignedSmallInteger('price_font_size')->default(24);
            $table->string('vertical_text', 80)->nullable();
            $table->integer('vertical_x')->default(250);
            $table->integer('vertical_y')->default(15);
            $table->unsignedSmallInteger('vertical_font_size')->default(15);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->unique(['business_id', 'name']);
            $table->index(['business_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('label_print_profiles');
    }
};

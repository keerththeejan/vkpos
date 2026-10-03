<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Product-name placement for the existing Zebra layout.
     * Column, barcode, SKU, price, and vertical offsets stay as they are.
     */
    public function up(): void
    {
        Schema::table('label_print_profiles', function (Blueprint $table) {
            $table->integer('product_name_x')->default(25)->after('vertical_font_size');
            $table->integer('product_name_y')->default(110)->after('product_name_x');
            $table->unsignedSmallInteger('product_name_font_size')->default(20)->after('product_name_y');
            $table->unsignedSmallInteger('product_name_font_width')->default(12)->after('product_name_font_size');
            $table->string('product_name_font_weight', 16)->default('bold')->after('product_name_font_width');
            $table->unsignedSmallInteger('product_name_max_width')->default(220)->after('product_name_font_weight');
            $table->unsignedTinyInteger('product_name_max_lines')->default(1)->after('product_name_max_width');
            $table->string('product_name_align', 16)->default('center')->after('product_name_max_lines');
            $table->boolean('show_product_name')->default(true)->after('product_name_align');
            $table->boolean('product_name_wrap')->default(false)->after('show_product_name');
        });
    }

    public function down(): void
    {
        Schema::table('label_print_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'product_name_x',
                'product_name_y',
                'product_name_font_size',
                'product_name_font_width',
                'product_name_font_weight',
                'product_name_max_width',
                'product_name_max_lines',
                'product_name_align',
                'show_product_name',
                'product_name_wrap',
            ]);
        });
    }
};

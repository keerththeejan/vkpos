<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SKU and price can be aligned independently of the product name.
     */
    public function up(): void
    {
        Schema::table('label_print_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('label_print_profiles', 'sku_align')) {
                $table->string('sku_align', 16)->default('center')->after('sku_font_weight');
            }
            if (! Schema::hasColumn('label_print_profiles', 'price_align')) {
                $table->string('price_align', 16)->default('center')->after('price_font_size');
            }
        });
    }

    public function down(): void
    {
        Schema::table('label_print_profiles', function (Blueprint $table) {
            $drop = [];
            foreach (['sku_align', 'price_align'] as $column) {
                if (Schema::hasColumn('label_print_profiles', $column)) {
                    $drop[] = $column;
                }
            }
            if ($drop) {
                $table->dropColumn($drop);
            }
        });
    }
};

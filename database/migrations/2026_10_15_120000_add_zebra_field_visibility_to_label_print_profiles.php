<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Designer toggles for barcode, SKU, and price. Existing labels keep all three visible.
     */
    public function up(): void
    {
        Schema::table('label_print_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('label_print_profiles', 'show_barcode')) {
                $table->boolean('show_barcode')->default(true)->after('show_product_name');
            }
            if (! Schema::hasColumn('label_print_profiles', 'show_sku')) {
                $table->boolean('show_sku')->default(true)->after('show_barcode');
            }
            if (! Schema::hasColumn('label_print_profiles', 'show_price')) {
                $table->boolean('show_price')->default(true)->after('show_sku');
            }
        });
    }

    public function down(): void
    {
        Schema::table('label_print_profiles', function (Blueprint $table) {
            $drop = [];
            foreach (['show_barcode', 'show_sku', 'show_price'] as $column) {
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

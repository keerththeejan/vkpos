<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extend product_serial_numbers for purchase receiving + POS selling.
     */
    public function up(): void
    {
        if (! Schema::hasTable('product_serial_numbers')) {
            return;
        }

        Schema::table('product_serial_numbers', function (Blueprint $table) {
            if (! Schema::hasColumn('product_serial_numbers', 'variation_id')) {
                $table->unsignedInteger('variation_id')->nullable()->after('product_id')->index();
            }
            if (! Schema::hasColumn('product_serial_numbers', 'location_id')) {
                $table->unsignedInteger('location_id')->nullable()->after('business_id')->index();
            }
            if (! Schema::hasColumn('product_serial_numbers', 'purchase_id')) {
                $table->unsignedInteger('purchase_id')->nullable()->after('location_id')->index();
            }
            if (! Schema::hasColumn('product_serial_numbers', 'purchase_line_id')) {
                $table->unsignedInteger('purchase_line_id')->nullable()->after('purchase_id')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('product_serial_numbers')) {
            return;
        }

        Schema::table('product_serial_numbers', function (Blueprint $table) {
            foreach (['variation_id', 'location_id', 'purchase_id', 'purchase_line_id'] as $col) {
                if (Schema::hasColumn('product_serial_numbers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

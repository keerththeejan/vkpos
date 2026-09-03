<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Increase quantity column precision so related units such as G/KG
     * can store values like 1 G = 0.001 KG and 10.25 G = 0.010250 KG.
     * Existing values are preserved.
     */
    public function up()
    {
        $columns = [
            'variation_location_details' => [
                'qty_available' => 'DECIMAL(22,6) NOT NULL DEFAULT 0',
            ],
            'products' => [
                'alert_quantity' => 'DECIMAL(22,6) NULL DEFAULT NULL',
            ],
            'purchase_lines' => [
                'quantity' => 'DECIMAL(22,6) NOT NULL DEFAULT 0',
                'secondary_unit_quantity' => 'DECIMAL(22,6) NOT NULL DEFAULT 0',
                'quantity_sold' => 'DECIMAL(22,6) NOT NULL DEFAULT 0',
                'quantity_adjusted' => 'DECIMAL(22,6) NOT NULL DEFAULT 0',
                'quantity_returned' => 'DECIMAL(22,6) NOT NULL DEFAULT 0',
                'po_quantity_purchased' => 'DECIMAL(22,6) NOT NULL DEFAULT 0',
                'mfg_quantity_used' => 'DECIMAL(22,6) NOT NULL DEFAULT 0',
            ],
            'stock_adjustment_lines' => [
                'quantity' => 'DECIMAL(22,6) NOT NULL',
                'secondary_unit_quantity' => 'DECIMAL(22,6) NOT NULL DEFAULT 0',
            ],
            'transaction_sell_lines' => [
                'quantity' => 'DECIMAL(22,6) NOT NULL DEFAULT 0',
                'secondary_unit_quantity' => 'DECIMAL(22,6) NOT NULL DEFAULT 0',
                'quantity_returned' => 'DECIMAL(22,6) NOT NULL DEFAULT 0',
                'so_quantity_invoiced' => 'DECIMAL(22,6) NOT NULL DEFAULT 0',
            ],
            'transaction_sell_lines_purchase_lines' => [
                'quantity' => 'DECIMAL(22,6) NOT NULL DEFAULT 0',
                'qty_returned' => 'DECIMAL(22,6) NOT NULL DEFAULT 0',
            ],
        ];

        foreach ($columns as $table => $defs) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach ($defs as $column => $definition) {
                if (Schema::hasColumn($table, $column)) {
                    DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` {$definition}");
                }
            }
        }

        if (Schema::hasTable('business') && Schema::hasColumn('business', 'quantity_precision')) {
            DB::table('business')
                ->where('quantity_precision', '<', 4)
                ->update(['quantity_precision' => 4]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Precision increase is safe to keep; do not shrink columns and lose data.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE users MODIFY surname VARCHAR(191) NULL DEFAULT NULL');
        DB::statement('ALTER TABLE business_locations MODIFY zip_code VARCHAR(20) NOT NULL');
        DB::statement('ALTER TABLE business MODIFY tax_label_1 VARCHAR(50) NULL DEFAULT NULL');
        DB::statement('ALTER TABLE business MODIFY tax_label_2 VARCHAR(50) NULL DEFAULT NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE users MODIFY surname CHAR(10) NULL DEFAULT NULL');
        DB::statement('ALTER TABLE business_locations MODIFY zip_code CHAR(7) NOT NULL');
        DB::statement('ALTER TABLE business MODIFY tax_label_1 VARCHAR(10) NULL DEFAULT NULL');
        DB::statement('ALTER TABLE business MODIFY tax_label_2 VARCHAR(10) NULL DEFAULT NULL');
    }
};

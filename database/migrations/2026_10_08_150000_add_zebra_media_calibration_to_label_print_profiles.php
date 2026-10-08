<?php

use App\Services\ZebraLabelGeometry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store the 30×15 mm gap and home offsets with the Zebra profile,
     * and move untouched 800×140 layouts onto the 3-up sticker geometry.
     */
    public function up(): void
    {
        Schema::table('label_print_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('label_print_profiles', 'label_gap_mm')) {
                $table->decimal('label_gap_mm', 5, 2)->default(2.00)->after('columns');
            }
            if (! Schema::hasColumn('label_print_profiles', 'top_offset_mm')) {
                $table->decimal('top_offset_mm', 5, 2)->default(0)->after('label_gap_mm');
            }
            if (! Schema::hasColumn('label_print_profiles', 'left_offset_mm')) {
                $table->decimal('left_offset_mm', 5, 2)->default(0)->after('top_offset_mm');
            }
        });

        $dots = app(ZebraLabelGeometry::class)->defaultProfileDots();
        DB::table('label_print_profiles')
            ->where('width', 800)
            ->where('height', 140)
            ->where('col1_x', 5)
            ->where('col2_x', 271)
            ->where('col3_x', 537)
            ->update($dots);
    }

    public function down(): void
    {
        Schema::table('label_print_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('label_print_profiles', 'left_offset_mm')) {
                $table->dropColumn(['label_gap_mm', 'top_offset_mm', 'left_offset_mm']);
            }
        });
    }
};

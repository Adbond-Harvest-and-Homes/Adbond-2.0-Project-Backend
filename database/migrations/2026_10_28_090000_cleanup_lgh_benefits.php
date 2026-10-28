<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $toRemove = DB::table('benefits')
            ->whereIn('name', ['20-Year Leasehold', '1-Bedroom and Studio Options', '12-Month Payment Plan'])
            ->pluck('id');

        if ($toRemove->count() > 0) {
            // package_benefits has no DB-level FK constraint, so clean up any
            // attachments manually before removing the benefit rows themselves.
            DB::table('package_benefits')->whereIn('benefit_id', $toRemove)->delete();
            DB::table('benefits')->whereIn('id', $toRemove)->delete();
        }

        DB::table('benefits')
            ->where('name', 'Free Plot of Land')
            ->update(['name' => 'Free Plot of Land at Legacy Green Homestead(LCH)']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('benefits')
            ->where('name', 'Free Plot of Land at Legacy Green Homestead(LCH)')
            ->update(['name' => 'Free Plot of Land']);

        // The removed benefit rows (and their package_benefits attachments)
        // are not recreated on rollback - re-run the Benefits seeder instead.
    }
};

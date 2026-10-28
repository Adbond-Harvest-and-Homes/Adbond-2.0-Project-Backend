<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Benefit icon URLs were stored at seed-time using env('APP_URL'), which
     * was pointing at a different/older server (api-v2.adbondharvestandhomes.com)
     * instead of this one. Rewrite the already-stored URLs now that APP_URL
     * is corrected, so this doesn't depend on re-running the seeder (which
     * only inserts missing rows, it never updates existing ones).
     */
    public function up(): void
    {
        $benefits = DB::table('benefits')->get();

        foreach ($benefits as $benefit) {
            $fixed = str_replace('api-v2.adbondharvestandhomes.com', 'api.adbondharvestandhomes.com', $benefit->icon);
            if ($fixed !== $benefit->icon) {
                DB::table('benefits')->where('id', $benefit->id)->update(['icon' => $fixed]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not reversed - the old host was wrong, there's nothing worth restoring.
    }
};

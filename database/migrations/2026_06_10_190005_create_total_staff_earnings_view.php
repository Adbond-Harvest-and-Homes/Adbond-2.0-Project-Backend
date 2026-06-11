<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("DROP VIEW IF EXISTS staff_total_earnings");
        DB::statement("
            CREATE VIEW staff_total_earnings AS
            SELECT 
                user_id,
                SUM(commission_after_tax) AS total_earnings,
                SUM(CASE WHEN type = 'direct' THEN commission_after_tax ELSE 0 END) AS total_direct_earnings,
                SUM(CASE WHEN type = 'indirect' THEN commission_after_tax ELSE 0 END) AS total_indirect_earnings
            FROM staff_commission_earnings
            GROUP BY user_id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS staff_total_earnings");
    }
};

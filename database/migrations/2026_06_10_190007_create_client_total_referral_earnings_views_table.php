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
        DB::statement("
            CREATE VIEW client_total_referral_earnings_view AS
            SELECT 
                client_id,
                SUM(amount_after_tax) AS total_earnings
            FROM client_commission_earnings
            GROUP BY client_id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS client_total_referral_earnings_view");
    }
};

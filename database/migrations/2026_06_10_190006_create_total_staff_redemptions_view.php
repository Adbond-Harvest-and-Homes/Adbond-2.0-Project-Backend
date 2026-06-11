<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use app\Enums\RedemptionStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            CREATE VIEW staff_total_redemptions AS
            SELECT 
                user_id,
                SUM(amount) AS total_redemptions
            FROM staff_commission_redemptions
            WHERE status = '" . RedemptionStatus::PENDING->value . "'
            GROUP BY user_id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS staff_total_redemptions");
    }
};

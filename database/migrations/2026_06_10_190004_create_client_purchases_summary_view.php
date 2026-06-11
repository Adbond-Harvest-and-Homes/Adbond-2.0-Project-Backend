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
            CREATE VIEW client_purchases_summary_view AS
            SELECT 
                DATE(client_packages.purchase_completed_at) AS purchase_date, 
                SUM(orders.amount_payable) AS total_amount
            FROM client_packages
            JOIN orders 
                ON client_packages.purchase_id = orders.id 
                AND client_packages.origin = 'order'
            GROUP BY DATE(client_packages.purchase_completed_at)
            ORDER BY purchase_date DESC
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS client_purchases_summary_view');
    }
};

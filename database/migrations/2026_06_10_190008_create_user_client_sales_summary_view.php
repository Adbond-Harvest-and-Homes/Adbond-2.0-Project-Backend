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
            CREATE VIEW user_client_sales_summary AS
            SELECT 
                u.id AS user_id,
                COUNT(DISTINCT c.id) AS total_clients,
                COALESCE(COUNT(o.id), 0) AS sales_count,
                COALESCE(SUM(o.amount_payable), 0) AS total_sales
            FROM users u
            LEFT JOIN clients c ON c.referer_id = u.id AND c.referer_type = 'app\\\\Models\\\\User'
            LEFT JOIN orders o ON o.client_id = c.id AND o.completed = 1
            GROUP BY u.id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS user_client_sales_summary");
    }
};

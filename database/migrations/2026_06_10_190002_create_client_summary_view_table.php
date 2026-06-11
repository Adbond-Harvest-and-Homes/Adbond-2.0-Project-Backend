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
            CREATE OR REPLACE VIEW client_summary AS
            SELECT 
                (SELECT COUNT(*) FROM clients) AS total_clients,
                (SELECT SUM(CASE WHEN activated = 1 THEN 1 ELSE 0 END) FROM clients) AS active_clients,
                (SELECT SUM(CASE WHEN activated = 0 THEN 1 ELSE 0 END) FROM clients) AS inactive_clients,
                (SELECT COUNT(DISTINCT client_id) FROM client_packages) AS purchasing_clients,
                (SELECT COUNT(*) FROM clients 
                WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(CURRENT_DATE, '%Y-%m')) AS new_clients,
                DATE_FORMAT(CURRENT_DATE, '%Y-%m') AS current_month
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_summary_view');
    }
};

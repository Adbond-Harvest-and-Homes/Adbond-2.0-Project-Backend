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
        DB::statement("DROP VIEW IF EXISTS staff_type_user_summary");
        DB::statement("
            CREATE VIEW staff_type_user_summary AS
            SELECT 
                st.id AS staff_type_id,
                st.name AS staff_type_name,
                COUNT(u.id) AS total_users,
                SUM(CASE WHEN u.activated = 1 THEN 1 ELSE 0 END) AS active_users,
                COALESCE(SUM(ucss.total_sales), 0) AS total_sales,
                COALESCE(SUM(ste.total_earnings), 0) AS total_earnings
            FROM staff_types st
            LEFT JOIN users u ON u.staff_type_id = st.id
            LEFT JOIN user_client_sales_summary ucss ON ucss.user_id = u.id
            LEFT JOIN staff_total_earnings ste ON ste.user_id = u.id
            GROUP BY st.id, st.name
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS staff_type_user_summary");
    }
};

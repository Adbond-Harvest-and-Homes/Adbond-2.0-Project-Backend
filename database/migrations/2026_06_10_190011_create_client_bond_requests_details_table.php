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
        /*
        -- =============================================================
            -- client_bond_requests_detail
            -- Enriched requests joined with their parent bond.
            -- Useful for filtering/grouping in reports or further queries.
        -- =============================================================
        */
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::statement("DROP VIEW IF EXISTS client_bond_requests_detail");
            DB::statement(
                "CREATE VIEW client_bond_requests_detail AS
                    SELECT
                        cbr.id                          AS request_id,
                        cbr.client_bond_id,
                        cbr.type,                       -- 'liquidation' | 'renewal'
                        cbr.approved,                   -- NULL = pending, TRUE = approved, FALSE = rejected
                        cbr.rejected_reason,
                        cbr.created_at                  AS requested_at,
                        strftime('%m', cbr.created_at)  AS request_month,
                        strftime('%Y', cbr.created_at)  AS request_year,
                        cb.client_id,
                        cb.status                       AS bond_status,
                        cb.started,
                        cb.ended,
                        cb.redeemed
                    FROM client_bond_requests cbr
                    INNER JOIN client_bonds cb ON cb.id = cbr.client_bond_id;"
            );
        } else {
            DB::statement(
                "CREATE OR REPLACE VIEW client_bond_requests_detail AS
                    SELECT
                        cbr.id                          AS request_id,
                        cbr.client_bond_id,
                        cbr.type,                       -- 'liquidation' | 'renewal'
                        cbr.approved,                   -- NULL = pending, TRUE = approved, FALSE = rejected
                        cbr.rejected_reason,
                        cbr.created_at                  AS requested_at,
                        MONTH(cbr.created_at)           AS request_month,
                        YEAR(cbr.created_at)            AS request_year,
                        cb.client_id,
                        cb.status                       AS bond_status,
                        cb.started,
                        cb.ended,
                        cb.redeemed
                    FROM client_bond_requests cbr
                    INNER JOIN client_bonds cb ON cb.id = cbr.client_bond_id;"
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS client_bond_requests_detail");
    }
};

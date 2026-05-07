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
            -- client_bond_summary
            -- Single-row summary for the dashboard.
            -- NOTE: MySQL views do not support CTEs, so subqueries are
            --       used instead.
        -- =============================================================
        */
        DB::statement("
            CREATE OR REPLACE VIEW client_bond_summary AS
            SELECT
                -- Total pending requests (approved IS NULL, across all types)
                COUNT(DISTINCT CASE
                    WHEN cbr.approved IS NULL
                    THEN cbr.id
                END)                                                        AS total_pending_requests,

                -- Active investments (started = true AND ended = false)
                COUNT(DISTINCT CASE
                    WHEN cb.started = 1 AND cb.ended = 0
                    THEN cb.id
                END)                                                        AS active_investments,

                -- Total renewals (all time, approved only)
                COUNT(DISTINCT CASE
                    WHEN cbr.type = 'renewal'
                        AND cbr.approved = 1
                    THEN cbr.id
                END)                                                        AS total_renewals,

                -- Total renewals this month
                COUNT(DISTINCT CASE
                    WHEN cbr.type = 'renewal'
                        AND cbr.approved = 1
                        AND MONTH(cbr.created_at) = MONTH(CURRENT_DATE())
                        AND YEAR(cbr.created_at)  = YEAR(CURRENT_DATE())
                    THEN cbr.id
                END)                                                        AS total_renewals_this_month,

                -- Total liquidations (all time, approved only)
                COUNT(DISTINCT CASE
                    WHEN cbr.type = 'liquidation'
                        AND cbr.approved = 1
                    THEN cbr.id
                END)                                                        AS total_liquidations,

                -- Total liquidations this month
                COUNT(DISTINCT CASE
                    WHEN cbr.type = 'liquidation'
                        AND cbr.approved = 1
                        AND MONTH(cbr.created_at) = MONTH(CURRENT_DATE())
                        AND YEAR(cbr.created_at)  = YEAR(CURRENT_DATE())
                    THEN cbr.id
                END)                                                        AS total_liquidations_this_month,

                -- Total amount liquidated: capital + all payouts on approved liquidated bonds (all time)
                COALESCE((
                    SELECT SUM(liq.current_capital + COALESCE(liq.total_payout, 0))
                    FROM (
                        SELECT cb2.id AS bond_id,
                               cb2.current_capital,
                               (
                                   SELECT SUM(cbp.payout_amount)
                                   FROM client_bond_payouts cbp
                                   WHERE cbp.client_bond_id = cb2.id
                               ) AS total_payout
                        FROM client_bonds cb2
                        WHERE EXISTS (
                            SELECT 1 FROM client_bond_requests cbr2
                            WHERE cbr2.client_bond_id = cb2.id
                              AND cbr2.type           = 'liquidation'
                              AND cbr2.approved       = 1
                        )
                    ) liq
                ), 0)                                                       AS total_amount_liquidated,

                -- Total amount liquidated this month: capital + payouts issued this month on approved liquidated bonds
                COALESCE((
                    SELECT SUM(liq.current_capital + COALESCE(liq.total_payout_this_month, 0))
                    FROM (
                        SELECT cb2.id AS bond_id,
                               cb2.current_capital,
                               (
                                   SELECT SUM(cbp.payout_amount)
                                   FROM client_bond_payouts cbp
                                   WHERE cbp.client_bond_id = cb2.id
                                     AND MONTH(cbp.created_at) = MONTH(CURRENT_DATE())
                                     AND YEAR(cbp.created_at)  = YEAR(CURRENT_DATE())
                               ) AS total_payout_this_month
                        FROM client_bonds cb2
                        WHERE EXISTS (
                            SELECT 1 FROM client_bond_requests cbr2
                            WHERE cbr2.client_bond_id = cb2.id
                              AND cbr2.type           = 'liquidation'
                              AND cbr2.approved       = 1
                              AND MONTH(cbr2.created_at) = MONTH(CURRENT_DATE())
                              AND YEAR(cbr2.created_at)  = YEAR(CURRENT_DATE())
                        )
                    ) liq
                ), 0)                                                       AS total_amount_liquidated_this_month,

                -- Total rental income paid out across all bonds (all time)
                COALESCE((
                    SELECT SUM(payout_amount) FROM client_bond_payouts
                ), 0)                                                       AS total_payouts_paid,

                -- Total rental income paid out this month (all bonds)
                COALESCE((
                    SELECT SUM(payout_amount)
                    FROM client_bond_payouts
                    WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
                      AND YEAR(created_at)  = YEAR(CURRENT_DATE())
                ), 0)                                                       AS total_payouts_paid_this_month

            FROM client_bonds cb
            LEFT JOIN client_bond_requests cbr ON cbr.client_bond_id = cb.id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS client_bond_summary");
    }
};
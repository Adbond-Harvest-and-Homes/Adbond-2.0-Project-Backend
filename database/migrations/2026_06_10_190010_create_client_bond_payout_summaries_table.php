<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * View columns:
     *  - client_id
     *  - total_payouts            : SUM of all payout_amount from client_bond_payouts
     *  - total_payout_count       : number of payout records
     *  - total_current_capital    : SUM of current_capital across ALL bonds (any status)
     *  - active_bond_count        : number of active bonds (started=1, ended=0)
     *  - expected_annual_payout   : annualised income across bonds that are either
     *                               (a) active (started=1, ended=0), OR
     *                               (b) not yet started but next_capital_payout falls
     *                                   within the current calendar year.
     *                               Handles both fixed-amount and percentage-based
     *                               net_rental_income, normalised to a yearly figure
     *                               regardless of net_rental_income_timeline.
     *  - next_payout_date         : closest upcoming next_capital_payout (>= today)
     *                               across all bonds that have not ended (ended=0),
     *                               regardless of whether they have started.
     *                               NULL if no upcoming payout date is set.
     */
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW client_bond_payout_summary AS

            SELECT
                c.client_id,

                -- ── Realised payouts (from client_bond_payouts — all records) ─────────
                COALESCE(p.total_payouts,      0) AS total_payouts,
                COALESCE(p.total_payout_count, 0) AS total_payout_count,

                -- ── Capital across ALL bonds regardless of status ──────────────────────
                COALESCE(bc.total_current_capital, 0) AS total_current_capital,

                -- ── Active-bond count (started=1, ended=0) ────────────────────────────
                COALESCE(ab.active_bond_count, 0) AS active_bond_count,

                -- ── Expected annual payout ────────────────────────────────────────────
                -- Bonds included: active (started=1, ended=0) OR not yet started but
                -- next_capital_payout falls within the current calendar year.
                -- Per-period income is resolved as fixed or % of current_capital,
                -- then annualised via the timeline multiplier (weekly×52, monthly×12, yearly×1).
                COALESCE(ab.expected_annual_payout, 0) AS expected_annual_payout,

                -- ── Next payout date ──────────────────────────────────────────────────
                -- Closest future (or today) next_capital_payout across all bonds where
                -- ended=0 (active or not yet started). NULL if none scheduled.
                np.next_payout_date

            FROM (
                -- Distinct client_ids across both tables so we never miss a client
                SELECT DISTINCT client_id FROM client_bonds
                UNION
                SELECT DISTINCT client_id FROM client_bond_payouts
            ) c

            -- ── Payout totals (all records, no status filter) ─────────────────────────
            LEFT JOIN (
                SELECT
                    client_id,
                    SUM(payout_amount) AS total_payouts,
                    COUNT(*)           AS total_payout_count
                FROM client_bond_payouts
                GROUP BY client_id
            ) p ON p.client_id = c.client_id

            -- ── Capital totals (ALL bonds, no status filter) ──────────────────────────
            LEFT JOIN (
                SELECT
                    client_id,
                    SUM(current_capital) AS total_current_capital
                FROM client_bonds
                GROUP BY client_id
            ) bc ON bc.client_id = c.client_id

            -- ── Expected-annual-payout calculations ───────────────────────────────────
            -- Includes bonds that are:
            --   (a) active: started=1, ended=0
            --   (b) not yet started but next_capital_payout is within the current year
            LEFT JOIN (
                SELECT
                    client_id,
                    COUNT(*) AS active_bond_count,

                    SUM(
                        -- 1. Resolve income amount per period
                        CASE
                            WHEN LOWER(net_rental_income_measurement) = 'percentage'
                                THEN current_capital * (net_rental_income / 100)
                            ELSE
                                net_rental_income   -- fixed amount
                        END

                        -- 2. Annualise by multiplying with the periods-per-year factor
                        *

                        CASE LOWER(net_rental_income_timeline)
                            WHEN 'weekly'  THEN 52
                            WHEN 'monthly' THEN 12
                            WHEN 'yearly'  THEN 1
                            ELSE 1          -- safe default for unexpected values
                        END
                    ) AS expected_annual_payout

                FROM client_bonds
                WHERE
                    -- (a) active bonds
                    (started = 1 AND ended = 0)
                    OR
                    -- (b) not yet started, but a payout is expected this calendar year
                    (started = 0 AND YEAR(next_capital_payout) = YEAR(CURDATE()))
                GROUP BY client_id
            ) ab ON ab.client_id = c.client_id

            -- ── Next payout date ──────────────────────────────────────────────────────
            -- MIN of next_capital_payout >= today, across all non-ended bonds
            -- (ended=0 covers both active and not-yet-started)
            LEFT JOIN (
                SELECT
                    client_id,
                    MIN(next_capital_payout) AS next_payout_date
                FROM client_bonds
                WHERE ended = 0
                  AND next_capital_payout >= CURDATE()
                GROUP BY client_id
            ) np ON np.client_id = c.client_id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS client_payout_summary');
    }
};
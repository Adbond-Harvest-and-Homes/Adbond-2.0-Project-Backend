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
        DB::statement("DROP VIEW IF EXISTS client_assets_view");
        DB::statement("
            CREATE VIEW client_assets_view AS
            SELECT
                clients.id AS client_id,
                clients.firstname AS firstname,
                clients.lastname AS lastname,
                COUNT(client_packages.id) AS total_packages,
                COUNT(
                    CASE
                        WHEN client_packages.origin = 'Order' AND orders.completed = 0 THEN 1
                        ELSE NULL
                    END
                ) AS total_active,
                COUNT(
                    CASE
                        WHEN client_packages.origin = 'Order' AND orders.completed != 0 THEN 1
                        ELSE NULL
                    END
                ) AS total_inactive,
                COALESCE(SUM(
                    CASE
                        WHEN client_packages.origin = 'order' THEN packages.amount * orders.units
                        WHEN client_packages.origin = 'offer' THEN packages.amount * offers.units
                        ELSE 0
                    END
                ), 0) AS total_worth,
                COALESCE(SUM(
                    CASE
                        WHEN client_packages.origin = 'order' THEN orders.unit_price * orders.units
                        WHEN client_packages.origin = 'offer' THEN offers.price
                        ELSE 0
                    END
                ), 0) AS total_purchase_worth
            FROM clients
            LEFT JOIN client_packages ON client_packages.client_id = clients.id AND client_packages.deleted_at IS NULL
            LEFT JOIN packages ON client_packages.package_id = packages.id
            LEFT JOIN orders ON client_packages.origin = 'order' AND client_packages.purchase_id = orders.id
            LEFT JOIN offers ON client_packages.origin = 'offer' AND client_packages.purchase_id = offers.id
            GROUP BY clients.id, clients.firstname, clients.lastname;
        ");

        DB::statement("DROP VIEW IF EXISTS client_purchases_summary_view");
        DB::statement("
            CREATE VIEW client_purchases_summary_view AS
            SELECT
                DATE(client_packages.purchase_completed_at) AS purchase_date,
                SUM(orders.amount_payable) AS total_amount
            FROM client_packages
            JOIN orders
                ON client_packages.purchase_id = orders.id
                AND client_packages.origin = 'order'
            WHERE client_packages.deleted_at IS NULL
            GROUP BY DATE(client_packages.purchase_completed_at)
            ORDER BY purchase_date DESC
        ");

        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::statement("DROP VIEW IF EXISTS client_summary");
            DB::statement("
                CREATE VIEW client_summary AS
                SELECT
                    (SELECT COUNT(*) FROM clients) AS total_clients,
                    (SELECT SUM(CASE WHEN activated = 1 THEN 1 ELSE 0 END) FROM clients) AS active_clients,
                    (SELECT SUM(CASE WHEN activated = 0 THEN 1 ELSE 0 END) FROM clients) AS inactive_clients,
                    (SELECT COUNT(DISTINCT client_id) FROM client_packages WHERE deleted_at IS NULL) AS purchasing_clients,
                    (SELECT COUNT(*) FROM clients
                    WHERE strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now')) AS new_clients,
                    strftime('%Y-%m', 'now') AS current_month
            ");
        } else {
            DB::statement("
                CREATE OR REPLACE VIEW client_summary AS
                SELECT
                    (SELECT COUNT(*) FROM clients) AS total_clients,
                    (SELECT SUM(CASE WHEN activated = 1 THEN 1 ELSE 0 END) FROM clients) AS active_clients,
                    (SELECT SUM(CASE WHEN activated = 0 THEN 1 ELSE 0 END) FROM clients) AS inactive_clients,
                    (SELECT COUNT(DISTINCT client_id) FROM client_packages WHERE deleted_at IS NULL) AS purchasing_clients,
                    (SELECT COUNT(*) FROM clients
                    WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(CURRENT_DATE, '%Y-%m')) AS new_clients,
                    DATE_FORMAT(CURRENT_DATE, '%Y-%m') AS current_month
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS client_assets_view");
        DB::statement("
            CREATE VIEW client_assets_view AS
            SELECT
                clients.id AS client_id,
                clients.firstname AS firstname,
                clients.lastname AS lastname,
                COUNT(client_packages.id) AS total_packages,
                COUNT(
                    CASE
                        WHEN client_packages.origin = 'Order' AND orders.completed = 0 THEN 1
                        ELSE NULL
                    END
                ) AS total_active,
                COUNT(
                    CASE
                        WHEN client_packages.origin = 'Order' AND orders.completed != 0 THEN 1
                        ELSE NULL
                    END
                ) AS total_inactive,
                COALESCE(SUM(
                    CASE
                        WHEN client_packages.origin = 'order' THEN packages.amount * orders.units
                        WHEN client_packages.origin = 'offer' THEN packages.amount * offers.units
                        ELSE 0
                    END
                ), 0) AS total_worth,
                COALESCE(SUM(
                    CASE
                        WHEN client_packages.origin = 'order' THEN orders.unit_price * orders.units
                        WHEN client_packages.origin = 'offer' THEN offers.price
                        ELSE 0
                    END
                ), 0) AS total_purchase_worth
            FROM clients
            LEFT JOIN client_packages ON client_packages.client_id = clients.id
            LEFT JOIN packages ON client_packages.package_id = packages.id
            LEFT JOIN orders ON client_packages.origin = 'order' AND client_packages.purchase_id = orders.id
            LEFT JOIN offers ON client_packages.origin = 'offer' AND client_packages.purchase_id = offers.id
            GROUP BY clients.id, clients.firstname, clients.lastname;
        ");

        DB::statement("DROP VIEW IF EXISTS client_purchases_summary_view");
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

        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::statement("DROP VIEW IF EXISTS client_summary");
            DB::statement("
                CREATE VIEW client_summary AS
                SELECT
                    (SELECT COUNT(*) FROM clients) AS total_clients,
                    (SELECT SUM(CASE WHEN activated = 1 THEN 1 ELSE 0 END) FROM clients) AS active_clients,
                    (SELECT SUM(CASE WHEN activated = 0 THEN 1 ELSE 0 END) FROM clients) AS inactive_clients,
                    (SELECT COUNT(DISTINCT client_id) FROM client_packages) AS purchasing_clients,
                    (SELECT COUNT(*) FROM clients
                    WHERE strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now')) AS new_clients,
                    strftime('%Y-%m', 'now') AS current_month
            ");
        } else {
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
    }
};

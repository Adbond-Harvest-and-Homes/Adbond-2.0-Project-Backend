<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW client_assets_view AS
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS client_assets_view");
    }
};

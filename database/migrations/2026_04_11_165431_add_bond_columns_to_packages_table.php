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
        Schema::table('packages', function (Blueprint $table) {
            $table->integer("bond_slots")->nullable()->after("installment_option");
            $table->integer("bond_available_slots")->nullable()->after("bond_slots");
            $table->double("bond_slots_amount")->nullable()->after("bond_available_slots");
            $table->string("bond_ownership_type")->nullable()->after("bond_slots_amount");
            $table->integer("bond_count_down")->nullable()->after("bond_ownership_type");
            $table->string("bond_count_down_metric")->nullable()->after("bond_count_down");
            $table->integer("bond_investment_duration")->nullable()->after("bond_count_down_metric");
            $table->string("bond_investment_duration_metric")->nullable()->after("bond_investment_duration");
            $table->double("bond_net_rental_income")->nullable()->after("bond_investment_duration_metric"); // the interest to be earned
            $table->string("bond_net_rental_income_measurement")->nullable()->after("bond_net_rental_income"); // fixed or percentage
            $table->string("bond_net_rental_income_timeline")->nullable()->after("bond_net_rental_income_measurement"); // weekly, monthly, etc
            $table->double("bond_asset_appreciation")->nullable()->after("bond_net_rental_income_timeline"); // the appreciation of the asset and hence your capital
            $table->string("bond_asset_appreciation_measurement")->nullable()->after("bond_asset_appreciation"); // fixed or percentage
            $table->string("bond_asset_appreciation_timeline")->nullable()->after("bond_asset_appreciation_measurement"); // weekly, monthly, etc
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            //
        });
    }
};

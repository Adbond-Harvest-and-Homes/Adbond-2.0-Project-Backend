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
        Schema::create('client_bonds', function (Blueprint $table) {
            $table->id();
            $table->foreignId("client_id");
            $table->foreignId("package_id");
            $table->foreignId("order_id");
            $table->date("start_date")->nullable();
            $table->date("end_date")->nullable();
            $table->boolean("started")->default(false);
            $table->boolean("ended")->default(false);
            $table->date("next_capital_payout")->nullable();
            $table->double("start_capital");
            $table->double("current_capital");
            $table->foreignId("parent_bond_id")->nullable();
            $table->integer("count_down");
            $table->string("count_down_metric");
            $table->integer("duration");
            $table->string("duration_metric");
            $table->double("net_rental_income"); // the interest to be earned
            $table->string("net_rental_income_measurement"); // fixed or percentage
            $table->string("net_rental_income_timeline"); // weekly, monthly, etc
            $table->double("asset_appreciation"); // the appreciation of the asset and hence your capital
            $table->string("asset_appreciation_measurement"); // fixed or percentage
            $table->string("asset_appreciation_timeline"); 
            $table->foreignId("mou_file_id")->nullable();
            $table->boolean("docs_uploaded")->default(false);
            $table->boolean("mou_sent")->default(false);
            $table->boolean("redeemed")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_bonds');
    }
};

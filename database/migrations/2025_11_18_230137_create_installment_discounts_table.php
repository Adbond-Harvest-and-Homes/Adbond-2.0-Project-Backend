<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('installment_discounts', function (Blueprint $table) {
            $table->id();
            $table->integer("duration");
            $table->double("discount");
            $table->timestamps();
        });

        Artisan::call("db:seed", ["--class" => "InstallmentDurationDiscounts"]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installment_discounts');
    }
};

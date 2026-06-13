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
        Schema::create('client_metrics', function (Blueprint $table) {
            $table->id();
            $table->integer("total")->nullable();
            $table->integer("previous_total")->nullable();
            $table->integer("active_total")->nullable();
            $table->integer("previous_active_total")->nullable();
            $table->timestamps();
        });

        var_dump(config('database.connections.mysql'));
        Artisan::call('db:seed', array('--class' => 'InitialClientMetrics'));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_metrics');
    }
};

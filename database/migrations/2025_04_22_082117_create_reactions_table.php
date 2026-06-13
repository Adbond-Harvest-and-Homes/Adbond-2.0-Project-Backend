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
        Schema::create('reactions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->morphs('user');
            $table->boolean('reaction')->nullable();
            $table->morphs('entity');
            $table->timestamps();
        });
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE reactions CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reactions');
    }
};

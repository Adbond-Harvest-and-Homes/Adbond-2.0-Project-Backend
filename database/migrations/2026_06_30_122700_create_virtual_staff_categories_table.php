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
        Schema::create('virtual_staff_categories', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->timestamps();
        });
        Artisan::call("db:seed", ["--class" => "VirtualStaffCategories"]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('virtual_staff_categories');
    }
};

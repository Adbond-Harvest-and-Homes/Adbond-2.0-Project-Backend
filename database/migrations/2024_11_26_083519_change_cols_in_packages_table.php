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
        if (Schema::hasColumn('packages', 'benefits')) {
            try {
                Schema::table('packages', function (Blueprint $table) {
                    $table->dropColumn("benefits");
                });
            } catch (\Exception $e) {
                // Ignore drop error
            }
        }
        if (Schema::hasColumn('packages', 'state_id')) {
            try {
                Schema::table('packages', function (Blueprint $table) {
                    $table->dropConstrainedForeignId("state_id");
                });
            } catch (\Exception $e) {
                try {
                    Schema::table('packages', function (Blueprint $table) {
                        $table->dropColumn("state_id");
                    });
                } catch (\Exception $ex) {
                    // Ignore drop error
                }
            }
        }
        if (!Schema::hasColumn('packages', 'state')) {
            try {
                Schema::table('packages', function (Blueprint $table) {
                    $table->string("state")->after("name");
                });
            } catch (\Exception $e) {
                // Ignore error
            }
        }
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

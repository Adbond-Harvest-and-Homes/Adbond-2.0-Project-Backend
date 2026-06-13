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
        Schema::table('tables', function (Blueprint $table) {
            // Get all table names in the current database
            // $tables = DB::connection()->getDoctrineSchemaManager()->listTableNames();
            if (DB::connection()->getDriverName() === 'sqlite') {
                $tables = collect(DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"))
                    ->pluck('name');
            } else {
                $dbName = DB::getDatabaseName();
                $tables = DB::table('information_schema.tables')
                ->where('table_schema', $dbName)
                ->where('table_type', 'BASE TABLE') // ✅ only base tables
                ->pluck('table_name');
            }

            foreach ($tables as $table) {
                // Skip Laravel's migrations table to avoid breaking
                if ($table === 'migrations') {
                    continue;
                }
    
                // Add the column only if it does not already exist
                if (!Schema::hasColumn($table, 'migrated')) {
                    Schema::table($table, function (Blueprint $table) {
                        $table->boolean('migrated')->default(false);
                    });
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = DB::connection()->getDoctrineSchemaManager()->listTableNames();

        foreach ($tables as $table) {
            if ($table === 'migrations') {
                continue;
            }

            if (Schema::hasColumn($table, 'migrated')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('migrated');
                });
            }
        }
    }
};

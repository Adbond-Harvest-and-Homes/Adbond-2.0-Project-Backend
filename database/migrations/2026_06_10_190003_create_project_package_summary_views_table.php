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
        DB::statement("CREATE VIEW project_packages_summary_view AS
            SELECT 
                MIN(project_types.id) AS project_type_id,
                MIN(project_types.name) AS project_type,
                projects.id AS project_id,
                projects.name AS project_name,
                COUNT(packages.id) AS total_packages
            FROM projects
            LEFT JOIN project_types ON project_types.id = projects.project_type_id
            LEFT JOIN packages ON projects.id = packages.project_id
            GROUP BY projects.id, projects.name");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_package_summary_views');
    }
};

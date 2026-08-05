<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use app\Models\ProjectType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $defaultId = ProjectType::land()?->id ?? 1;
        Schema::table('posts', function (Blueprint $table) use ($defaultId) {
            $table->foreignId("project_type_id")->default($defaultId)->after("content");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            //
        });
    }
};

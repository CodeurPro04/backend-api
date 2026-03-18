<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_media', function (Blueprint $table) {
            $table->string('category')->nullable()->after('type');
        });

        Schema::table('construction_projects', function (Blueprint $table) {
            $table->json('render_3d_path')->nullable()->after('plans_path');
        });

        Schema::table('investment_projects', function (Blueprint $table) {
            $table->json('plans_path')->nullable()->after('images_path');
            $table->json('render_3d_path')->nullable()->after('plans_path');
        });
    }

    public function down(): void
    {
        Schema::table('investment_projects', function (Blueprint $table) {
            $table->dropColumn(['plans_path', 'render_3d_path']);
        });

        Schema::table('construction_projects', function (Blueprint $table) {
            $table->dropColumn('render_3d_path');
        });

        Schema::table('property_media', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};

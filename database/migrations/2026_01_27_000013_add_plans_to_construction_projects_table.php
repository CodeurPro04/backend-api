<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPlansToConstructionProjectsTable extends Migration
{
    public function up()
    {
        Schema::table('construction_projects', function (Blueprint $table) {
            $table->json('plans_path')->nullable()->after('documents_path');
        });
    }

    public function down()
    {
        Schema::table('construction_projects', function (Blueprint $table) {
            $table->dropColumn('plans_path');
        });
    }
}

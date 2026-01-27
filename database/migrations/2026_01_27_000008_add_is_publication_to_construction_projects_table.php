<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsPublicationToConstructionProjectsTable extends Migration
{
    public function up()
    {
        Schema::table('construction_projects', function (Blueprint $table) {
            $table->boolean('is_publication')->default(false)->after('status');
        });
    }

    public function down()
    {
        Schema::table('construction_projects', function (Blueprint $table) {
            $table->dropColumn('is_publication');
        });
    }
}


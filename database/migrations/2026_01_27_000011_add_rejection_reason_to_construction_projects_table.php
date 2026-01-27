<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRejectionReasonToConstructionProjectsTable extends Migration
{
    public function up()
    {
        Schema::table('construction_projects', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('is_publication');
        });
    }

    public function down()
    {
        Schema::table('construction_projects', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });
    }
}


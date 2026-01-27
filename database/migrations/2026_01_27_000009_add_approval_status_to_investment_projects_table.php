<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApprovalStatusToInvestmentProjectsTable extends Migration
{
    public function up()
    {
        Schema::table('investment_projects', function (Blueprint $table) {
            $table->string('approval_status')->default('approved')->after('status');
        });
    }

    public function down()
    {
        Schema::table('investment_projects', function (Blueprint $table) {
            $table->dropColumn('approval_status');
        });
    }
}


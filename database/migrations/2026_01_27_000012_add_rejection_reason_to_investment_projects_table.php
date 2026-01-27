<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRejectionReasonToInvestmentProjectsTable extends Migration
{
    public function up()
    {
        Schema::table('investment_projects', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('approval_status');
        });
    }

    public function down()
    {
        Schema::table('investment_projects', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });
    }
}


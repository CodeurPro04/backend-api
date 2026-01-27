<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReferenceFieldsToInvestmentProjectsTable extends Migration
{
    public function up()
    {
        Schema::table('investment_projects', function (Blueprint $table) {
            $table->string('reference_code')->nullable()->after('city');
            $table->string('postal_code', 20)->nullable()->after('reference_code');
            $table->decimal('surface_area', 10, 2)->nullable()->after('postal_code');
        });
    }

    public function down()
    {
        Schema::table('investment_projects', function (Blueprint $table) {
            $table->dropColumn(['reference_code', 'postal_code', 'surface_area']);
        });
    }
}

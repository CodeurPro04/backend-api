<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPlanRequestFieldsToClientRequestsTable extends Migration
{
    public function up()
    {
        Schema::table('client_requests', function (Blueprint $table) {
            $table->string('sector')->nullable()->after('message');
            $table->string('department')->nullable()->after('sector');
            $table->text('project_description')->nullable()->after('department');
            $table->boolean('consent')->default(false)->after('project_description');
        });
    }

    public function down()
    {
        Schema::table('client_requests', function (Blueprint $table) {
            $table->dropColumn(['sector', 'department', 'project_description', 'consent']);
        });
    }
}

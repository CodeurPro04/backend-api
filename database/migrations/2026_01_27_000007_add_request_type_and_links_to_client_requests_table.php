<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRequestTypeAndLinksToClientRequestsTable extends Migration
{
    public function up()
    {
        Schema::table('client_requests', function (Blueprint $table) {
            $table->string('request_type')->default('immobilier')->after('agent_id');
            $table->foreignId('construction_project_id')
                ->nullable()
                ->after('property_id')
                ->constrained('construction_projects')
                ->onDelete('set null');
            $table->foreignId('investment_project_id')
                ->nullable()
                ->after('construction_project_id')
                ->constrained('investment_projects')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('client_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('investment_project_id');
            $table->dropConstrainedForeignId('construction_project_id');
            $table->dropColumn('request_type');
        });
    }
}


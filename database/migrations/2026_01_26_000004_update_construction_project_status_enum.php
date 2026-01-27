<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateConstructionProjectStatusEnum extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE construction_projects MODIFY COLUMN status ENUM('submitted','published','in_study','quoted','approved','rejected','in_progress','completed') DEFAULT 'submitted'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE construction_projects MODIFY COLUMN status ENUM('submitted','in_study','quoted','approved','rejected','in_progress','completed') DEFAULT 'submitted'");
    }
}

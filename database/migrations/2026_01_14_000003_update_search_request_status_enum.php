<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateSearchRequestStatusEnum extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE search_requests MODIFY COLUMN status ENUM('pending','approved','rejected','assigned','in_progress','fulfilled','cancelled') DEFAULT 'pending'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE search_requests MODIFY COLUMN status ENUM('pending','assigned','in_progress','fulfilled','cancelled') DEFAULT 'pending'");
    }
}

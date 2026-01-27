<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MakeClientRequestsUserIdNullable extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE client_requests DROP FOREIGN KEY client_requests_user_id_foreign');
        DB::statement('ALTER TABLE client_requests MODIFY user_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE client_requests ADD CONSTRAINT client_requests_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL');
    }

    public function down()
    {
        DB::statement('ALTER TABLE client_requests DROP FOREIGN KEY client_requests_user_id_foreign');
        DB::statement('ALTER TABLE client_requests MODIFY user_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE client_requests ADD CONSTRAINT client_requests_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');
    }
}


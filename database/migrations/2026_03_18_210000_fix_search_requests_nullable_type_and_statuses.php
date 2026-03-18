<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('search_requests')) {
            return;
        }

        DB::statement("ALTER TABLE search_requests DROP FOREIGN KEY search_requests_property_type_id_foreign");
        DB::statement("ALTER TABLE search_requests MODIFY property_type_id BIGINT UNSIGNED NULL");
        DB::statement("ALTER TABLE search_requests ADD CONSTRAINT search_requests_property_type_id_foreign FOREIGN KEY (property_type_id) REFERENCES property_types(id) ON DELETE RESTRICT");
        DB::statement("ALTER TABLE search_requests MODIFY status ENUM('pending', 'approved', 'rejected', 'assigned', 'agent_approved', 'agent_rejected', 'in_progress', 'fulfilled', 'cancelled', 'deal_concluded') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('search_requests')) {
            return;
        }

        DB::statement("ALTER TABLE search_requests DROP FOREIGN KEY search_requests_property_type_id_foreign");
        DB::statement("ALTER TABLE search_requests MODIFY property_type_id BIGINT UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE search_requests ADD CONSTRAINT search_requests_property_type_id_foreign FOREIGN KEY (property_type_id) REFERENCES property_types(id) ON DELETE RESTRICT");
        DB::statement("ALTER TABLE search_requests MODIFY status ENUM('pending', 'assigned', 'in_progress', 'fulfilled', 'cancelled') NOT NULL DEFAULT 'pending'");
    }
};

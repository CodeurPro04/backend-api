<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'users',
        'properties',
        'construction_projects',
        'investment_projects',
        'partnerships',
        'partner_products',
        'house_models',
        'search_requests',
        'client_requests',
        'property_requests',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'country_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('country_id')->nullable()->after('id')->constrained('countries')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'country_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('country_id');
            });
        }
    }
};

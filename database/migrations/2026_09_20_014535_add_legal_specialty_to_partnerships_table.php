<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partnerships', function (Blueprint $table) {
            // Renseigne uniquement pour les partenaires juridiques (notaire, avocat,
            // huissier, conseil juridique...) : precise leur profession/specialite.
            $table->string('legal_specialty')->nullable()->after('company_type');
        });
    }

    public function down(): void
    {
        Schema::table('partnerships', function (Blueprint $table) {
            $table->dropColumn('legal_specialty');
        });
    }
};

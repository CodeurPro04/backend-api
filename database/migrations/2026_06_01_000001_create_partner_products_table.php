<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartnerProductsTable extends Migration
{
    public function up()
    {
        Schema::create('partner_products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('partnership_id')->constrained('partnerships')->onDelete('cascade');

            // Informations générales communes à tous les types
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 14, 2)->nullable();
            $table->string('currency', 10)->default('XOF');
            $table->json('images')->nullable();        // chemins des images uploadées

            // Données spécifiques selon le type de partenaire
            // Financier : financing_type, interest_rate_min/max, amount_min/max, duration_months, conditions, target_audience
            // Constructeur : construction_type, location, surface_min/max, price_per_sqm, delivery_date, materials
            // Immobilier : property_type, transaction_type, location, surface, rooms, bathrooms
            $table->json('type_data')->nullable();

            // Workflow de validation
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['partnership_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('partner_products');
    }
}

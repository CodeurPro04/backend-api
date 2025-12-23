<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropertiesTable extends Migration
{
    public function up()
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('agent_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('property_type_id')->constrained('property_types')->onDelete('restrict');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('transaction_type', ['vente', 'location']);
            $table->decimal('price', 15, 2);
            $table->string('currency')->default('XOF');
            $table->boolean('negotiable')->default(false);
            $table->decimal('surface_area', 10, 2)->nullable();
            $table->decimal('land_area', 10, 2)->nullable();
            $table->integer('bedrooms')->nullable();
            $table->integer('bathrooms')->nullable();
            $table->integer('parking_spaces')->nullable();
            $table->integer('floor_number')->nullable();
            $table->integer('total_floors')->nullable();
            $table->year('year_built')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('commune')->nullable();
            $table->string('quartier')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'archived'])->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->boolean('featured')->default(false);
            $table->integer('views_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Index recommandés
            $table->index('status');
            $table->index('property_type_id');
            $table->index('transaction_type');
            $table->index('price');
            $table->index('city');
            $table->index('featured');
        });
    }

    public function down()
    {
        Schema::dropIfExists('properties');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvestmentProjectsTable extends Migration
{
    public function up()
    {
        Schema::create('investment_projects', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('project_type', ['immobilier', 'construction', 'renovation']);
            $table->string('location')->nullable();
            $table->string('city')->nullable();
            $table->decimal('total_investment', 15, 2)->nullable();
            $table->decimal('min_investment', 15, 2)->nullable();
            $table->decimal('expected_return', 8, 2)->nullable()->comment('% ROI');
            $table->integer('duration_months')->nullable();
            $table->enum('status', ['open', 'in_progress', 'closed', 'completed'])->default('open');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('documents_path')->nullable();
            $table->json('images_path')->nullable();
            $table->decimal('current_funding', 15, 2)->default(0);
            $table->integer('investors_count')->default(0);
            $table->boolean('featured')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('investment_projects');
    }
}

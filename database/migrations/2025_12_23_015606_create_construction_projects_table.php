<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConstructionProjectsTable extends Migration
{
    public function up()
    {
        Schema::create('construction_projects', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('agent_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('project_type', ['residential', 'commercial', 'industrial']);
            $table->decimal('budget_min', 15, 2)->nullable();
            $table->decimal('budget_max', 15, 2)->nullable();
            $table->decimal('surface_area', 10, 2)->nullable();
            $table->string('location')->nullable();
            $table->string('city')->nullable();
            $table->enum('status', ['submitted', 'in_study', 'quoted', 'approved', 'rejected', 'in_progress', 'completed'])->default('submitted');
            $table->string('plan_3d_path')->nullable();
            $table->json('documents_path')->nullable();
            $table->integer('estimated_duration')->nullable()->comment('en mois');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('construction_projects');
    }
}

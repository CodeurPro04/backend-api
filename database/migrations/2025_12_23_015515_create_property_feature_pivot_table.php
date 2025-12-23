<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropertyFeaturePivotTable extends Migration
{
    public function up()
    {
        Schema::create('property_feature_pivot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
            $table->foreignId('feature_id')->constrained('property_features')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['property_id', 'feature_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('property_feature_pivot');
    }
}

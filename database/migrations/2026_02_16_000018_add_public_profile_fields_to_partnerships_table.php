<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('partnerships', function (Blueprint $table) {
            $table->string('profile_title')->nullable()->after('description');
            $table->longText('profile_description')->nullable()->after('profile_title');
            $table->string('cover_image_path')->nullable()->after('profile_description');
            $table->json('service_offers')->nullable()->after('cover_image_path');
            $table->json('product_showcase')->nullable()->after('service_offers');
        });
    }

    public function down(): void
    {
        Schema::table('partnerships', function (Blueprint $table) {
            $table->dropColumn([
                'profile_title',
                'profile_description',
                'cover_image_path',
                'service_offers',
                'product_showcase',
            ]);
        });
    }
};


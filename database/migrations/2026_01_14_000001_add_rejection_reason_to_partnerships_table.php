<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRejectionReasonToPartnershipsTable extends Migration
{
    public function up()
    {
        Schema::table('partnerships', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('approved_at');
        });
    }

    public function down()
    {
        Schema::table('partnerships', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });
    }
}

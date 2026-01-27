<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class BackfillConstructionPublications extends Migration
{
    public function up()
    {
        DB::table('construction_projects')
            ->where('status', 'published')
            ->update(['is_publication' => true]);
    }

    public function down()
    {
        // no-op
    }
}


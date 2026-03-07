<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_request_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_request_id')->constrained('client_requests')->cascadeOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('report_type');
            $table->longText('content');
            $table->text('summary')->nullable();
            $table->text('client_feedback')->nullable();
            $table->text('next_step')->nullable();
            $table->string('sale_price')->nullable();
            $table->text('closure_note')->nullable();
            $table->timestamp('concluded_at')->nullable();
            $table->timestamps();
        });

        Schema::table('client_requests', function (Blueprint $table) {
            $table->string('deal_status')->nullable()->after('status');
            $table->timestamp('deal_concluded_at')->nullable()->after('assigned_at');
            $table->string('deal_sale_price')->nullable()->after('deal_concluded_at');
            $table->text('deal_closure_note')->nullable()->after('deal_sale_price');
        });
    }

    public function down(): void
    {
        Schema::table('client_requests', function (Blueprint $table) {
            $table->dropColumn([
                'deal_status',
                'deal_concluded_at',
                'deal_sale_price',
                'deal_closure_note',
            ]);
        });

        Schema::dropIfExists('client_request_reports');
    }
};

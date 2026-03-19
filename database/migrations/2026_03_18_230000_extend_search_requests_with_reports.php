<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('search_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('search_requests', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('status');
            }
            if (!Schema::hasColumn('search_requests', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('rejection_reason');
            }
            if (!Schema::hasColumn('search_requests', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('search_requests', 'deal_status')) {
                $table->string('deal_status')->nullable()->after('fulfilled_at');
            }
            if (!Schema::hasColumn('search_requests', 'deal_concluded_at')) {
                $table->timestamp('deal_concluded_at')->nullable()->after('deal_status');
            }
            if (!Schema::hasColumn('search_requests', 'deal_sale_price')) {
                $table->string('deal_sale_price')->nullable()->after('deal_concluded_at');
            }
            if (!Schema::hasColumn('search_requests', 'deal_closure_note')) {
                $table->text('deal_closure_note')->nullable()->after('deal_sale_price');
            }
        });

        if (!Schema::hasTable('search_request_reports')) {
            Schema::create('search_request_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('search_request_id')->constrained('search_requests')->onDelete('cascade');
                $table->foreignId('agent_id')->constrained('users')->onDelete('cascade');
                $table->enum('report_type', ['progress_report', 'final_report'])->default('progress_report');
                $table->text('content');
                $table->text('summary')->nullable();
                $table->text('client_feedback')->nullable();
                $table->text('next_step')->nullable();
                $table->string('sale_price')->nullable();
                $table->text('closure_note')->nullable();
                $table->timestamp('concluded_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('search_request_reports')) {
            Schema::dropIfExists('search_request_reports');
        }

        Schema::table('search_requests', function (Blueprint $table) {
            foreach (['deal_closure_note', 'deal_sale_price', 'deal_concluded_at', 'deal_status', 'rejected_at', 'approved_at', 'rejection_reason'] as $column) {
                if (Schema::hasColumn('search_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('evenleads_leads')) {
            Schema::table('evenleads_leads', function (Blueprint $table) {
                if (!Schema::hasColumn('evenleads_leads', 'is_relevant')) {
                    $table->boolean('is_relevant')->default(true)->after('status');
                }
                if (!Schema::hasColumn('evenleads_leads', 'marked_non_relevant_at')) {
                    $table->timestamp('marked_non_relevant_at')->nullable()->after('is_relevant');
                }
                if (!Schema::hasColumn('evenleads_leads', 'marked_non_relevant_by')) {
                    $table->foreignId('marked_non_relevant_by')->nullable()->constrained('users')->onDelete('set null')->after('marked_non_relevant_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('evenleads_leads')) {
            Schema::table('evenleads_leads', function (Blueprint $table) {
                $table->dropForeign(['marked_non_relevant_by']);
                $table->dropColumn(['is_relevant', 'marked_non_relevant_at', 'marked_non_relevant_by']);
            });
        }
    }
};

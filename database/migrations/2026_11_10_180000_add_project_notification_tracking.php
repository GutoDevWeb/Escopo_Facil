<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // No project foreign key: removal/deletion notices must survive the project.
            $table->unsignedBigInteger('project_id')->nullable()->index();
            $table->timestamp('project_seen_at')->nullable();
            $table->string('dedup_key')->nullable()->unique();
            $table->boolean('mail_pending')->default(false);
            $table->timestamp('mail_retry_at')->nullable();
            $table->timestamp('mail_sent_at')->nullable();
            $table->index(['mail_pending', 'mail_retry_at']);
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['mail_pending', 'mail_retry_at']);
            $table->dropIndex(['project_id']);
            $table->dropUnique(['dedup_key']);
            $table->dropColumn(['project_id', 'project_seen_at', 'dedup_key', 'mail_pending', 'mail_retry_at', 'mail_sent_at']);
        });
    }
};

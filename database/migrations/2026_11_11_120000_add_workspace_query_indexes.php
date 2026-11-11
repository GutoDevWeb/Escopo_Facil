<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_members', function (Blueprint $table) {
            $table->index(['user_id', 'project_id'], 'project_members_user_project_index');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['project_id', 'status'], 'tasks_project_status_index');
            $table->index(['project_id', 'due_date'], 'tasks_project_due_date_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_type', 'notifiable_id', 'project_seen_at', 'project_id'], 'notifications_project_updates_index');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_project_updates_index');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_project_status_index');
            $table->dropIndex('tasks_project_due_date_index');
        });

        Schema::table('project_members', function (Blueprint $table) {
            $table->dropIndex('project_members_user_project_index');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('usage_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('day');
            $table->unsignedInteger('requests')->default(0);
            $table->unsignedInteger('logins')->default(0);
            $table->timestamp('last_seen_at')->nullable();
            $table->unique(['user_id', 'day']);
        });
    }
    public function down(): void { Schema::dropIfExists('usage_metrics'); }
};

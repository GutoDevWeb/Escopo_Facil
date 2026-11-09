<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Instalações SQLite antigas já receberam essa chave na migration users.
        // Bancos novos precisam aguardar a criação de plans para adicioná-la.
        if (! $this->hasPlanForeignKey()) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('plan_id')->references('id')->on('plans')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if ($this->hasPlanForeignKey()) {
            Schema::table('users', fn (Blueprint $table) => $table->dropForeign(['plan_id']));
        }
    }

    private function hasPlanForeignKey(): bool
    {
        foreach (Schema::getForeignKeys('users') as $foreignKey) {
            if ($foreignKey['columns'] === ['plan_id'] && $foreignKey['foreign_table'] === 'plans') {
                return true;
            }
        }

        return false;
    }
};

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Plan::firstOrCreate(['slug' => 'free'], [
            'name' => 'Plano Free',
            'slug' => 'free',
            'description' => 'Ideal para estudantes e testes individuais.',
            'price_monthly' => 0.00,
            'price_yearly' => 0.00,
            'limits' => ['projects' => -1, 'members' => -1],
        ]);

        Plan::firstOrCreate(['slug' => 'pro'], [
            'name' => 'Escopo Pro',
            'slug' => 'pro',
            'description' => 'Focado em prioridades, prazos e gestão avançada.',
            'price_monthly' => 27.90,
            'price_yearly' => 238.80,
            'limits' => ['projects' => -1, 'members' => -1],
        ]);

        Plan::firstOrCreate(['slug' => 'equipe'], [
            'name' => 'Escopo Equipe',
            'slug' => 'equipe',
            'description' => 'Permissões avançadas, suporte e histórico completo para times.',
            'price_monthly' => 49.90,
            'price_yearly' => 499.00,
            'limits' => ['projects' => -1, 'members' => -1],
        ]);
    }
}

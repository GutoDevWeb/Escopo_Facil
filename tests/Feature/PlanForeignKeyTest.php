<?php

namespace Tests\Feature;

use App\Models\{Plan, User};
use Database\Seeders\PlanSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanForeignKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_plan_preserves_the_user_and_clears_its_reference(): void
    {
        $this->seed(PlanSeeder::class);
        $plan = Plan::where('slug', 'pro')->firstOrFail();
        $user = User::factory()->create(['plan_id' => $plan->id]);
        $plan->delete();

        $this->assertNull($user->fresh()->plan_id);
    }

    public function test_the_database_rejects_a_reference_to_a_missing_plan(): void
    {
        $this->expectException(QueryException::class);
        User::factory()->create(['plan_id' => 999999]);
    }
}

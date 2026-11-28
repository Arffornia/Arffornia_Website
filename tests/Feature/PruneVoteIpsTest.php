<?php

namespace Tests\Feature;

use App\Models\Stage;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneVoteIpsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Stage::factory()->create();
    }

    private function voteCreatedDaysAgo(int $days): Vote
    {
        $vote = Vote::create([
            'user_id' => User::factory()->create()->id,
            'site' => 'smc',
            'ip_address' => '203.0.113.7',
        ]);
        $vote->forceFill(['created_at' => now()->subDays($days)])->save();

        return $vote;
    }

    public function test_it_clears_ip_addresses_older_than_the_retention_period(): void
    {
        $this->travelTo(now());
        $old = $this->voteCreatedDaysAgo(31);

        $this->artisan('votes:prune-ips')->assertSuccessful();

        $this->assertNull($old->fresh()->ip_address);
    }

    public function test_it_keeps_the_vote_row_when_clearing_its_ip_address(): void
    {
        $this->travelTo(now());
        $old = $this->voteCreatedDaysAgo(31);

        $this->artisan('votes:prune-ips');

        $this->assertDatabaseHas('votes', ['id' => $old->id, 'site' => 'smc']);
    }

    public function test_it_keeps_ip_addresses_within_the_retention_period(): void
    {
        $this->travelTo(now());
        $recent = $this->voteCreatedDaysAgo(29);

        $this->artisan('votes:prune-ips');

        $this->assertSame('203.0.113.7', $recent->fresh()->ip_address);
    }

    public function test_it_is_a_no_op_when_run_again(): void
    {
        $this->travelTo(now());
        $old = $this->voteCreatedDaysAgo(31);
        $recent = $this->voteCreatedDaysAgo(29);

        $this->artisan('votes:prune-ips');
        $this->artisan('votes:prune-ips')->expectsOutputToContain('Cleared 0');

        $this->assertNull($old->fresh()->ip_address);
        $this->assertSame('203.0.113.7', $recent->fresh()->ip_address);
    }

    public function test_it_honours_a_configured_retention_period(): void
    {
        $this->travelTo(now());
        config(['privacy.vote_ip_retention_days' => 7]);
        $vote = $this->voteCreatedDaysAgo(8);

        $this->artisan('votes:prune-ips');

        $this->assertNull($vote->fresh()->ip_address);
    }

    public function test_it_is_scheduled_daily(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($e) => str_contains($e->command, 'votes:prune-ips'));

        $this->assertSame('0 0 * * *', $event?->expression);
    }
}

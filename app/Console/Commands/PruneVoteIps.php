<?php

namespace App\Console\Commands;

use App\Models\Vote;
use Illuminate\Console\Command;

class PruneVoteIps extends Command
{
    protected $signature = 'votes:prune-ips';

    protected $description = 'Clears vote IP addresses older than the configured retention period.';

    public function handle(): int
    {
        $days = config('privacy.vote_ip_retention_days');

        $cleared = Vote::whereNotNull('ip_address')
            ->where('created_at', '<', now()->subDays($days))
            ->update(['ip_address' => null]);

        $this->info("Cleared {$cleared} vote IP address(es) older than {$days} days.");

        return self::SUCCESS;
    }
}

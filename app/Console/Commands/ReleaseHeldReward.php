<?php

namespace App\Console\Commands;

use App\Models\PendingReward;
use App\Rewards\RewardCatalog;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class ReleaseHeldReward extends Command
{
    protected $signature = 'rewards:release {id : pending_rewards id, status on_hold} {contents : typed contents as a JSON list}';

    protected $description = 'Gives an on-hold reward typed contents and makes it claimable again.';

    public function handle(): int
    {
        $contents = json_decode((string) $this->argument('contents'), true);
        try {
            $contents = RewardCatalog::validate($contents);
        } catch (ValidationException $e) {
            $this->error('Invalid contents: ' . json_encode($e->errors()));
            return self::FAILURE;
        }

        $updated = PendingReward::whereKey((int) $this->argument('id'))
            ->where('status', PendingReward::STATUS_ON_HOLD)
            ->update(['contents' => json_encode($contents), 'status' => PendingReward::STATUS_PENDING, 'hold_reason' => null]);

        if ($updated === 0) {
            $this->error('No on-hold reward with this id.');
            return self::FAILURE;
        }

        $this->info('Reward released.');
        return self::SUCCESS;
    }
}

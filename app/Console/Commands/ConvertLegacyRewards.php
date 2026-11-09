<?php

namespace App\Console\Commands;

use App\Models\PendingReward;
use App\Models\ShopItem;
use App\Rewards\LegacyCommandConverter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ConvertLegacyRewards extends Command
{
    protected $signature = 'rewards:convert-legacy {--dry-run : Report what would change without writing}';

    protected $description = 'Converts command-string rewards (shop items and pending rewards) into typed reward contents.';

    public function handle(LegacyCommandConverter $converter): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $report = [];

        ShopItem::whereNull('contents')->whereNotNull('commands')->lazyById()->each(function (ShopItem $item) use ($converter, $dryRun, &$report) {
            $result = $converter->convert($this->commandList($item->getRawOriginal('commands')), null);
            $convertible = $result['unsupported'] === [] && $result['entries'] !== [];

            if ($convertible && !$dryRun) {
                $item->forceFill(['contents' => $result['entries']])->save();
            }
            if (!$convertible) {
                Log::warning('Shop item left without typed contents: it cannot be bought until fixed.', ['shop_item_id' => $item->id]);
            }
            $report[] = ['shop_item', $item->id, $convertible ? 'converted' : 'not purchasable', $this->summary($result)];
        });

        PendingReward::with('user:id,name')
            ->where('status', PendingReward::STATUS_PENDING)
            ->whereNull('contents')
            ->lazyById()
            ->each(function (PendingReward $reward) use ($converter, $dryRun, &$report) {
                $result = $converter->convert($this->commandList($reward->getRawOriginal('commands')), $reward->user?->name);

                if ($result['unsupported'] === [] && $result['entries'] !== []) {
                    $changes = ['contents' => $result['entries']];
                    $outcome = 'converted';
                } else {
                    $reason = $result['unsupported'] !== []
                        ? 'Legacy command not convertible: ' . implode(' | ', $result['unsupported'])
                        : 'Legacy reward has nothing deliverable.';
                    $changes = ['status' => PendingReward::STATUS_ON_HOLD, 'hold_reason' => Str::limit($reason, 250)];
                    $outcome = 'on hold';
                }

                if (!$dryRun) {
                    $updated = PendingReward::whereKey($reward->id)
                        ->where('status', PendingReward::STATUS_PENDING)
                        ->whereNull('contents')
                        ->update(array_map(fn ($v) => is_array($v) ? json_encode($v) : $v, $changes));
                    if ($updated === 0) {
                        $outcome = 'skipped (changed meanwhile)';
                    } elseif ($outcome === 'on hold') {
                        Log::warning('Legacy pending reward put on hold.', ['pending_reward_id' => $reward->id, 'reason' => $changes['hold_reason']]);
                    }
                }
                $report[] = ['pending_reward', $reward->id, $outcome, $this->summary($result)];
            });

        if ($report === []) {
            $this->info('No legacy rewards to convert.');
            return self::SUCCESS;
        }

        $this->table(['Kind', 'Id', 'Outcome', 'Details'], $report);
        if ($dryRun) {
            $this->warn('Dry run: nothing was written.');
        }

        return self::SUCCESS;
    }

    /** @return list<mixed> */
    private function commandList(?string $raw): array
    {
        $decoded = $raw === null ? [] : json_decode($raw, true);

        return is_array($decoded) ? array_values($decoded) : [$raw];
    }

    /** @param array{entries: list<array>, dropped: list<string>, unsupported: list<string>} $result */
    private function summary(array $result): string
    {
        $parts = [count($result['entries']) . ' entries'];
        if ($result['dropped'] !== []) {
            $parts[] = 'dropped: ' . implode(' | ', $result['dropped']);
        }
        if ($result['unsupported'] !== []) {
            $parts[] = 'unsupported: ' . implode(' | ', $result['unsupported']);
        }

        return Str::limit(implode('; ', $parts), 200);
    }
}

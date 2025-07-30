<?php

namespace App\Console\Commands;

use App\Models\ServiceAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class SyncServiceAccounts extends Command
{
    protected $signature = 'app:sync-service-accounts';
    protected $description = 'Creates or updates service accounts from environment variables.';

    public function handle()
    {
        $this->info('Starting service account synchronization...');

        $svcAccountsJson = config('app.svc_accounts');
        if (empty($svcAccountsJson)) {
            $this->warn('No SVC_ACCOUNTS found in configuration (config/app.php). Skipping.');
            return 0;
        }

        try {
            $accounts = json_decode($svcAccountsJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->error('Failed to parse SVC_ACCOUNTS JSON: ' . $e->getMessage());
            Log::error('SVC_ACCOUNTS JSON parsing error: ' . $e->getMessage());
            return 1;
        }

        if (!is_array($accounts)) {
            $this->error('SVC_ACCOUNTS is not a valid array of accounts.');
            return 1;
        }

        foreach ($accounts as $accountData) {
            if (empty($accountData['client_id']) || empty($accountData['client_secret']) || empty($accountData['roles'])) {
                $this->warn('Skipping invalid service account entry: ' . json_encode($accountData));
                continue;
            }

            $serviceAccount = ServiceAccount::updateOrCreate(
                ['client_id' => $accountData['client_id']],
                [
                    'name' => $accountData['name'] ?? 'Untitled Service Account',
                    'client_secret' => Hash::make($accountData['client_secret']),
                    'roles' => $accountData['roles']
                ]
            );

            $this->line("Service account '{$serviceAccount->name}' processed successfully.");
        }

        $this->info('Service account synchronization finished.');
        return 0;
    }
}

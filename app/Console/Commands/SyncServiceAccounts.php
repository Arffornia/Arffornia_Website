<?php

namespace App\Console\Commands;

use App\Models\ServiceAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SyncServiceAccounts extends Command
{
    protected $signature = 'app:sync-service-accounts';
    protected $description = 'Creates, updates, or deletes service accounts from environment variables.';

    public function handle()
    {
        $this->info('Starting service account synchronization...');

        $expectedAccounts = $this->parseAccountsFromEnv();
        if (empty($expectedAccounts)) {
            $this->warn('No service account environment variables found (e.g., SVC_ACCOUNT_MINECRAFT_CLIENT_ID).');
            return 0;
        }

        $expectedClientIds = array_keys($expectedAccounts);

        foreach ($expectedAccounts as $clientId => $accountData) {
            if (empty($clientId) || empty($accountData['client_secret']) || empty($accountData['roles']) || empty($accountData['name'])) {
                $this->warn('Skipping invalid service account entry for client_id: ' . $clientId);
                continue;
            }

            $serviceAccount = ServiceAccount::updateOrCreate(
                ['client_id' => $clientId],
                [
                    'name' => $accountData['name'],
                    'client_secret' => Hash::make($accountData['client_secret']),
                    'roles' => $accountData['roles']
                ]
            );

            $this->line("Service account '{$serviceAccount->name}' (ID: {$clientId}) processed.");
        }

        $staleAccounts = ServiceAccount::whereNotIn('client_id', $expectedClientIds)->get();


        if ($staleAccounts->isNotEmpty()) {
            $this->info('Deleting stale service accounts...');

            foreach ($staleAccounts as $staleAccount) {
                $this->warn("Deleting account '{$staleAccount->name}' (ID: {$staleAccount->client_id}).");
                $staleAccount->delete();
            }
        }

        $this->info('Service account synchronization finished.');
        return 0;
    }

    /**
     * Scans environment variables and builds an array of service accounts.
     * Expects variables like:
     * SVC_ACCOUNT_MINECRAFT_CLIENT_ID, SVC_ACCOUNT_MINECRAFT_CLIENT_SECRET, etc.
     *
     * @return array
     */
    private function parseAccountsFromEnv(): array
    {
        $accounts = [];
        $prefix = 'SVC_ACCOUNT_';

        foreach ($_ENV as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                $parts = explode('_', substr($key, strlen($prefix)), 2);
                if (count($parts) === 2) {
                    // $accountKey = $parts[0]; // e.g.,: MINECRAFT
                    $propertyKey = strtolower($parts[1]); // e.g.,: client_id

                    if ($propertyKey === 'client_id') {
                        $accounts[$value] = $accounts[$value] ?? [];
                    }
                }
            }
        }

        $accounts = [];
        $keys = [];

        foreach (array_keys($_ENV) as $key) {
            if (preg_match('/^SVC_ACCOUNT_([A-Z0-9]+)_.+/', $key, $matches)) {
                $keys[$matches[1]] = true;
            }
        }

        foreach (array_keys($keys) as $key) {
            $clientId = env("SVC_ACCOUNT_{$key}_CLIENT_ID");
            $clientSecret = env("SVC_ACCOUNT_{$key}_CLIENT_SECRET");
            $name = env("SVC_ACCOUNT_{$key}_NAME");
            $roles = env("SVC_ACCOUNT_{$key}_ROLES");

            if ($clientId && $clientSecret) {
                $accounts[$clientId] = [
                    'client_secret' => $clientSecret,
                    'name' => $name,
                    'roles' => $roles,
                ];
            }
        }

        return $accounts;
    }
}

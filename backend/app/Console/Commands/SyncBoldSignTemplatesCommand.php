<?php

namespace App\Console\Commands;

use App\Services\BoldSignTemplateSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncBoldSignTemplatesCommand extends Command
{
    protected $signature = 'boldsign:sync-templates {--skip-details : Skip template details lookup and sync only the list endpoint}';

    protected $description = 'Sync BoldSign templates into the local boldsign_templates cache table';

    public function handle(BoldSignTemplateSyncService $syncService): int
    {
        try {
            $this->info('Starting BoldSign template sync...');

            $result = $syncService->syncAll(
                syncDetails: !$this->option('skip-details')
            );

            $this->line('Remote templates: ' . (int) ($result['total_remote'] ?? 0));
            $this->line('Created: ' . (int) ($result['created'] ?? 0));
            $this->line('Updated: ' . (int) ($result['updated'] ?? 0));
            $this->line('Reactivated: ' . (int) ($result['reactivated'] ?? 0));
            $this->line('Inactivated: ' . (int) ($result['inactivated'] ?? 0));

            $warnings = (array) ($result['warnings'] ?? []);
            if (!empty($warnings)) {
                $this->newLine();
                $this->warn('Warnings:');
                foreach ($warnings as $warning) {
                    $this->warn('- ' . $warning);
                }
            }

            $this->newLine();
            $this->info('BoldSign template sync completed.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('BoldSign template sync failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}

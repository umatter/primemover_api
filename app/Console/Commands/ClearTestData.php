<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:clear-test {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all test data (experiments, crawlers, agents, proxies, jobs, etc.) while preserving users and system data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Show warning
        $this->warn('⚠️  This will DELETE ALL experiment-related data from the database!');
        $this->warn('   The following will be cleared:');
        $this->line('   - Experiments');
        $this->line('   - Crawlers');
        $this->line('   - Agents');
        $this->line('   - Proxies');
        $this->line('   - Configurations, Params, Preferences');
        $this->line('   - Queues, Jobs, Behaviors');
        $this->line('   - Terms, Outlets');
        $this->line('   - Reports');
        $this->line('   - Posts');
        $this->newLine();
        $this->info('✓  The following will be PRESERVED:');
        $this->line('   - Users and authentication data');
        $this->line('   - Failed jobs log');
        $this->line('   - Telescope debugging data');
        $this->newLine();

        // Confirm unless --force flag is used
        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to proceed?', false)) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $this->info('Starting data cleanup...');
        $this->newLine();

        // Disable foreign key checks to avoid constraint violations
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            // Delete in order from child to parent tables
            $tables = [
                'behaviors' => 'Behaviors',
                'jobs' => 'Jobs',
                'reports' => 'Reports',
                'queues' => 'Queues',
                'preferences' => 'Preferences',
                'params' => 'Params',
                'terms' => 'Search Terms',
                'outlets' => 'Media Outlets',
                'configurations' => 'Configurations',
                'agents' => 'Agents',
                'proxies' => 'Proxies',
                'crawlers' => 'Crawlers',
                'experiments' => 'Experiments',
                'posts' => 'Posts',
            ];

            foreach ($tables as $table => $label) {
                $count = DB::table($table)->count();

                if ($count > 0) {
                    DB::table($table)->delete();
                    $this->line("✓ Deleted {$count} records from <info>{$label}</info>");
                } else {
                    $this->line("- No records in <comment>{$label}</comment>");
                }
            }

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->newLine();
            $this->info('✓ Database cleanup completed successfully!');
            $this->newLine();

            return Command::SUCCESS;

        } catch (\Exception $e) {
            // Re-enable foreign key checks even if error occurs
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->error('✗ An error occurred during cleanup:');
            $this->error($e->getMessage());
            $this->newLine();

            return Command::FAILURE;
        }
    }
}

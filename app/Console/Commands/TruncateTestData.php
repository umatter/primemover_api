<?php

namespace App\Console\Commands;

use Database\Seeders\TruncateTablesSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class TruncateTestData extends Command
{
    /**
     * @var string
     */
    protected $signature = 'app:truncate-data';

    /**
     * @var string
     */
    protected $description = 'Truncates all tables in the test database when not in production mode.';

    public function handle(): void
    {
        $this->output->title('Truncating test data...');

        try {
            Artisan::call('db:seed', [
                '--class' => TruncateTablesSeeder::class,
                '--force' => true,
            ]);

            $this->output->success('Test data truncated.');
        } catch (Throwable $e) {
            $this->output->error('Failed to truncate test data.');
            $this->output->error($e->getMessage());
        }
    }
}

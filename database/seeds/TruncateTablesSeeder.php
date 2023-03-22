<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TruncateTablesSeeder extends Seeder
{
    public const TABLES = [
        'agents',
        'configurations',
        'proxies',
        'jobs',
        'queues',
        'crawlers',
        'reports',
        'behaviors',
        'preferences',
        'params',
        'telescope_entries_tags',
        'telescope_entries',
    ];

    public function run(): void
    {
        if (App::isProduction()) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        Collection::make(self::TABLES)
            ->each(fn ($table) => DB::table($table)->truncate());

        Schema::enableForeignKeyConstraints();
    }
}

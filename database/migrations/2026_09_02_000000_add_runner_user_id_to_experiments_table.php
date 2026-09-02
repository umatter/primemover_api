<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('experiments', function (Blueprint $table): void {
            // Which runner account executes this experiment's queues.
            // NULL means the default runner (config primemover.runner.default_user_id).
            // Deliberately separate from user_id: ownership is visibility,
            // this is execution.
            $table->foreignId('runner_user_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('experiments', function (Blueprint $table): void {
            if ('sqlite' !== DB::getDriverName()) {
                $table->dropConstrainedForeignId('runner_user_id');

                return;
            }

            $table->dropColumn('runner_user_id');
        });
    }
};

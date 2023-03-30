<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangeUserFkOndelete extends Migration
{
    public function up(): void
    {
        Schema::table('proxies', function (Blueprint $table): void {
            if ('sqlite' !== DB::getDriverName()) {
                $table->dropForeign(['user_id']);
            }

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('crawlers', function (Blueprint $table): void {
            if ('sqlite' !== DB::getDriverName()) {
                $table->dropForeign(['user_id']);
            }

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('configurations', function (Blueprint $table): void {
            if ('sqlite' !== DB::getDriverName()) {
                $table->dropForeign(['user_id']);
            }
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('experiments', function (Blueprint $table): void {
            if ('sqlite' !== DB::getDriverName()) {
                $table->dropForeign(['user_id']);
            }

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('agents', function (Blueprint $table): void {
            if ('sqlite' !== DB::getDriverName()) {
                $table->dropForeign(['user_id']);
            }

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('queues', function (Blueprint $table): void {
            if ('sqlite' !== DB::getDriverName()) {
                $table->dropForeign(['user_id']);
            }

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('jobs', function (Blueprint $table): void {
            if ('sqlite' !== DB::getDriverName()) {
                $table->dropForeign(['user_id']);
            }

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('behaviors', function (Blueprint $table): void {
            if ('sqlite' !== DB::getDriverName()) {
                $table->dropForeign(['user_id']);
            }

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('reports', function (Blueprint $table): void {
            if ('sqlite' !== DB::getDriverName()) {
                $table->dropForeign(['user_id']);
            }

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('params', function (Blueprint $table): void {
            if ('sqlite' !== DB::getDriverName()) {
                $table->dropForeign(['user_id']);
            }
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('preferences', function (Blueprint $table): void {
            if ('sqlite' !== DB::getDriverName()) {
                $table->dropForeign(['user_id']);
            }
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('outlets', function (Blueprint $table): void {
            if ('sqlite' !== DB::getDriverName()) {
                $table->dropForeign(['user_id']);
            }
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('terms', function (Blueprint $table): void {
            if ('sqlite' !== DB::getDriverName()) {
                $table->dropForeign(['user_id']);
            }
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
    }
}

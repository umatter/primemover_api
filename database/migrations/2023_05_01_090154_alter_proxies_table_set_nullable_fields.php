<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('proxies', function (Blueprint $table): void {
            $table->string('username')->nullable()->change();
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('proxies', function (Blueprint $table): void {
            $table->string('username')->change();
            $table->string('password')->change();
        });
    }
};

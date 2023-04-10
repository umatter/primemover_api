<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('preferences', function (Blueprint $table): void {
            $table->string('description')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('preferences', function (Blueprint $table): void {
            $table->string('description')->change();
        });
    }
};

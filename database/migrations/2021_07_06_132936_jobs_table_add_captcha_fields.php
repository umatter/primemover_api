<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class JobsTableAddCaptchaFields extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table): void {
            $table->boolean('captcha_found')->nullable();
            $table->boolean('captcha_solved')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table): void {
            $table->dropColumn('captcha_found');
            $table->dropColumn('captcha_solved');
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('params', function (Blueprint $table): void {
            $table->id();

            $table->string('pi')->nullable();
            $table->string('psi')->nullable();
            $table->string('kappa')->nullable();
            $table->string('tau')->nullable();
            $table->string('beta')->nullable();
            $table->string('alpha')->nullable();
            $table->longText('search_terms')->nullable();
            $table->longText('media_outlet_urls')->nullable();

            $table->timestamp('updated_at')->useCurrent()->default(\DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
            $table->timestamp('created_at')->useCurrent();

            // Foreign keys
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->foreignId('configuration_id')->constrained()->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('params');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrawlersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('crawlers', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->default('Crawly');
            $table->string('description')->nullable();
            $table->boolean('active')->default(1);
            $table->boolean('new')->default(1);
            $table->boolean('testing')->default(0);
            $table->boolean('updated')->default(0);

            $table->timestamp('updated_at')->useCurrent()->default(\DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
            $table->timestamp('created_at')->useCurrent();

            // Foreign keys
            $table->foreignId('experiment_id')->constrained()->onDelete('restrict');
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->foreignId('proxy_id')->constrained()->onDelete('restrict');
            $table->foreignId('agent_id')->constrained()->onDelete('restrict');
            $table->foreignId('configuration_id')->constrained()->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('crawlers');
    }
}

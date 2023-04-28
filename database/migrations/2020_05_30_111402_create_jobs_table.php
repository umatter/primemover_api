<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->default('Query 111');
            $table->string('description')->nullable();
            $table->string('type')->default('visit_webpage_browserbar');
            $table->string('status_code')->nullable();
            $table->text('status_message')->nullable();
            $table->integer('order')->default(1);
            $table->boolean('active')->default(1);

            $table->timestamp('updated_at')->useCurrent()->default(\DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
            $table->timestamp('created_at')->useCurrent();

            $table->string('started_at')->nullable();
            $table->string('finished_at')->nullable();

            // Foreign keys
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->foreignId('queue_id')->constrained()->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};

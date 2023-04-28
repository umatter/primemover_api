<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table): void {
            $table->id();

            $table->text('description');
            $table->boolean('active')->default(1);
            $table->text('path')->nullable();
            $table->text('filename')->nullable();
            $table->longText('filecontent')->nullable();
            $table->string('mimetype')->nullable();

            $table->timestamp('updated_at')->useCurrent()->default(\DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
            $table->timestamp('created_at')->useCurrent();

            // Foreign keys
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->foreignId('job_id')->constrained()->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('preferences', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('description');
            $table->morphs('preferencable');
            $table->string('value');
            $table->boolean('active')->default(1);
            $table->timestamp('updated_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();

            // Foreign keys
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preferences');
    }
};

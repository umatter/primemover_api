<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->string('identification')->nullable();
            $table->string('multilogin_id')->nullable();
            $table->longText('multilogin_profile')->nullable();
            $table->string('location')->nullable();
            $table->boolean('active')->default(1);

            $table->timestamp('updated_at')->useCurrent()->default(\DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
            $table->timestamp('created_at')->useCurrent();

            // Foreign keys
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
}

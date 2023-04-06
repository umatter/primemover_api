<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOutletsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('outlets', function (Blueprint $table): void {
            $table->id();
            $table->text('title')->nullable();
            $table->string('outlet')->nullable();
            $table->string('yhat_n')->nullable();
            $table->string('congress')->nullable();
            $table->string('domain')->nullable();
            $table->string('url')->nullable();
            $table->string('redirect_url')->nullable();
            $table->string('media_type')->nullable();
            $table->string('pub_state')->nullable();
            $table->string('avg_reach_rank')->nullable();
            $table->string('avg_reach_permillion')->nullable();
            $table->longText('awis_categories')->nullable();
            $table->string('source')->nullable();
            $table->string('pi')->nullable();

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
        Schema::dropIfExists('outlets');
    }
}

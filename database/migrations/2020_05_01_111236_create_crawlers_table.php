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
    public function up()
    {
        Schema::create('crawlers', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('proxy_id');
            $table->unsignedBigInteger('agent_id');
            $table->unsignedBigInteger('configuration_id');

            $table->string('name')->default('Crawly');
            $table->string('description')->nullable();
            $table->boolean('active')->default(1);

            $table->timestamp('updated_at')->useCurrent()->default(\DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
            $table->timestamp('created_at')->useCurrent();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('proxy_id')->references('id')->on('proxies')->onDelete('restrict');
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('restrict');
            $table->foreign('configuration_id')->references('id')->on('configurations')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crawlers');
    }
}

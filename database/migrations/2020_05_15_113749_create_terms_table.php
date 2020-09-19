<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTermsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->string('bigram')->nullable();
            $table->string('bigram_stem')->nullable();
            $table->string('search_term')->nullable();
            $table->string('refterm')->nullable();
            $table->string('n_days_hits')->nullable();
            $table->string('s_states_hits')->nullable();
            $table->string('mean_hits_time')->nullable();
            $table->string('mean_hits_states')->nullable();
            $table->string('sd_hits_time')->nullable();
            $table->string('sd_hits_states')->nullable();
            $table->string('subject')->nullable();
            $table->string('f_pld')->nullable();
            $table->string('f_plr')->nullable();
            $table->string('f_npld')->nullable();
            $table->string('f_nplr')->nullable();
            $table->string('chisq_pl')->nullable();
            $table->string('a_p')->nullable();
            $table->string('a_p_se')->nullable();
            $table->string('a_p_tval')->nullable();
            $table->string('a_p_pval')->nullable();
            $table->string('b_p')->nullable();
            $table->string('b_p_se')->nullable();
            $table->string('b_p_tval')->nullable();
            $table->string('b_p_pval')->nullable();
            $table->string('pi_p')->nullable();

            $table->timestamp('updated_at')->useCurrent()->default(\DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
            $table->timestamp('created_at')->useCurrent();

            // Foreign keys
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('terms');
    }
}

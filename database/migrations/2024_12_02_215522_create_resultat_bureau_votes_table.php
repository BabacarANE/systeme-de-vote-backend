<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('resultats_bureau_vote', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bureau_de_vote_id')->constrained('bureaux_de_vote')->onDelete('cascade');
            $table->integer('nombre_votants');
            $table->integer('bulletins_nuls');
            $table->integer('bulletins_blancs');
            $table->integer('suffrages_exprimes');
            $table->string('pv')->nullable();
            $table->boolean('validite')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resultats_bureau_vote');
    }
};
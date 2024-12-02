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
        Schema::create('bureaux_de_vote', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centre_de_vote_id')->constrained('centres_de_vote')->onDelete('cascade');
            $table->string('nom');
            $table->string('statut');
            $table->integer('nombre_inscrits');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bureaux_de_vote');
    }
};

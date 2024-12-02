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
        Schema::create('vote_candidats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resultat_bureau_vote_id')->constrained('resultats_bureau_vote')->onDelete('cascade');
            $table->foreignId('candidature_id')->constrained('candidatures')->onDelete('cascade');
            $table->integer('nombre_voix');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vote_candidats');
    }
};

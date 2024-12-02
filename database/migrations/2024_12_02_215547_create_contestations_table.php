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
        Schema::create('contestations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resultat_bureau_vote_id')->constrained('resultats_bureau_vote')->onDelete('cascade');
            $table->foreignId('role_representant_id')->constrained('role_representants')->onDelete('cascade');
            $table->foreignId('role_candidat_id')->constrained('role_candidats')->onDelete('cascade');
            $table->text('motif');
            $table->string('statut');
            $table->text('description')->nullable();
            $table->json('pieces_jointes')->nullable();
            $table->timestamp('date_soumission');
            $table->timestamp('date_traitement')->nullable();
            $table->text('decision')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contestations');
    }
};

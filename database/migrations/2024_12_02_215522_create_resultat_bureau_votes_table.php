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
        Schema::create('resultat_bureau_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bureau_de_vote_id')->constrained('bureau_de_votes')->onDelete('cascade');
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
        Schema::dropIfExists('resultat_bureau_votes');
    }
};

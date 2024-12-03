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
        Schema::create('liste_electorales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bureau_de_vote_id')->constrained('bureau_de_votes')->onDelete('cascade');
            $table->string('code')->unique();
            $table->date('date_creation');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listes_electorales');
    }
};

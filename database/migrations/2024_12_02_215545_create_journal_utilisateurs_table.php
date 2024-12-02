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
        Schema::create('journal_utilisateurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_utilisateur_id')->constrained('role_utilisateurs')->onDelete('cascade');
            $table->string('action');
            $table->timestamp('horodatage');
            $table->json('donnees_additionnelles')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_utilisateurs');
    }
};

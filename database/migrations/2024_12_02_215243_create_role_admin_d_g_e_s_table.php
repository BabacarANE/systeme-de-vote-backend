<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('role_admin_d_g_e_s', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_utilisateur_id')->constrained('role_utilisateurs')->onDelete('cascade');
            $table->string('code')->unique();
            $table->string('niveau_acces');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_admin_dges');
    }
};

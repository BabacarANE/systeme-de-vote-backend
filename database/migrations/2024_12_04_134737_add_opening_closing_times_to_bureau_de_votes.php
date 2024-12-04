<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bureau_de_votes', function (Blueprint $table) {
            $table->timestamp('heure_ouverture')->nullable();
            $table->timestamp('heure_fermeture')->nullable();
        });
    }

    public function down()
    {
        Schema::table('bureau_de_votes', function (Blueprint $table) {
            $table->dropColumn(['heure_ouverture', 'heure_fermeture']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('role_electeurs', function (Blueprint $table) {
            $table->foreignId('liste_electorale_id')
                ->nullable()
                ->constrained('liste_electorales')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('role_electeurs', function (Blueprint $table) {
            $table->dropForeign(['liste_electorale_id']);
            $table->dropColumn('liste_electorale_id');
        });
    }
};

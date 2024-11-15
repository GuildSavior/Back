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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // Nom de l'événement
            $table->text('description')->nullable();      // Description de l'événement, optionnelle
            $table->foreignId('guild_id')->constrained('guilds')->onDelete('cascade');  // Référence à la guilde
            $table->dateTime('start_time');                // Heure de début de l'événement
            $table->dateTime('end_time');                  // Heure de fin de l'événement
            $table->string('access_code')->nullable();    // Code d'accès unique pour l'événement
            $table->timestamps();  
        });
    }

    /**dqd
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event');
    }
};

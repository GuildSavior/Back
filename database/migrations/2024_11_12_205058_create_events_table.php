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
            $table->text('description')->nullable();      // Description de l'événement
            $table->foreignId('guild_id')->constrained('guilds')->onDelete('cascade');  // Référence à la guilde
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // Créateur (owner)
            $table->dateTime('start_time');              // Heure de début
            $table->dateTime('end_time');                // Heure de fin
            $table->integer('dkp_reward');               // Récompense en DKP
            $table->string('access_code', 8)->unique();  // Code d'accès unique (8 caractères)
            $table->boolean('is_active')->default(true); // Event actif ou non
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events'); // ⭐ CORRIGER : events pas event
    }
};

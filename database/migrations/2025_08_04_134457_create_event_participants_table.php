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
        Schema::create('event_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['interested', 'confirmed', 'attended'])->default('interested');
            // interested = intéressé, confirmed = confirmé sa venue, attended = a validé avec le code
            $table->timestamp('confirmed_at')->nullable(); // Quand il a confirmé sa venue
            $table->timestamp('attended_at')->nullable();  // Quand il a validé avec le code
            $table->integer('dkp_earned')->default(0);     // DKP gagnés
            $table->timestamps();
            
            // Un utilisateur ne peut participer qu'une fois par événement
            $table->unique(['event_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_participants');
    }
};

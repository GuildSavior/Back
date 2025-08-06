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
        Schema::create('guild_member_dkp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('guild_id')->constrained('guilds')->onDelete('cascade');
            $table->integer('dkp')->default(0);
            $table->integer('events_joined')->default(0);
            $table->timestamps();
            
            // ⭐ UN UTILISATEUR NE PEUT AVOIR QU'UN SEUL RECORD DKP PAR GUILDE
            $table->unique(['user_id', 'guild_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guild_member_dkp');
    }
};

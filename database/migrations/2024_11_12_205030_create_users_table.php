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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('discord_id')->unique();
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('avatar')->nullable();
            $table->rememberToken()->nullable();
            $table->string('refresh_token')->nullable();
            $table->string('statut')->default('on');
            $table->integer('total_dkp')->default(0);
            $table->timestamps();
            
            // ⭐ RETIRER LES CONTRAINTES POUR L'INSTANT
            // $table->foreignId('role_id')->nullable()->constrained('roles');
            // $table->foreignId('guild_id')->nullable()->constrained('guilds')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

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
            $table->string('password');
            $table->string('username')->unique();   
            $table->rememberToken()->nullable();
            $table->foreignId('guild_id')->constrained('guilds')->onDelete('cascade');
            $table->foreignId('role_id')->nullable()->constrained('roles'); // Référence au rôle du joueur
            $table->integer('total_dkp')->default(0);    // Points DKP, initialisés à 0
            $table->string('refresh_token')->nullable();
            $table->string('statut')->default('on');
            $table->timestamps();
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
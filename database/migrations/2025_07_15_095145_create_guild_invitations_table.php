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
        Schema::create('guild_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guild_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('code', 10)->unique(); // Code d'invitation unique
            $table->integer('max_uses')->nullable(); // Nombre max d'utilisations (null = illimité)
            $table->integer('uses_count')->default(0); // Nombre d'utilisations actuelles
            $table->timestamp('expires_at')->nullable(); // Date d'expiration (null = jamais)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guild_invitations');
    }
};

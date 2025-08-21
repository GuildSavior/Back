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
        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            $table->string('item_name');
            $table->text('description')->nullable();
            $table->foreignId('guild_id')->constrained('guilds')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            
            // ⭐ PRIX ET ENCHÈRES
            $table->integer('starting_price'); // Prix de départ
            $table->integer('buyout_price')->nullable(); // Prix d'achat instantané (optionnel)
            $table->integer('current_bid')->default(0); // Enchère actuelle
            $table->foreignId('current_winner_id')->nullable()->constrained('users')->onDelete('set null');
            
            // ⭐ TIMING
            $table->datetime('start_time'); // Début des enchères
            $table->datetime('end_time'); // Fin des enchères
            
            // ⭐ STATUT
            $table->enum('status', ['upcoming', 'active', 'ended', 'cancelled'])->default('upcoming');
            $table->boolean('is_active')->default(true);
            
            // ⭐ RÉSULTAT
            $table->foreignId('winner_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('final_price')->nullable();
            $table->datetime('ended_at')->nullable();
            
            $table->timestamps();
            
            // ⭐ INDEX POUR LES PERFORMANCES
            $table->index(['guild_id', 'status']);
            $table->index(['start_time', 'end_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auctions');
    }
};

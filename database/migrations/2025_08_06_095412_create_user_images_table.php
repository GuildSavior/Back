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
        Schema::create('user_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title')->nullable(); // Titre optionnel de l'image
            $table->text('description')->nullable(); // Description optionnelle
            $table->string('filename'); // Nom du fichier
            $table->string('original_name'); // Nom original du fichier
            $table->string('path'); // Chemin vers le fichier
            $table->string('mime_type'); // Type MIME (image/jpeg, image/png, etc.)
            $table->integer('file_size'); // Taille en bytes
            $table->integer('width')->nullable(); // Largeur de l'image
            $table->integer('height')->nullable(); // Hauteur de l'image
            $table->boolean('is_public')->default(true); // Visible par les autres ou privé
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_images');
    }
};

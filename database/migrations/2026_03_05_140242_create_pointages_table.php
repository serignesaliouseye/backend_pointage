<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pointages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');
            
            // Sans contrainte foreign key pour l'instant
            $table->unsignedBigInteger('qr_token_id')->nullable();
            
            $table->date('date');
            $table->time('heure_arrivee')->nullable();
            $table->time('heure_sortie')->nullable();
            $table->enum('statut', [
                'present', 
                'retard', 
                'absent', 
                'justifie',
                'conges',
                'formation'
            ])->default('absent');
            
            $table->text('note')->nullable();
            $table->string('justificatif')->nullable();
            
            $table->foreignId('corrige_par')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');
            
            $table->timestamp('corrige_le')->nullable();
            $table->timestamps();
            
            // Un seul pointage par jour par utilisateur
            $table->unique(['user_id', 'date']);
            
            // Index pour les performances
            $table->index('date');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pointages');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->date('date_validite');
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->boolean('est_utilise')->default(false);
            $table->foreignId('cree_par')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->timestamps();
            
            // Index pour les recherches
            $table->index('token');
            $table->index('date_validite');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_tokens');
    }
};
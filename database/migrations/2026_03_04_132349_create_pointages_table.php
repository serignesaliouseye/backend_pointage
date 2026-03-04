<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pointages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('qr_token_id')->nullable()->constrained();
            $table->date('date');
            $table->time('heure_arrivee')->nullable();
            $table->time('heure_sortie')->nullable();
            $table->enum('statut', ['present', 'retard', 'absent', 'justifie'])->default('absent');
            $table->text('note')->nullable();
            $table->string('justificatif')->nullable();
            $table->foreignId('corrige_par')->nullable()->constrained('users');
            $table->timestamp('corrige_le')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('pointages');
    }
};
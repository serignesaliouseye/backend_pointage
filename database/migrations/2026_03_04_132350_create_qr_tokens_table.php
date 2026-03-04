<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('qr_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique();
            $table->date('date_validite');
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->boolean('est_utilise')->default(false);
            $table->foreignId('cree_par')->constrained('users');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('qr_tokens');
    }
};
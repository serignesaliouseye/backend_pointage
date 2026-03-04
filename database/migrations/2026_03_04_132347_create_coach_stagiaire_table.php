<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('coach_stagiaire', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('stagiaire_id')->constrained('users')->onDelete('cascade');
            $table->date('date_affectation')->default(now());
            $table->timestamps();
            
            $table->unique(['coach_id', 'stagiaire_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('coach_stagiaire');
    }
};
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sanctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stagiaire_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('coach_id')->constrained('users')->onDelete('cascade');
            $table->enum('niveau', ['avertissement', 'blame', 'suspension', 'exclusion']);
            $table->string('motif');
            $table->text('description');
            $table->date('date_sanction');
            $table->date('date_fin_suspension')->nullable();
            $table->boolean('est_lue')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sanctions');
    }
};
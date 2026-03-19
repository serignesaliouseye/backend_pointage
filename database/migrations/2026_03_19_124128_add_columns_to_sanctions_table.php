<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sanctions', function (Blueprint $table) {
            $table->foreignId('stagiaire_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('coach_id')->constrained('users')->onDelete('cascade');
            $table->enum('niveau', ['avertissement', 'blame', 'suspension', 'exclusion']);
            $table->string('motif');
            $table->text('description');
            $table->timestamp('date_sanction')->useCurrent();
            $table->date('date_fin_suspension')->nullable();
            $table->boolean('est_lue')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('sanctions', function (Blueprint $table) {
            $table->dropColumn([
                'stagiaire_id', 'coach_id', 'niveau', 'motif',
                'description', 'date_sanction', 'date_fin_suspension', 'est_lue'
            ]);
        });
    }
};
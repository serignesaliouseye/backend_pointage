<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pointages', function (Blueprint $table) {
            $table->foreign('qr_token_id')
                  ->references('id')
                  ->on('qr_tokens')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('pointages', function (Blueprint $table) {
            $table->dropForeign(['qr_token_id']);
        });
    }
};
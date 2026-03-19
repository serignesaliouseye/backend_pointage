<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QrToken extends Model
{
    protected $fillable = [
        'token',
        'date_validite',
        'heure_debut',
        'heure_fin',
        'cree_par',      // ✅ vrai nom
        'est_utilise',   // ✅ vrai nom
    ];

    protected $casts = [
        'date_validite' => 'date',
        'est_utilise' => 'boolean',
    ];

    public function generateur()
    {
        return $this->belongsTo(User::class, 'cree_par');
    }

    public function pointages()
    {
        return $this->hasMany(Pointage::class, 'qr_token_id');
    }
}
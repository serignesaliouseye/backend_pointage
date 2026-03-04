<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pointage extends Model
{
    protected $fillable = [
        'user_id', 'qr_token_id', 'date', 'heure_arrivee', 'heure_sortie',
        'statut', 'note', 'justificatif', 'corrige_par', 'corrige_le'
    ];

    protected $casts = [
        'date' => 'date',
        'heure_arrivee' => 'datetime',
        'heure_sortie' => 'datetime',
        'corrige_le' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function qrToken()
    {
        return $this->belongsTo(QrToken::class);
    }

    public function correcteur()
    {
        return $this->belongsTo(User::class, 'corrige_par');
    }

    // Scope pour la date du jour
    public function scopeToday($query)
    {
        return $query->whereDate('date', now());
    }

    // Calculer la durée de présence
    public function getDureeAttribute()
    {
        if ($this->heure_arrivee && $this->heure_sortie) {
            $arrivee = \Carbon\Carbon::parse($this->heure_arrivee);
            $sortie = \Carbon\Carbon::parse($this->heure_sortie);
            return $arrivee->diffInHours($sortie) . 'h ' . 
                   ($arrivee->diffInMinutes($sortie) % 60) . 'min';
        }
        return null;
    }
}
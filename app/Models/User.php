<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use App\Traits\HasNotifications;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser, HasName
{
    use HasApiTokens, Notifiable;

    // Empêche le chargement automatique des relations
    protected $with = [];

    protected $fillable = [
        'nom', 'prenom', 'email', 'password', 'role', 'telephone',
        'photo', 'promotion', 'date_debut', 'date_fin', 'est_actif'
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'est_actif' => 'boolean'
    ];

    // Filament
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === 'admin';
    }

    public function getFilamentName(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    // Relations
    public function stagiaires()
    {
        return $this->belongsToMany(User::class, 'coach_stagiaire', 'coach_id', 'stagiaire_id');
    }

    public function coachs()
    {
        return $this->belongsToMany(User::class, 'coach_stagiaire', 'stagiaire_id', 'coach_id');
    }

    public function pointages()
    {
        return $this->hasMany(Pointage::class);
    }

    public function sanctions()
    {
        return $this->hasMany(Sanction::class, 'stagiaire_id');
    }

    public function sanctionsDonnees()
    {
        return $this->hasMany(Sanction::class, 'coach_id');
    }

    public function estAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function estCoach(): bool
    {
        return $this->role === 'coach';
    }

    public function estStagiaire(): bool
    {
        return $this->role === 'stagiaire';
    }

    public function getNomCompletAttribute(): string
    {
        return "{$this->prenom} {$this->nom}";
    }
}
<?php
namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

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

    // Méthodes utilitaires
    public function estAdmin()
    {
        return $this->role === 'admin';
    }

    public function estCoach()
    {
        return $this->role === 'coach';
    }

    public function estStagiaire()
    {
        return $this->role === 'stagiaire';
    }

    public function getNomCompletAttribute()
    {
        return "{$this->prenom} {$this->nom}";
    }
}
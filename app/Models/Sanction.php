<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sanction extends Model
{
    protected $fillable = [
        'stagiaire_id',
        'coach_id',
        'niveau',
        'motif',
        'description',
        'date_sanction',
        'date_fin_suspension',
        'est_lue',
    ];

    protected $casts = [
        'date_sanction' => 'datetime',
        'date_fin_suspension' => 'date',
        'est_lue' => 'boolean',
    ];

    public function stagiaire()
    {
        return $this->belongsTo(User::class, 'stagiaire_id');
    }

    public function coach()
    {
        return $this->belongsTo(User::class, 'coach_id');
    }
}
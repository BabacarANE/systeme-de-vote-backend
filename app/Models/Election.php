<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Election extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'titre',
        'date',
        'statut',
        'description'
    ];

    protected $casts = [
        'date' => 'date'
    ];

    public function candidatures()
    {
        return $this->hasMany(Candidature::class);
    }

    public function affectations()
    {
        return $this->hasMany(Affectation::class);
    }
    // Ajout de la relation resultats
    public function resultats()
    {
        return $this->hasManyThrough(
            ResultatBureauVote::class,
            BureauDeVote::class,
            'election_id',  // Clé étrangère sur bureaux_de_vote
            'bureau_de_vote_id',  // Clé étrangère sur resultats_bureau_vote
            'id',  // Clé locale sur elections
            'id'  // Clé locale sur bureaux_de_vote
        );
    }
}

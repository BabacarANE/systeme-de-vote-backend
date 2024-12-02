<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BureauDeVote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'centre_de_vote_id',
        'nom',
        'statut',
        'nombre_inscrits'
    ];

    protected $casts = [
        'nombre_inscrits' => 'integer'
    ];

    public function centreDeVote()
    {
        return $this->belongsTo(CentreDeVote::class);
    }

    public function listeElectorale()
    {
        return $this->hasOne(ListeElectorale::class);
    }

    public function resultats()
    {
        return $this->hasMany(ResultatBureauVote::class);
    }

    public function affectations()
    {
        return $this->hasMany(Affectation::class);
    }

    public function journalVotes()
    {
        return $this->hasMany(JournalVote::class);
    }
}

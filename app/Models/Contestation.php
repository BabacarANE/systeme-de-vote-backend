<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contestation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'resultat_bureau_vote_id',
        'role_representant_id',
        'role_candidat_id',
        'motif',
        'statut',
        'description',
        'pieces_jointes',
        'date_soumission',
        'date_traitement',
        'decision'
    ];

    protected $casts = [
        'pieces_jointes' => 'array',
        'date_soumission' => 'datetime',
        'date_traitement' => 'datetime'
    ];

    public function resultatBureauVote()
    {
        return $this->belongsTo(ResultatBureauVote::class);
    }

    public function roleRepresentant()
    {
        return $this->belongsTo(RoleRepresentant::class);
    }

    public function roleCandidat()
    {
        return $this->belongsTo(RoleCandidat::class);
    }
}

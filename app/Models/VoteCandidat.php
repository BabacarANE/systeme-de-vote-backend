<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VoteCandidat extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'resultat_bureau_vote_id',
        'candidature_id',
        'nombre_voix'
    ];

    protected $casts = [
        'nombre_voix' => 'integer'
    ];

    public function resultatBureauVote()
    {
        return $this->belongsTo(ResultatBureauVote::class);
    }

    public function candidature()
    {
        return $this->belongsTo(Candidature::class);
    }
}

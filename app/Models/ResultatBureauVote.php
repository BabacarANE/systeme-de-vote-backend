<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResultatBureauVote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bureau_de_vote_id',
        'nombre_votants',
        'bulletins_nuls',
        'bulletins_blancs',
        'suffrages_exprimes',
        'pv',
        'validite'
    ];

    protected $casts = [
        'nombre_votants' => 'integer',
        'bulletins_nuls' => 'integer',
        'bulletins_blancs' => 'integer',
        'suffrages_exprimes' => 'integer',
        'validite' => 'boolean'
    ];

    public function bureauDeVote()
    {
        return $this->belongsTo(BureauDeVote::class);
    }

    public function voteCandidats()
    {
        return $this->hasMany(VoteCandidat::class);
    }

    public function contestations()
    {
        return $this->hasMany(Contestation::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Affectation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bureau_de_vote_id',
        'role_personnel_bv_id',
        'election_id',
        'code_role',
        'date_debut',
        'date_fin',
        'statut',
        'date_creation'
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'statut' => 'boolean',
        'date_creation' => 'date'
    ];

    public function bureauDeVote()
    {
        return $this->belongsTo(BureauDeVote::class);
    }

    public function rolePersonnelBV()
    {
        return $this->belongsTo(RolePersonnelBV::class);
    }

    public function election()
    {
        return $this->belongsTo(Election::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ListeElectorale extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'liste_electorales';

    protected $fillable = [
        'bureau_de_vote_id',
        'code',
        'date_creation'
    ];

    protected $casts = [
        'date_creation' => 'date'
    ];

    public function bureauDeVote()
    {
        return $this->belongsTo(BureauDeVote::class);
    }

    public function electeurs()
    {
        return $this->hasMany(RoleElecteur::class);
    }
}

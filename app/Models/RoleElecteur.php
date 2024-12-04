<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoleElecteur extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'personne_id',
        'numero_electeur',
        'a_voter',
        'liste_electorale_id'
    ];

    protected $casts = [
        'a_voter' => 'boolean'
    ];

    // Relations
    public function personne()
    {
        return $this->belongsTo(Personne::class);
    }

    public function listeElectorale()
    {
        return $this->belongsTo(ListeElectorale::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Personne extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nom',
        'prenom',
        'date_naissance',
        'sexe',
        'adresse'
    ];

    protected $casts = [
        'date_naissance' => 'date'
    ];

    // Relations
    public function roleElecteur()
    {
        return $this->hasOne(RoleElecteur::class);
    }

    public function roleCandidat()
    {
        return $this->hasOne(RoleCandidat::class);
    }

    public function roleUtilisateur()
    {
        return $this->hasOne(RoleUtilisateur::class);
    }
}

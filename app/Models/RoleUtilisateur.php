<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoleUtilisateur extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'personne_id',
        'email',
        'mot_de_passe',
        'est_actif'
    ];

    protected $hidden = [
        'mot_de_passe',
        'remember_token',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
        'mot_de_passe' => 'hashed',
        'email_verified_at' => 'datetime',
    ];

    // Relations
    public function personne()
    {
        return $this->belongsTo(Personne::class);
    }

    public function superviseurCENA()
    {
        return $this->hasOne(RoleSuperviseurCENA::class);
    }

    public function personnelBV()
    {
        return $this->hasOne(RolePersonnelBV::class);
    }

    public function representant()
    {
        return $this->hasOne(RoleRepresentant::class);
    }

    public function adminDGE()
    {
        return $this->hasOne(RoleAdminDGE::class);
    }

    public function journalUtilisateur()
    {
        return $this->hasMany(JournalUtilisateur::class);
    }
}

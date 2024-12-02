<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalUtilisateur extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'role_utilisateur_id',
        'action',
        'horodatage',
        'donnees_additionnelles',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'horodatage' => 'datetime',
        'donnees_additionnelles' => 'array'
    ];

    public function roleUtilisateur()
    {
        return $this->belongsTo(RoleUtilisateur::class);
    }
}

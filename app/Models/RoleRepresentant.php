<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoleRepresentant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'role_utilisateur_id',
        'code'
    ];

    public function roleUtilisateur()
    {
        return $this->belongsTo(RoleUtilisateur::class);
    }

    public function contestations()
    {
        return $this->hasMany(Contestation::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoleCandidat extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'personne_id',
        'parti',
        'code',
        'profession'
    ];

    // Relations
    public function personne()
    {
        return $this->belongsTo(Personne::class);
    }

    public function candidatures()
    {
        return $this->hasMany(Candidature::class);
    }

    public function contestations()
    {
        return $this->hasMany(Contestation::class);
    }
}

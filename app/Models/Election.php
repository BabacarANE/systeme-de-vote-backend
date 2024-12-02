<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Election extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'titre',
        'date',
        'statut',
        'description'
    ];

    protected $casts = [
        'date' => 'date'
    ];

    public function candidatures()
    {
        return $this->hasMany(Candidature::class);
    }

    public function affectations()
    {
        return $this->hasMany(Affectation::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Commune extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'departement_id',
        'nom',
        'code'
    ];

    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }

    public function centresDeVote()
    {
        return $this->hasMany(CentreDeVote::class);
    }
}

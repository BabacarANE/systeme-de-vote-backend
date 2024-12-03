<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CentreDeVote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'commune_id',
        'nom',
        'adresse',
        'nombre_de_bureau'
    ];

    protected $casts = [
        'nombre_de_bureau' => 'integer'
    ];

    public function commune()
    {
        return $this->belongsTo(Commune::class);
    }

    public function bureauxDeVote()
    {
        return $this->hasMany(BureauDeVote::class);
    }
    protected $table = 'centre_de_votes';
}

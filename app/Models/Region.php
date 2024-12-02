<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Region extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pays_id',
        'nom',
        'code'
    ];

    public function pays()
    {
        return $this->belongsTo(Pays::class);
    }

    public function departements()
    {
        return $this->hasMany(Departement::class);
    }
}

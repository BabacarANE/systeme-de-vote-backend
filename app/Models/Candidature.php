<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Candidature extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'election_id',
        'role_candidat_id',
        'statut',
        'date_inscription',
        'bulletin'
    ];

    protected $casts = [
        'date_inscription' => 'date'
    ];

    public function election()
    {
        return $this->belongsTo(Election::class);
    }

    public function roleCandidat()
    {
        return $this->belongsTo(RoleCandidat::class);
    }

    public function voteCandidats()
    {
        return $this->hasMany(VoteCandidat::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalVote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bureau_de_vote_id',
        'numero_electeur',
        'horodatage',
        'ip_address'
    ];

    protected $casts = [
        'horodatage' => 'datetime'
    ];

    public function bureauDeVote()
    {
        return $this->belongsTo(BureauDeVote::class);
    }
}

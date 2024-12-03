<?php

namespace App\Http\Controllers\API;

use App\Models\JournalVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JournalVoteController extends BaseController
{
    public function index()
    {
        $journaux = JournalVote::with('bureauDeVote.centreDeVote')
            ->orderBy('horodatage', 'desc')
            ->paginate(50);

        return $this->sendResponse($journaux, 'Liste du journal des votes récupérée avec succès.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bureau_de_vote_id' => 'required|exists:bureaux_de_vote,id',
            'numero_electeur' => 'required|string|exists:role_electeurs,numero_electeur',
            'ip_address' => 'nullable|ip'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Vérifier si l'électeur n'a pas déjà voté
        if (JournalVote::where('numero_electeur', $request->numero_electeur)->exists()) {
            return $this->sendError(
                'Erreur de validation',
                ['message' => 'Cet électeur a déjà voté.'],
                422
            );
        }

        $data = $request->all();
        $data['horodatage'] = now();
        $data['ip_address'] = $request->ip_address ?? $request->ip();

        $journal = JournalVote::create($data);

        return $this->sendResponse($journal, 'Vote enregistré avec succès.', 201);
    }

    public function show(JournalVote $journalVote)
    {
        $journalVote->load('bureauDeVote.centreDeVote');
        return $this->sendResponse($journalVote, 'Détails du vote récupérés avec succès.');
    }

    // Méthodes de recherche et statistiques
    public function recherche(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_debut' => 'sometimes|required|date',
            'date_fin' => 'sometimes|required|date|after:date_debut',
            'bureau_de_vote_id' => 'sometimes|exists:bureaux_de_vote,id',
            'numero_electeur' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $query = JournalVote::with('bureauDeVote.centreDeVote');

        foreach ($request->all() as $key => $value) {
            switch ($key) {
                case 'date_debut':
                    $query->whereDate('horodatage', '>=', $value);
                    break;
                case 'date_fin':
                    $query->whereDate('horodatage', '<=', $value);
                    break;
                case 'bureau_de_vote_id':
                    $query->where('bureau_de_vote_id', $value);
                    break;
                case 'numero_electeur':
                    $query->where('numero_electeur', 'LIKE', "%{$value}%");
                    break;
            }
        }

        $journaux = $query->orderBy('horodatage', 'desc')->paginate(50);

        return $this->sendResponse($journaux, 'Résultats de la recherche récupérés avec succès.');
    }

    public function statistiques(Request $request)
    {
        $stats = [
            'votes_total' => JournalVote::count(),
            'votes_par_heure' => JournalVote::selectRaw('HOUR(horodatage) as heure, COUNT(*) as total')
                ->groupBy('heure')
                ->orderBy('heure')
                ->get(),
            'votes_par_bureau' => JournalVote::with('bureauDeVote')
                ->select('bureau_de_vote_id')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('bureau_de_vote_id')
                ->get(),
            'taux_participation' => $this->calculerTauxParticipation()
        ];

        return $this->sendResponse($stats, 'Statistiques des votes récupérées avec succès.');
    }

    private function calculerTauxParticipation()
    {
        $totalElecteurs = \App\Models\RoleElecteur::count();
        $totalVotants = JournalVote::count();

        return [
            'total_electeurs' => $totalElecteurs,
            'total_votants' => $totalVotants,
            'pourcentage' => $totalElecteurs > 0
                ? round(($totalVotants / $totalElecteurs) * 100, 2)
                : 0
        ];
    }
}

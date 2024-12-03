<?php

namespace App\Http\Controllers\API;

use App\Models\VoteCandidat;
use App\Models\ResultatBureauVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VoteCandidatController extends BaseController
{
    public function index()
    {
        $votes = VoteCandidat::with([
            'resultatBureauVote.bureauDeVote',
            'candidature.roleCandidat.personne'
        ])->get();

        return $this->sendResponse($votes, 'Liste des votes par candidat récupérée avec succès.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'resultat_bureau_vote_id' => 'required|exists:resultats_bureau_vote,id',
            'candidature_id' => 'required|exists:candidatures,id',
            'nombre_voix' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Vérifier si le résultat bureau de vote est déjà validé
        $resultatBV = ResultatBureauVote::find($request->resultat_bureau_vote_id);
        if ($resultatBV->validite) {
            return $this->sendError(
                'Opération non autorisée',
                ['message' => 'Impossible d\'ajouter des votes à un résultat déjà validé.'],
                403
            );
        }

        // Vérifier que le total des voix ne dépasse pas les suffrages exprimés
        $totalVoixActuel = VoteCandidat::where('resultat_bureau_vote_id', $request->resultat_bureau_vote_id)
            ->sum('nombre_voix');

        if (($totalVoixActuel + $request->nombre_voix) > $resultatBV->suffrages_exprimes) {
            return $this->sendError(
                'Erreur de validation',
                ['message' => 'Le total des voix ne peut pas dépasser les suffrages exprimés.'],
                422
            );
        }

        $voteCandidat = VoteCandidat::create($request->all());

        return $this->sendResponse(
            $voteCandidat->load(['resultatBureauVote', 'candidature.roleCandidat']),
            'Vote enregistré avec succès.',
            201
        );
    }

    public function show(VoteCandidat $voteCandidat)
    {
        $voteCandidat->load([
            'resultatBureauVote.bureauDeVote',
            'candidature.roleCandidat.personne'
        ]);

        return $this->sendResponse($voteCandidat, 'Détails du vote récupérés avec succès.');
    }

    public function update(Request $request, VoteCandidat $voteCandidat)
    {
        // Vérifier si le résultat est déjà validé
        if ($voteCandidat->resultatBureauVote->validite) {
            return $this->sendError(
                'Opération non autorisée',
                ['message' => 'Impossible de modifier un vote dans un résultat validé.'],
                403
            );
        }

        $validator = Validator::make($request->all(), [
            'nombre_voix' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Vérifier que le nouveau total ne dépasse pas les suffrages exprimés
        $totalVoixAutresCandidats = VoteCandidat::where('resultat_bureau_vote_id', $voteCandidat->resultat_bureau_vote_id)
            ->where('id', '!=', $voteCandidat->id)
            ->sum('nombre_voix');

        if (($totalVoixAutresCandidats + $request->nombre_voix) > $voteCandidat->resultatBureauVote->suffrages_exprimes) {
            return $this->sendError(
                'Erreur de validation',
                ['message' => 'Le total des voix ne peut pas dépasser les suffrages exprimés.'],
                422
            );
        }

        $voteCandidat->update($request->all());

        return $this->sendResponse(
            $voteCandidat->load(['resultatBureauVote', 'candidature.roleCandidat']),
            'Vote mis à jour avec succès.'
        );
    }

    public function destroy(VoteCandidat $voteCandidat)
    {
        if ($voteCandidat->resultatBureauVote->validite) {
            return $this->sendError(
                'Opération non autorisée',
                ['message' => 'Impossible de supprimer un vote dans un résultat validé.'],
                403
            );
        }

        $voteCandidat->delete();
        return $this->sendResponse(null, 'Vote supprimé avec succès.');
    }

    // Méthodes additionnelles
    public function statistiquesParCandidat($candidatureId)
    {
        $stats = [
            'total_voix' => VoteCandidat::where('candidature_id', $candidatureId)->sum('nombre_voix'),
            'repartition_par_commune' => VoteCandidat::where('candidature_id', $candidatureId)
                ->join('resultats_bureau_vote', 'vote_candidats.resultat_bureau_vote_id', '=', 'resultats_bureau_vote.id')
                ->join('bureaux_de_vote', 'resultats_bureau_vote.bureau_de_vote_id', '=', 'bureaux_de_vote.id')
                ->join('centres_de_vote', 'bureaux_de_vote.centre_de_vote_id', '=', 'centres_de_vote.id')
                ->join('communes', 'centres_de_vote.commune_id', '=', 'communes.id')
                ->selectRaw('communes.nom, SUM(vote_candidats.nombre_voix) as total_voix')
                ->groupBy('communes.id', 'communes.nom')
                ->get()
        ];

        return $this->sendResponse($stats, 'Statistiques des votes du candidat récupérées avec succès.');
    }
}

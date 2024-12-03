<?php

namespace App\Http\Controllers\API;

use App\Models\BureauDeVote;
use App\Models\ListeElectorale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BureauDeVoteController extends BaseController
{
    public function index()
    {
        $bureaux = BureauDeVote::with([
            'centreDeVote.commune',
            'listeElectorale',
            'resultats',
            'affectations'
        ])->get();
        return $this->sendResponse($bureaux, 'Liste des bureaux de vote récupérée avec succès.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'centre_de_vote_id' => 'required|exists:centre_de_votes,id',
            'nom' => 'required|string|max:100',
            'statut' => 'required|in:ACTIF,INACTIF',
            'nombre_inscrits' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Vérifier le nombre de bureaux autorisés
        $centreDeVote = \App\Models\CentreDeVote::find($request->centre_de_vote_id);
        $nombreBureauxExistants = $centreDeVote->bureauxDeVote()->count();

        if ($nombreBureauxExistants >= $centreDeVote->nombre_de_bureau) {
            return $this->sendError(
                'Erreur de validation',
                ['message' => 'Le nombre maximum de bureaux de vote pour ce centre est atteint.'],
                422
            );
        }

        $bureau = BureauDeVote::create($request->all());

        // Créer automatiquement une liste électorale vide pour ce bureau
        ListeElectorale::create([
            'bureau_de_vote_id' => $bureau->id,
            'code' => 'LISTE-' . $bureau->id,
            'date_creation' => now()
        ]);

        return $this->sendResponse(
            $bureau->load(['centreDeVote', 'listeElectorale']),
            'Bureau de vote créé avec succès.',
            201
        );
    }

    public function show(BureauDeVote $bureauDeVote)
    {
        $bureauDeVote->load([
            'centreDeVote.commune',
            'listeElectorale.electeurs',
            'resultats.voteCandidats',
            'affectations.rolePersonnelBV',
            'journalVotes'
        ]);
        return $this->sendResponse($bureauDeVote, 'Détails du bureau de vote récupérés avec succès.');
    }

    public function update(Request $request, BureauDeVote $bureauDeVote)
    {
        $validator = Validator::make($request->all(), [
            'centre_de_vote_id' => 'sometimes|required|exists:centres_de_vote,id',
            'nom' => 'sometimes|required|string|max:100',
            'statut' => 'sometimes|required|in:ACTIF,INACTIF',
            'nombre_inscrits' => 'sometimes|required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $bureauDeVote->update($request->all());
        return $this->sendResponse(
            $bureauDeVote->load(['centreDeVote', 'listeElectorale']),
            'Bureau de vote mis à jour avec succès.'
        );
    }

    public function destroy(BureauDeVote $bureauDeVote)
    {
        try {
            $bureauDeVote->delete();
            return $this->sendResponse(null, 'Bureau de vote supprimé avec succès.');
        } catch (\Exception $e) {
            return $this->sendError(
                'Erreur lors de la suppression',
                ['message' => 'Le bureau de vote ne peut pas être supprimé car il contient des données liées.'],
                409
            );
        }
    }

    // Méthodes additionnelles
    public function statistiquesParticipation(BureauDeVote $bureauDeVote)
    {
        $resultats = $bureauDeVote->resultats()->latest()->first();

        $stats = [
            'inscrits' => $bureauDeVote->nombre_inscrits,
            'votants' => $resultats ? $resultats->nombre_votants : 0,
            'bulletins_nuls' => $resultats ? $resultats->bulletins_nuls : 0,
            'bulletins_blancs' => $resultats ? $resultats->bulletins_blancs : 0,
            'suffrages_exprimes' => $resultats ? $resultats->suffrages_exprimes : 0,
            'taux_participation' => $bureauDeVote->nombre_inscrits > 0 && $resultats
                ? round(($resultats->nombre_votants / $bureauDeVote->nombre_inscrits) * 100, 2)
                : 0
        ];

        return $this->sendResponse($stats, 'Statistiques de participation récupérées avec succès.');
    }

    public function personnelAffecte(BureauDeVote $bureauDeVote)
    {
        $personnel = $bureauDeVote->affectations()
            ->where('statut', true)
            ->where('date_fin', '>=', now())
            ->with(['rolePersonnelBV.roleUtilisateur.personne'])
            ->get();

        return $this->sendResponse($personnel, 'Personnel affecté récupéré avec succès.');
    }

    public function journalVotes(BureauDeVote $bureauDeVote)
    {
        $journal = $bureauDeVote->journalVotes()
            ->orderBy('horodatage', 'desc')
            ->paginate(50);

        return $this->sendResponse($journal, 'Journal des votes récupéré avec succès.');
    }
}

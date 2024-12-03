<?php

namespace App\Http\Controllers\API;

use App\Models\ResultatBureauVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ResultatBureauVoteController extends BaseController
{
    public function index()
    {
        $resultats = ResultatBureauVote::with([
            'bureauDeVote.centreDeVote',
            'voteCandidats.candidature',
            'contestations'
        ])->get();

        return $this->sendResponse($resultats, 'Liste des résultats récupérée avec succès.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bureau_de_vote_id' => 'required|exists:bureaux_de_vote,id',
            'nombre_votants' => 'required|integer|min:0',
            'bulletins_nuls' => 'required|integer|min:0',
            'bulletins_blancs' => 'required|integer|min:0',
            'suffrages_exprimes' => 'required|integer|min:0',
            'pv' => 'nullable|file|mimes:pdf|max:10240', // Max 10MB
            'vote_candidats' => 'required|array',
            'vote_candidats.*.candidature_id' => 'required|exists:candidatures,id',
            'vote_candidats.*.nombre_voix' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Vérifier la cohérence des nombres
        $bureauDeVote = \App\Models\BureauDeVote::find($request->bureau_de_vote_id);
        if ($request->nombre_votants > $bureauDeVote->nombre_inscrits) {
            return $this->sendError(
                'Erreur de validation',
                ['nombre_votants' => ['Le nombre de votants ne peut pas être supérieur au nombre d\'inscrits.']],
                422
            );
        }

        if ($request->suffrages_exprimes > $request->nombre_votants) {
            return $this->sendError(
                'Erreur de validation',
                ['suffrages_exprimes' => ['Le nombre de suffrages exprimés ne peut pas être supérieur au nombre de votants.']],
                422
            );
        }

        // Vérifier que la somme des voix correspond aux suffrages exprimés
        $totalVoix = collect($request->vote_candidats)->sum('nombre_voix');
        if ($totalVoix != $request->suffrages_exprimes) {
            return $this->sendError(
                'Erreur de validation',
                ['vote_candidats' => ['La somme des voix doit être égale aux suffrages exprimés.']],
                422
            );
        }

        // Gérer le PV s'il est fourni
        if ($request->hasFile('pv')) {
            $pvPath = $request->file('pv')->store('pvs', 'public');
        }

        $resultat = ResultatBureauVote::create([
            'bureau_de_vote_id' => $request->bureau_de_vote_id,
            'nombre_votants' => $request->nombre_votants,
            'bulletins_nuls' => $request->bulletins_nuls,
            'bulletins_blancs' => $request->bulletins_blancs,
            'suffrages_exprimes' => $request->suffrages_exprimes,
            'pv' => $pvPath ?? null,
            'validite' => false
        ]);

        // Enregistrer les votes pour chaque candidat
        foreach ($request->vote_candidats as $vote) {
            $resultat->voteCandidats()->create([
                'candidature_id' => $vote['candidature_id'],
                'nombre_voix' => $vote['nombre_voix']
            ]);
        }

        return $this->sendResponse(
            $resultat->load(['bureauDeVote', 'voteCandidats.candidature']),
            'Résultats enregistrés avec succès.',
            201
        );
    }

    public function show(ResultatBureauVote $resultatBureauVote)
    {
        $resultatBureauVote->load([
            'bureauDeVote.centreDeVote',
            'voteCandidats.candidature.roleCandidat.personne',
            'contestations.roleRepresentant'
        ]);

        return $this->sendResponse($resultatBureauVote, 'Détails des résultats récupérés avec succès.');
    }

    public function update(Request $request, ResultatBureauVote $resultatBureauVote)
    {
        // Vérifier si les résultats sont déjà validés
        if ($resultatBureauVote->validite) {
            return $this->sendError(
                'Opération non autorisée',
                ['message' => 'Les résultats validés ne peuvent pas être modifiés.'],
                403
            );
        }

        $validator = Validator::make($request->all(), [
            'nombre_votants' => 'sometimes|required|integer|min:0',
            'bulletins_nuls' => 'sometimes|required|integer|min:0',
            'bulletins_blancs' => 'sometimes|required|integer|min:0',
            'suffrages_exprimes' => 'sometimes|required|integer|min:0',
            'pv' => 'nullable|file|mimes:pdf|max:10240',
            'vote_candidats' => 'sometimes|required|array',
            'vote_candidats.*.candidature_id' => 'required|exists:candidatures,id',
            'vote_candidats.*.nombre_voix' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Mettre à jour le PV si fourni
        if ($request->hasFile('pv')) {
            // Supprimer l'ancien PV s'il existe
            if ($resultatBureauVote->pv) {
                Storage::disk('public')->delete($resultatBureauVote->pv);
            }
            $pvPath = $request->file('pv')->store('pvs', 'public');
            $resultatBureauVote->pv = $pvPath;
        }

        $resultatBureauVote->update($request->except(['pv', 'vote_candidats']));

        // Mettre à jour les votes des candidats si fournis
        if ($request->has('vote_candidats')) {
            $resultatBureauVote->voteCandidats()->delete();
            foreach ($request->vote_candidats as $vote) {
                $resultatBureauVote->voteCandidats()->create($vote);
            }
        }

        return $this->sendResponse(
            $resultatBureauVote->load(['bureauDeVote', 'voteCandidats.candidature']),
            'Résultats mis à jour avec succès.'
        );
    }

    public function destroy(ResultatBureauVote $resultatBureauVote)
    {
        if ($resultatBureauVote->validite) {
            return $this->sendError(
                'Opération non autorisée',
                ['message' => 'Les résultats validés ne peuvent pas être supprimés.'],
                403
            );
        }

        try {
            // Supprimer le PV s'il existe
            if ($resultatBureauVote->pv) {
                Storage::disk('public')->delete($resultatBureauVote->pv);
            }

            $resultatBureauVote->delete();
            return $this->sendResponse(null, 'Résultats supprimés avec succès.');
        } catch (\Exception $e) {
            return $this->sendError(
                'Erreur lors de la suppression',
                ['message' => 'Les résultats ne peuvent pas être supprimés car ils sont liés à des contestations.'],
                409
            );
        }
    }

    // Méthodes additionnelles
    public function valider(Request $request, ResultatBureauVote $resultatBureauVote)
    {
        // Vérifier que l'utilisateur est un superviseur CENA
        if (!auth()->user()->superviseurCENA) {
            return $this->sendError(
                'Opération non autorisée',
                ['message' => 'Seul un superviseur CENA peut valider les résultats.'],
                403
            );
        }

        $validator = Validator::make($request->all(), [
            'commentaire' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $resultatBureauVote->update([
            'validite' => true,
            'commentaire_validation' => $request->commentaire
        ]);

        return $this->sendResponse($resultatBureauVote, 'Résultats validés avec succès.');
    }

    public function getContestations(ResultatBureauVote $resultatBureauVote)
    {
        $contestations = $resultatBureauVote->contestations()
            ->with(['roleRepresentant.roleUtilisateur.personne', 'roleCandidat.personne'])
            ->orderBy('date_soumission', 'desc')
            ->get();

        return $this->sendResponse($contestations, 'Contestations récupérées avec succès.');
    }

    public function getPv(ResultatBureauVote $resultatBureauVote)
    {
        if (!$resultatBureauVote->pv) {
            return $this->sendError('Non trouvé', ['message' => 'Aucun PV n\'est disponible.'], 404);
        }

        $path = storage_path('app/public/' . $resultatBureauVote->pv);

        if (!file_exists($path)) {
            return $this->sendError('Non trouvé', ['message' => 'Le fichier PV est introuvable.'], 404);
        }

        return response()->file($path);
    }

    public function statistiques(ResultatBureauVote $resultatBureauVote)
    {
        $stats = [
            'participation' => [
                'inscrits' => $resultatBureauVote->bureauDeVote->nombre_inscrits,
                'votants' => $resultatBureauVote->nombre_votants,
                'taux_participation' => round(($resultatBureauVote->nombre_votants / $resultatBureauVote->bureauDeVote->nombre_inscrits) * 100, 2),
                'bulletins_nuls' => $resultatBureauVote->bulletins_nuls,
                'bulletins_blancs' => $resultatBureauVote->bulletins_blancs,
                'suffrages_exprimes' => $resultatBureauVote->suffrages_exprimes
            ],
            'resultats_par_candidat' => $resultatBureauVote->voteCandidats()
                ->with('candidature.roleCandidat.personne')
                ->get()
                ->map(function ($voteCandidat) use ($resultatBureauVote) {
                    return [
                        'candidat' => $voteCandidat->candidature->roleCandidat->personne->nom . ' ' .
                            $voteCandidat->candidature->roleCandidat->personne->prenom,
                        'parti' => $voteCandidat->candidature->roleCandidat->parti,
                        'voix' => $voteCandidat->nombre_voix,
                        'pourcentage' => $resultatBureauVote->suffrages_exprimes > 0
                            ? round(($voteCandidat->nombre_voix / $resultatBureauVote->suffrages_exprimes) * 100, 2)
                            : 0
                    ];
                }),
            'contestations' => [
                'total' => $resultatBureauVote->contestations()->count(),
                'en_cours' => $resultatBureauVote->contestations()->where('statut', 'EN_ATTENTE')->count(),
                'traitees' => $resultatBureauVote->contestations()->whereIn('statut', ['ACCEPTEE', 'REJETEE'])->count()
            ]
        ];

        return $this->sendResponse($stats, 'Statistiques des résultats récupérées avec succès.');
    }

    public function resultatsFinalises()
    {
        $resultatsValides = ResultatBureauVote::where('validite', true)
            ->with(['bureauDeVote.centreDeVote.commune', 'voteCandidats.candidature'])
            ->get();

        $resultatsGlobaux = [
            'participation' => [
                'inscrits' => $resultatsValides->sum('bureauDeVote.nombre_inscrits'),
                'votants' => $resultatsValides->sum('nombre_votants'),
                'bulletins_nuls' => $resultatsValides->sum('bulletins_nuls'),
                'bulletins_blancs' => $resultatsValides->sum('bulletins_blancs'),
                'suffrages_exprimes' => $resultatsValides->sum('suffrages_exprimes')
            ],
            'resultats_par_candidat' => []
        ];

        // Calculer les résultats par candidat
        $resultatsValides->each(function ($resultat) use (&$resultatsGlobaux) {
            foreach ($resultat->voteCandidats as $voteCandidat) {
                $candidatId = $voteCandidat->candidature->roleCandidat->id;
                if (!isset($resultatsGlobaux['resultats_par_candidat'][$candidatId])) {
                    $resultatsGlobaux['resultats_par_candidat'][$candidatId] = [
                        'candidat' => $voteCandidat->candidature->roleCandidat->personne->nom . ' ' .
                            $voteCandidat->candidature->roleCandidat->personne->prenom,
                        'parti' => $voteCandidat->candidature->roleCandidat->parti,
                        'total_voix' => 0
                    ];
                }
                $resultatsGlobaux['resultats_par_candidat'][$candidatId]['total_voix'] += $voteCandidat->nombre_voix;
            }
        });

        return $this->sendResponse($resultatsGlobaux, 'Résultats finalisés récupérés avec succès.');
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Models\Election;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ElectionController extends BaseController
{
    public function index()
    {
        $elections = Election::with([
            'candidatures.roleCandidat.personne',
            'affectations'
        ])->get();

        return $this->sendResponse($elections, 'Liste des élections récupérée avec succès.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'titre' => 'required|string|max:255',
            'date' => 'required|date|after:today',
            'statut' => 'required|in:PLANIFIEE,EN_COURS,TERMINEE,ANNULEE',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Vérifier s'il n'y a pas déjà une élection à cette date
        $electionExistante = Election::whereDate('date', $request->date)->exists();
        if ($electionExistante) {
            return $this->sendError(
                'Erreur de validation',
                ['date' => ['Une élection est déjà planifiée à cette date.']],
                422
            );
        }

        $election = Election::create($request->all());

        return $this->sendResponse($election, 'Élection créée avec succès.', 201);
    }

    public function show(Election $election)
    {
        $election->load([
            'candidatures.roleCandidat.personne',
            'affectations.rolePersonnelBV.roleUtilisateur.personne'
        ]);

        return $this->sendResponse($election, 'Détails de l\'élection récupérés avec succès.');
    }

    public function update(Request $request, Election $election)
    {
        // Vérifier si l'élection peut être modifiée
        if ($election->statut === 'TERMINEE' || $election->statut === 'ANNULEE') {
            return $this->sendError(
                'Opération non autorisée',
                ['message' => 'Une élection terminée ou annulée ne peut pas être modifiée.'],
                403
            );
        }

        $validator = Validator::make($request->all(), [
            'titre' => 'sometimes|required|string|max:255',
            'date' => 'sometimes|required|date|after:today',
            'statut' => 'sometimes|required|in:PLANIFIEE,EN_COURS,TERMINEE,ANNULEE',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Vérifier la date si elle est modifiée
        if ($request->has('date') && $request->date !== $election->date) {
            $electionExistante = Election::where('id', '!=', $election->id)
                ->whereDate('date', $request->date)
                ->exists();

            if ($electionExistante) {
                return $this->sendError(
                    'Erreur de validation',
                    ['date' => ['Une élection est déjà planifiée à cette date.']],
                    422
                );
            }
        }

        $election->update($request->all());

        return $this->sendResponse($election, 'Élection mise à jour avec succès.');
    }

    public function destroy(Election $election)
    {
        if ($election->statut !== 'PLANIFIEE') {
            return $this->sendError(
                'Opération non autorisée',
                ['message' => 'Seule une élection planifiée peut être supprimée.'],
                403
            );
        }

        try {
            $election->delete();
            return $this->sendResponse(null, 'Élection supprimée avec succès.');
        } catch (\Exception $e) {
            return $this->sendError(
                'Erreur lors de la suppression',
                ['message' => 'L\'élection ne peut pas être supprimée car elle contient des données liées.'],
                409
            );
        }
    }

    // Méthodes additionnelles pour la gestion des élections

    public function demarrerElection(Election $election)
    {
        if ($election->statut !== 'PLANIFIEE') {
            return $this->sendError(
                'Opération non autorisée',
                ['message' => 'Seule une élection planifiée peut être démarrée.'],
                403
            );
        }

        if ($election->date > now()) {
            return $this->sendError(
                'Opération non autorisée',
                ['message' => 'L\'élection ne peut pas être démarrée avant sa date prévue.'],
                403
            );
        }

        $election->update(['statut' => 'EN_COURS']);

        return $this->sendResponse($election, 'Élection démarrée avec succès.');
    }

    public function terminerElection(Election $election)
    {
        if ($election->statut !== 'EN_COURS') {
            return $this->sendError(
                'Opération non autorisée',
                ['message' => 'Seule une élection en cours peut être terminée.'],
                403
            );
        }

        $election->update(['statut' => 'TERMINEE']);

        return $this->sendResponse($election, 'Élection terminée avec succès.');
    }

    public function annulerElection(Request $request, Election $election)
    {
        $validator = Validator::make($request->all(), [
            'motif_annulation' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        if ($election->statut === 'TERMINEE' || $election->statut === 'ANNULEE') {
            return $this->sendError(
                'Opération non autorisée',
                ['message' => 'Cette élection ne peut plus être annulée.'],
                403
            );
        }

        $election->update([
            'statut' => 'ANNULEE',
            'description' => $election->description . "\nMotif d'annulation: " . $request->motif_annulation
        ]);

        return $this->sendResponse($election, 'Élection annulée avec succès.');
    }

    public function getCandidatures(Election $election)
    {
        $candidatures = $election->candidatures()
            ->with(['roleCandidat.personne'])
            ->get();

        return $this->sendResponse($candidatures, 'Liste des candidatures récupérée avec succès.');
    }

    public function getResultatsProvisoires(Election $election)
    {
        if ($election->statut !== 'EN_COURS' && $election->statut !== 'TERMINEE') {
            return $this->sendError(
                'Opération non autorisée',
                ['message' => 'Les résultats ne sont pas encore disponibles.'],
                403
            );
        }

        $resultats = [
            'participation' => [
                'bureaux_decomptes' => 0,
                'total_inscrits' => 0,
                'total_votants' => 0,
                'bulletins_nuls' => 0,
                'bulletins_blancs' => 0,
                'suffrages_exprimes' => 0
            ],
            'resultats_candidats' => [],
            'derniere_mise_a_jour' => now()->format('Y-m-d H:i:s'),
            'progression_depouillement' => 0
        ];

        // Calcul des résultats par bureau de vote
        $election->candidatures->each(function ($candidature) use (&$resultats) {
            $totalVoix = $candidature->voteCandidats()
                ->whereHas('resultatBureauVote', function ($query) {
                    $query->where('validite', true);
                })->sum('nombre_voix');

            $resultats['resultats_candidats'][] = [
                'candidat' => $candidature->roleCandidat->personne->nom . ' ' .
                    $candidature->roleCandidat->personne->prenom,
                'parti' => $candidature->roleCandidat->parti,
                'total_voix' => $totalVoix
            ];
        });

        // Calcul des statistiques de participation
        $bureaux = \App\Models\BureauDeVote::count();
        $bureauxDecomptes = \App\Models\ResultatBureauVote::where('validite', true)->count();

        $resultats['participation']['bureaux_decomptes'] = $bureauxDecomptes;
        $resultats['progression_depouillement'] = $bureaux > 0
            ? round(($bureauxDecomptes / $bureaux) * 100, 2)
            : 0;

        return $this->sendResponse($resultats, 'Résultats provisoires récupérés avec succès.');
    }

    public function getStatistiques(Election $election)
    {
        $stats = [
            'participation' => [
                'inscrits' => \App\Models\RoleElecteur::count(),
                'votants' => \App\Models\RoleElecteur::where('a_voter', true)->count()
            ],
            'bureaux_vote' => [
                'total' => \App\Models\BureauDeVote::count(),
                'ayant_transmis' => \App\Models\ResultatBureauVote::where('validite', true)->count()
            ],
            'candidats' => [
                'total' => $election->candidatures()->count(),
                'par_sexe' => [
                    'hommes' => $election->candidatures()
                        ->whereHas('roleCandidat.personne', function ($query) {
                            $query->where('sexe', 'M');
                        })->count(),
                    'femmes' => $election->candidatures()
                        ->whereHas('roleCandidat.personne', function ($query) {
                            $query->where('sexe', 'F');
                        })->count()
                ]
            ],
            'contestations' => [
                'total' => \App\Models\Contestation::count(),
                'en_cours' => \App\Models\Contestation::where('statut', 'EN_ATTENTE')->count(),
                'traitees' => \App\Models\Contestation::whereIn('statut', ['ACCEPTEE', 'REJETEE'])->count()
            ]
        ];

        return $this->sendResponse($stats, 'Statistiques de l\'élection récupérées avec succès.');
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Models\Affectation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AffectationController extends BaseController
{
    public function index()
    {
        $affectations = Affectation::with([
            'bureauDeVote.centreDeVote',
            'rolePersonnelBV.roleUtilisateur.personne',
            'election'
        ])->get();

        return $this->sendResponse($affectations, 'Liste des affectations récupérée avec succès.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bureau_de_vote_id' => 'required|exists:bureaux_de_vote,id',
            'role_personnel_bv_id' => 'required|exists:role_personnel_bvs,id',
            'election_id' => 'required|exists:elections,id',
            'code_role' => 'required|string',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'statut' => 'boolean'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Vérifier si le personnel n'est pas déjà affecté pour la même période
        $affectationExistante = Affectation::where('role_personnel_bv_id', $request->role_personnel_bv_id)
            ->where(function($query) use ($request) {
                $query->whereBetween('date_debut', [$request->date_debut, $request->date_fin])
                    ->orWhereBetween('date_fin', [$request->date_debut, $request->date_fin]);
            })
            ->exists();

        if ($affectationExistante) {
            return $this->sendError(
                'Erreur de validation',
                ['message' => 'Le personnel est déjà affecté pendant cette période.'],
                422
            );
        }

        $data = $request->all();
        $data['date_creation'] = now();

        $affectation = Affectation::create($data);

        return $this->sendResponse(
            $affectation->load(['bureauDeVote', 'rolePersonnelBV', 'election']),
            'Affectation créée avec succès.',
            201
        );
    }

    public function show(Affectation $affectation)
    {
        $affectation->load([
            'bureauDeVote.centreDeVote',
            'rolePersonnelBV.roleUtilisateur.personne',
            'election'
        ]);

        return $this->sendResponse($affectation, 'Détails de l\'affectation récupérés avec succès.');
    }

    public function update(Request $request, Affectation $affectation)
    {
        $validator = Validator::make($request->all(), [
            'date_debut' => 'sometimes|required|date',
            'date_fin' => 'sometimes|required|date|after:date_debut',
            'statut' => 'sometimes|boolean',
            'code_role' => 'sometimes|required|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        if ($request->has(['date_debut', 'date_fin'])) {
            // Vérifier les chevauchements de période
            $affectationExistante = Affectation::where('role_personnel_bv_id', $affectation->role_personnel_bv_id)
                ->where('id', '!=', $affectation->id)
                ->where(function($query) use ($request) {
                    $query->whereBetween('date_debut', [$request->date_debut, $request->date_fin])
                        ->orWhereBetween('date_fin', [$request->date_debut, $request->date_fin]);
                })
                ->exists();

            if ($affectationExistante) {
                return $this->sendError(
                    'Erreur de validation',
                    ['message' => 'Le personnel est déjà affecté pendant cette période.'],
                    422
                );
            }
        }

        $affectation->update($request->all());

        return $this->sendResponse(
            $affectation->load(['bureauDeVote', 'rolePersonnelBV', 'election']),
            'Affectation mise à jour avec succès.'
        );
    }

    public function destroy(Affectation $affectation)
    {
        // Vérifier si l'affectation est en cours
        if ($affectation->date_debut <= now() && $affectation->date_fin >= now()) {
            return $this->sendError(
                'Opération non autorisée',
                ['message' => 'Impossible de supprimer une affectation en cours.'],
                403
            );
        }

        try {
            $affectation->delete();
            return $this->sendResponse(null, 'Affectation supprimée avec succès.');
        } catch (\Exception $e) {
            return $this->sendError(
                'Erreur lors de la suppression',
                ['message' => 'Une erreur est survenue lors de la suppression.'],
                409
            );
        }
    }

    // Méthodes additionnelles

    public function terminerAffectation(Affectation $affectation)
    {
        if (!$affectation->statut) {
            return $this->sendError(
                'Opération non autorisée',
                ['message' => 'Cette affectation est déjà terminée.'],
                403
            );
        }

        $affectation->update([
            'statut' => false,
            'date_fin' => now()
        ]);

        return $this->sendResponse($affectation, 'Affectation terminée avec succès.');
    }

    public function getAffectationsActives()
    {
        $affectationsActives = Affectation::where('statut', true)
            ->where('date_debut', '<=', now())
            ->where('date_fin', '>=', now())
            ->with([
                'bureauDeVote.centreDeVote',
                'rolePersonnelBV.roleUtilisateur.personne',
                'election'
            ])
            ->get();

        return $this->sendResponse($affectationsActives, 'Affectations actives récupérées avec succès.');
    }

    public function getAffectationsParBureauDeVote($bureauDeVoteId)
    {
        $affectations = Affectation::where('bureau_de_vote_id', $bureauDeVoteId)
            ->with([
                'rolePersonnelBV.roleUtilisateur.personne',
                'election'
            ])
            ->orderBy('date_debut', 'desc')
            ->get();

        return $this->sendResponse($affectations, 'Affectations du bureau de vote récupérées avec succès.');
    }

    public function getAffectationsParPersonnel($rolePersonnelBVId)
    {
        $affectations = Affectation::where('role_personnel_bv_id', $rolePersonnelBVId)
            ->with([
                'bureauDeVote.centreDeVote',
                'election'
            ])
            ->orderBy('date_debut', 'desc')
            ->get();

        return $this->sendResponse($affectations, 'Affectations du personnel récupérées avec succès.');
    }

    public function statistiques()
    {
        $stats = [
            'total_affectations' => Affectation::count(),
            'affectations_actives' => Affectation::where('statut', true)
                ->where('date_debut', '<=', now())
                ->where('date_fin', '>=', now())
                ->count(),
            'repartition_par_role' => Affectation::select('code_role')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('code_role')
                ->get(),
            'affectations_par_bureau' => Affectation::where('statut', true)
                ->join('bureaux_de_vote', 'affectations.bureau_de_vote_id', '=', 'bureaux_de_vote.id')
                ->select('bureaux_de_vote.nom')
                ->selectRaw('COUNT(*) as total_personnel')
                ->groupBy('bureaux_de_vote.id', 'bureaux_de_vote.nom')
                ->get()
        ];

        return $this->sendResponse($stats, 'Statistiques des affectations récupérées avec succès.');
    }

    public function planningParBureau(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bureau_de_vote_id' => 'required|exists:bureaux_de_vote,id',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $planning = Affectation::where('bureau_de_vote_id', $request->bureau_de_vote_id)
            ->whereBetween('date_debut', [$request->date_debut, $request->date_fin])
            ->orWhereBetween('date_fin', [$request->date_debut, $request->date_fin])
            ->with([
                'rolePersonnelBV.roleUtilisateur.personne',
                'election'
            ])
            ->orderBy('date_debut')
            ->get();

        return $this->sendResponse($planning, 'Planning du bureau récupéré avec succès.');
    }
}

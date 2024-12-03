<?php

namespace App\Http\Controllers\API;

use App\Models\Departement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartementController extends BaseController
{
    public function index()
    {
        $departements = Departement::with(['region.pays', 'communes'])->get();
        return $this->sendResponse($departements, 'Liste des départements récupérée avec succès.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'region_id' => 'required|exists:regions,id',
            'nom' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:departements'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Vérifier l'unicité du nom dans la même région
        $exists = Departement::where('region_id', $request->region_id)
            ->where('nom', $request->nom)
            ->exists();

        if ($exists) {
            return $this->sendError(
                'Erreur de validation',
                ['nom' => ['Un département avec ce nom existe déjà dans cette région.']],
                422
            );
        }

        $departement = Departement::create($request->all());
        return $this->sendResponse(
            $departement->load('region.pays'),
            'Département créé avec succès.',
            201
        );
    }

    public function show(Departement $departement)
    {
        $departement->load([
            'region.pays',
            'communes.centresDeVote'
        ]);
        return $this->sendResponse($departement, 'Détails du département récupérés avec succès.');
    }

    public function update(Request $request, Departement $departement)
    {
        $validator = Validator::make($request->all(), [
            'region_id' => 'sometimes|required|exists:regions,id',
            'nom' => 'sometimes|required|string|max:100',
            'code' => 'sometimes|required|string|max:50|unique:departements,code,' . $departement->id
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Vérifier l'unicité du nom dans la même région si le nom est modifié
        if ($request->has('nom') && $request->nom !== $departement->nom) {
            $exists = Departement::where('region_id', $request->region_id ?? $departement->region_id)
                ->where('nom', $request->nom)
                ->where('id', '!=', $departement->id)
                ->exists();

            if ($exists) {
                return $this->sendError(
                    'Erreur de validation',
                    ['nom' => ['Un département avec ce nom existe déjà dans cette région.']],
                    422
                );
            }
        }

        $departement->update($request->all());
        return $this->sendResponse(
            $departement->load('region.pays'),
            'Département mis à jour avec succès.'
        );
    }

    public function destroy(Departement $departement)
    {
        try {
            $departement->delete();
            return $this->sendResponse(null, 'Département supprimé avec succès.');
        } catch (\Exception $e) {
            return $this->sendError(
                'Erreur lors de la suppression',
                ['message' => 'Le département ne peut pas être supprimé car il est lié à des communes.'],
                409
            );
        }
    }

    // Méthodes additionnelles
    public function communes(Departement $departement)
    {
        return $this->sendResponse(
            $departement->communes()->with('centresDeVote')->get(),
            'Communes du département récupérées avec succès.'
        );
    }

    public function centresDeVote(Departement $departement)
    {
        $centresDeVote = $departement->communes()
            ->with('centresDeVote.bureauxDeVote')
            ->get()
            ->pluck('centresDeVote')
            ->flatten();

        return $this->sendResponse($centresDeVote, 'Centres de vote du département récupérés avec succès.');
    }

    public function statistiques(Departement $departement)
    {
        $stats = [
            'nombre_communes' => $departement->communes()->count(),
            'nombre_centres_vote' => $departement->communes()
                ->withCount('centresDeVote')
                ->get()
                ->sum('centres_de_vote_count'),
            'nombre_bureaux_vote' => $departement->communes()
                ->with('centresDeVote')
                ->get()
                ->sum(function($commune) {
                    return $commune->centresDeVote->sum('nombre_de_bureau');
                }),
            'nombre_electeurs_inscrits' => $departement->communes()
                ->withSum('centresDeVote.bureauxDeVote', 'nombre_inscrits')
                ->get()
                ->sum('bureaux_de_vote_sum_nombre_inscrits')
        ];

        return $this->sendResponse($stats, 'Statistiques du département récupérées avec succès.');
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RegionController extends BaseController
{
    public function index()
    {
        $regions = Region::with(['pays', 'departements'])->get();
        return $this->sendResponse($regions, 'Liste des régions récupérée avec succès.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pays_id' => 'required|exists:pays,id',
            'nom' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:regions'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Vérifier l'unicité du nom dans le même pays
        $exists = Region::where('pays_id', $request->pays_id)
            ->where('nom', $request->nom)
            ->exists();

        if ($exists) {
            return $this->sendError(
                'Erreur de validation',
                ['nom' => ['Une région avec ce nom existe déjà dans ce pays.']],
                422
            );
        }

        $region = Region::create($request->all());
        return $this->sendResponse(
            $region->load('pays'),
            'Région créée avec succès.',
            201
        );
    }

    public function show(Region $region)
    {
        $region->load(['pays', 'departements.communes']);
        return $this->sendResponse($region, 'Détails de la région récupérés avec succès.');
    }

    public function update(Request $request, Region $region)
    {
        $validator = Validator::make($request->all(), [
            'pays_id' => 'sometimes|required|exists:pays,id',
            'nom' => 'sometimes|required|string|max:100',
            'code' => 'sometimes|required|string|max:50|unique:regions,code,' . $region->id
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Vérifier l'unicité du nom dans le même pays si le nom est modifié
        if ($request->has('nom') && $request->nom !== $region->nom) {
            $exists = Region::where('pays_id', $request->pays_id ?? $region->pays_id)
                ->where('nom', $request->nom)
                ->where('id', '!=', $region->id)
                ->exists();

            if ($exists) {
                return $this->sendError(
                    'Erreur de validation',
                    ['nom' => ['Une région avec ce nom existe déjà dans ce pays.']],
                    422
                );
            }
        }

        $region->update($request->all());
        return $this->sendResponse(
            $region->load('pays'),
            'Région mise à jour avec succès.'
        );
    }

    public function destroy(Region $region)
    {
        try {
            $region->delete();
            return $this->sendResponse(null, 'Région supprimée avec succès.');
        } catch (\Exception $e) {
            return $this->sendError(
                'Erreur lors de la suppression',
                ['message' => 'La région ne peut pas être supprimée car elle est liée à des départements.'],
                409
            );
        }
    }

    // Méthodes additionnelles
    public function departements(Region $region)
    {
        return $this->sendResponse(
            $region->departements()->with('communes')->get(),
            'Départements de la région récupérés avec succès.'
        );
    }

    public function centresDeVote(Region $region)
    {
        $centresDeVote = $region->departements()
            ->with(['communes.centresDeVote'])
            ->get()
            ->pluck('communes')
            ->flatten()
            ->pluck('centresDeVote')
            ->flatten();

        return $this->sendResponse($centresDeVote, 'Centres de vote de la région récupérés avec succès.');
    }

    public function statistiques(Region $region)
    {
        $stats = [
            'nombre_departements' => $region->departements()->count(),
            'nombre_communes' => $region->departements()->withCount('communes')->get()
                ->sum('communes_count'),
            'nombre_centres_vote' => $region->departements()
                ->withCount('communes.centresDeVote')
                ->get()
                ->sum(function($departement) {
                    return $departement->communes->sum('centres_de_vote_count');
                }),
            'nombre_bureaux_vote' => $region->departements()
                ->withCount('communes.centresDeVote.bureauxDeVote')
                ->get()
                ->sum(function($departement) {
                    return $departement->communes->sum(function($commune) {
                        return $commune->centresDeVote->sum('bureaux_de_vote_count');
                    });
                })
        ];

        return $this->sendResponse($stats, 'Statistiques de la région récupérées avec succès.');
    }
}

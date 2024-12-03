<?php

namespace App\Http\Controllers\API;

use App\Models\Pays;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaysController extends BaseController
{
    public function index()
    {
        $pays = Pays::with(['regions'])->get();
        return $this->sendResponse($pays, 'Liste des pays récupérée avec succès.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:100|unique:pays',
            'code' => 'required|string|max:50|unique:pays'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $pays = Pays::create($request->all());
        return $this->sendResponse($pays, 'Pays créé avec succès.', 201);
    }

    public function show(Pays $pays)
    {
        $pays->load(['regions.departements.communes']);
        return $this->sendResponse($pays, 'Détails du pays récupérés avec succès.');
    }

    public function update(Request $request, Pays $pays)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|required|string|max:100|unique:pays,nom,' . $pays->id,
            'code' => 'sometimes|required|string|max:50|unique:pays,code,' . $pays->id
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $pays->update($request->all());
        return $this->sendResponse($pays, 'Pays mis à jour avec succès.');
    }

    public function destroy(Pays $pays)
    {
        try {
            $pays->delete();
            return $this->sendResponse(null, 'Pays supprimé avec succès.');
        } catch (\Exception $e) {
            return $this->sendError(
                'Erreur lors de la suppression',
                ['message' => 'Le pays ne peut pas être supprimé car il est lié à des régions.'],
                409
            );
        }
    }

    // Méthodes additionnelles
    public function regions(Pays $pays)
    {
        return $this->sendResponse(
            $pays->regions()->with('departements')->get(),
            'Régions du pays récupérées avec succès.'
        );
    }

    public function statistiques(Pays $pays)
    {
        $stats = [
            'nombre_regions' => $pays->regions()->count(),
            'nombre_departements' => $pays->regions()->withCount('departements')->get()
                ->sum('departements_count'),
            'nombre_communes' => $pays->regions()->withCount('departements.communes')->get()
                ->sum(function($region) {
                    return $region->departements->sum('communes_count');
                })
        ];

        return $this->sendResponse($stats, 'Statistiques du pays récupérées avec succès.');
    }
}

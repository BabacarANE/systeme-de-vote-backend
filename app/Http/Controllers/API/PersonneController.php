<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Personne;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PersonneController extends BaseController
{
    public function index()
    {
        $personnes = Personne::with(['roleElecteur', 'roleCandidat', 'roleUtilisateur'])->get();
        return $this->sendResponse($personnes, 'Liste des personnes récupérée avec succès.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'date_naissance' => 'required|date',
            'sexe' => 'required|in:M,F',
            'adresse' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $personne = Personne::create($request->all());
        return $this->sendResponse($personne, 'Personne créée avec succès.', 201);
    }

    public function show(Personne $personne)
    {
        $personne->load(['roleElecteur', 'roleCandidat', 'roleUtilisateur']);
        return $this->sendResponse($personne, 'Détails de la personne récupérés avec succès.');
    }

    public function update(Request $request, Personne $personne)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|required|string|max:255',
            'prenom' => 'sometimes|required|string|max:255',
            'date_naissance' => 'sometimes|required|date',
            'sexe' => 'sometimes|required|in:M,F',
            'adresse' => 'sometimes|required|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $personne->update($request->all());
        return $this->sendResponse($personne, 'Personne mise à jour avec succès.');
    }

    public function destroy(Personne $personne)
    {
        $personne->delete();
        return $this->sendResponse(null, 'Personne supprimée avec succès.');
    }
}

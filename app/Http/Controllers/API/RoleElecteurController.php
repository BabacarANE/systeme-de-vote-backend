<?php

namespace App\Http\Controllers\API;

use App\Models\RoleElecteur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleElecteurController extends BaseController
{
    public function index()
    {
        $electeurs = RoleElecteur::with(['personne', 'listeElectorale'])->get();
        return $this->sendResponse($electeurs, 'Liste des électeurs récupérée avec succès.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'personne_id' => 'required|exists:personnes,id',
            'numero_electeur' => 'required|string|unique:role_electeurs',
            'a_voter' => 'boolean'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $electeur = RoleElecteur::create($request->all());
        return $this->sendResponse($electeur, 'Électeur créé avec succès.', 201);
    }

    public function show(RoleElecteur $electeur)
    {
        $electeur->load(['personne', 'listeElectorale']);
        return $this->sendResponse($electeur, 'Détails de l\'électeur récupérés avec succès.');
    }

    public function update(Request $request, RoleElecteur $electeur)
    {
        $validator = Validator::make($request->all(), [
            'personne_id' => 'sometimes|required|exists:personnes,id',
            'numero_electeur' => 'sometimes|required|string|unique:role_electeurs,numero_electeur,' . $electeur->id,
            'a_voter' => 'boolean'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $electeur->update($request->all());
        return $this->sendResponse($electeur, 'Électeur mis à jour avec succès.');
    }

    public function destroy(RoleElecteur $electeur)
    {
        $electeur->delete();
        return $this->sendResponse(null, 'Électeur supprimé avec succès.');
    }
}

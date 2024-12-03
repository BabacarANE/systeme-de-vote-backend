<?php

namespace App\Http\Controllers\API;

use App\Models\RoleCandidat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleCandidatController extends BaseController
{
    public function index()
    {
        $candidats = RoleCandidat::with(['personne', 'candidatures'])->get();
        return $this->sendResponse($candidats, 'Liste des candidats récupérée avec succès.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'personne_id' => 'required|exists:personnes,id',
            'parti' => 'required|string|max:100',
            'code' => 'required|string|unique:role_candidats',
            'profession' => 'required|string|max:100'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $candidat = RoleCandidat::create($request->all());
        return $this->sendResponse($candidat, 'Candidat créé avec succès.', 201);
    }

    public function show(RoleCandidat $candidat)
    {
        $candidat->load(['personne', 'candidatures']);
        return $this->sendResponse($candidat, 'Détails du candidat récupérés avec succès.');
    }

    public function update(Request $request, RoleCandidat $candidat)
    {
        $validator = Validator::make($request->all(), [
            'personne_id' => 'sometimes|required|exists:personnes,id',
            'parti' => 'sometimes|required|string|max:100',
            'code' => 'sometimes|required|string|unique:role_candidats,code,' . $candidat->id,
            'profession' => 'sometimes|required|string|max:100'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $candidat->update($request->all());
        return $this->sendResponse($candidat, 'Candidat mis à jour avec succès.');
    }

    public function destroy(RoleCandidat $candidat)
    {
        try {
            $candidat->delete();
            return $this->sendResponse(null, 'Candidat supprimé avec succès.');
        } catch (\Exception $e) {
            return $this->sendError('Erreur lors de la suppression', ['message' => 'Le candidat ne peut pas être supprimé car il est lié à des candidatures.'], 409);
        }
    }

    // Méthodes supplémentaires spécifiques aux candidats
    public function candidatures(RoleCandidat $candidat)
    {
        return $this->sendResponse($candidat->candidatures()->with('election')->get(),
            'Candidatures du candidat récupérées avec succès.');
    }

    public function contestations(RoleCandidat $candidat)
    {
        return $this->sendResponse($candidat->contestations()->with('resultatBureauVote')->get(),
            'Contestations du candidat récupérées avec succès.');
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Models\RolePersonnelBV;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RolePersonnelBVController extends BaseController
{
    public function index()
    {
        $personnel = RolePersonnelBV::with([
            'roleUtilisateur.personne',
            'affectations.bureauDeVote'
        ])->get();

        return $this->sendResponse($personnel, 'Liste du personnel des bureaux de vote récupérée avec succès.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_utilisateur_id' => 'required|exists:role_utilisateurs,id|unique:role_personnel_bvs',
            'code' => 'sometimes|string|unique:role_personnel_bvs'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $data = $request->all();
        // Génération automatique du code si non fourni
        if (!isset($data['code'])) {
            $data['code'] = 'PERS-' . Str::upper(Str::random(8));
        }

        $personnel = RolePersonnelBV::create($data);

        return $this->sendResponse(
            $personnel->load('roleUtilisateur.personne'),
            'Personnel de bureau de vote créé avec succès.',
            201
        );
    }

    public function show(RolePersonnelBV $personnel)
    {
        $personnel->load([
            'roleUtilisateur.personne',
            'affectations.bureauDeVote',
            'affectations.election'
        ]);

        return $this->sendResponse($personnel, 'Détails du personnel récupérés avec succès.');
    }

    public function update(Request $request, RolePersonnelBV $personnel)
    {
        $validator = Validator::make($request->all(), [
            'role_utilisateur_id' => 'sometimes|required|exists:role_utilisateurs,id|unique:role_personnel_bvs,role_utilisateur_id,' . $personnel->id,
            'code' => 'sometimes|required|string|unique:role_personnel_bvs,code,' . $personnel->id
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $personnel->update($request->all());

        return $this->sendResponse(
            $personnel->load('roleUtilisateur.personne'),
            'Personnel mis à jour avec succès.'
        );
    }

    public function destroy(RolePersonnelBV $personnel)
    {
        try {
            $personnel->delete();
            return $this->sendResponse(null, 'Personnel supprimé avec succès.');
        } catch (\Exception $e) {
            return $this->sendError(
                'Erreur lors de la suppression',
                ['message' => 'Le personnel ne peut pas être supprimé car il est lié à des affectations actives.'],
                409
            );
        }
    }

    // Méthodes spécifiques au personnel des bureaux de vote

    public function affectations(RolePersonnelBV $personnel)
    {
        $affectations = $personnel->affectations()
            ->with(['bureauDeVote', 'election'])
            ->orderBy('date_debut', 'desc')
            ->get();

        return $this->sendResponse($affectations, 'Affectations du personnel récupérées avec succès.');
    }

    public function affectationsActives(RolePersonnelBV $personnel)
    {
        $affectationsActives = $personnel->affectations()
            ->where('statut', true)
            ->where('date_fin', '>=', now())
            ->with(['bureauDeVote', 'election'])
            ->get();

        return $this->sendResponse($affectationsActives, 'Affectations actives récupérées avec succès.');
    }

    public function enregistrerPresence(Request $request, RolePersonnelBV $personnel)
    {
        $validator = Validator::make($request->all(), [
            'affectation_id' => 'required|exists:affectations,id',
            'date_presence' => 'required|date',
            'heure_arrivee' => 'required|date_format:H:i',
            'heure_depart' => 'nullable|date_format:H:i|after:heure_arrivee'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Logique d'enregistrement de présence à implémenter

        return $this->sendResponse(null, 'Présence enregistrée avec succès.');
    }

    public function rapportActivite(Request $request, RolePersonnelBV $personnel)
    {
        $validator = Validator::make($request->all(), [
            'affectation_id' => 'required|exists:affectations,id',
            'description' => 'required|string',
            'type_activite' => 'required|string',
            'piece_jointe' => 'nullable|file|max:2048'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Logique d'enregistrement du rapport d'activité à implémenter

        return $this->sendResponse(null, 'Rapport d\'activité enregistré avec succès.');
    }
}

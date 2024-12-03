<?php

namespace App\Http\Controllers\API;

use App\Models\RoleSuperviseurCENA;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RoleSuperviseurCENAController extends BaseController
{
    public function index()
    {
        $superviseurs = RoleSuperviseurCENA::with(['roleUtilisateur.personne'])->get();
        return $this->sendResponse($superviseurs, 'Liste des superviseurs CENA récupérée avec succès.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_utilisateur_id' => 'required|exists:role_utilisateurs,id|unique:role_superviseur_cenas',
            'code' => 'sometimes|string|unique:role_superviseur_cenas'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $data = $request->all();
        // Génération automatique du code s'il n'est pas fourni
        if (!isset($data['code'])) {
            $data['code'] = 'SUP-' . Str::upper(Str::random(8));
        }

        $superviseur = RoleSuperviseurCENA::create($data);
        return $this->sendResponse(
            $superviseur->load('roleUtilisateur.personne'),
            'Superviseur CENA créé avec succès.',
            201
        );
    }

    public function show(RoleSuperviseurCENA $superviseur)
    {
        $superviseur->load(['roleUtilisateur.personne']);
        return $this->sendResponse($superviseur, 'Détails du superviseur CENA récupérés avec succès.');
    }

    public function update(Request $request, RoleSuperviseurCENA $superviseur)
    {
        $validator = Validator::make($request->all(), [
            'role_utilisateur_id' => 'sometimes|required|exists:role_utilisateurs,id|unique:role_superviseur_cenas,role_utilisateur_id,' . $superviseur->id,
            'code' => 'sometimes|required|string|unique:role_superviseur_cenas,code,' . $superviseur->id
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $superviseur->update($request->all());
        return $this->sendResponse(
            $superviseur->load('roleUtilisateur.personne'),
            'Superviseur CENA mis à jour avec succès.'
        );
    }

    public function destroy(RoleSuperviseurCENA $superviseur)
    {
        try {
            $superviseur->delete();
            return $this->sendResponse(null, 'Superviseur CENA supprimé avec succès.');
        } catch (\Exception $e) {
            return $this->sendError(
                'Erreur lors de la suppression',
                ['message' => 'Le superviseur CENA ne peut pas être supprimé car il est lié à des opérations.'],
                409
            );
        }
    }

    // Méthodes additionnelles spécifiques aux superviseurs CENA

    public function validateResults(Request $request, RoleSuperviseurCENA $superviseur)
    {
        $validator = Validator::make($request->all(), [
            'resultat_bureau_vote_id' => 'required|exists:resultats_bureau_vote,id',
            'validation' => 'required|boolean',
            'commentaire' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Logique de validation des résultats
        // À implémenter selon les besoins spécifiques

        return $this->sendResponse(null, 'Validation des résultats effectuée avec succès.');
    }

    public function listValidations(RoleSuperviseurCENA $superviseur)
    {
        // Récupération de l'historique des validations
        // À implémenter selon les besoins spécifiques

        return $this->sendResponse([], 'Historique des validations récupéré avec succès.');
    }
}

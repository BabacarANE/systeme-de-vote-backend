<?php

namespace App\Http\Controllers\API;

use App\Models\RoleAdminDGE;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RoleAdminDGEController extends BaseController
{
    public function index()
    {
        $admins = RoleAdminDGE::with(['roleUtilisateur.personne'])->get();
        return $this->sendResponse($admins, 'Liste des administrateurs DGE récupérée avec succès.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_utilisateur_id' => 'required|exists:role_utilisateurs,id|unique:role_admin_dges',
            'code' => 'sometimes|string|unique:role_admin_dges',
            'niveau_acces' => 'required|in:SUPER_ADMIN,ADMIN,MANAGER,CONSULTANT'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $data = $request->all();
        if (!isset($data['code'])) {
            $data['code'] = 'DGE-' . Str::upper(Str::random(8));
        }

        $admin = RoleAdminDGE::create($data);

        return $this->sendResponse(
            $admin->load('roleUtilisateur.personne'),
            'Administrateur DGE créé avec succès.',
            201
        );
    }

    public function show(RoleAdminDGE $admin)
    {
        $admin->load(['roleUtilisateur.personne']);
        return $this->sendResponse($admin, 'Détails de l\'administrateur DGE récupérés avec succès.');
    }

    public function update(Request $request, RoleAdminDGE $admin)
    {
        $validator = Validator::make($request->all(), [
            'role_utilisateur_id' => 'sometimes|required|exists:role_utilisateurs,id|unique:role_admin_dges,role_utilisateur_id,' . $admin->id,
            'code' => 'sometimes|required|string|unique:role_admin_dges,code,' . $admin->id,
            'niveau_acces' => 'sometimes|required|in:SUPER_ADMIN,ADMIN,MANAGER,CONSULTANT'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $admin->update($request->all());

        return $this->sendResponse(
            $admin->load('roleUtilisateur.personne'),
            'Administrateur DGE mis à jour avec succès.'
        );
    }

    public function destroy(RoleAdminDGE $admin)
    {
        try {
            $admin->delete();
            return $this->sendResponse(null, 'Administrateur DGE supprimé avec succès.');
        } catch (\Exception $e) {
            return $this->sendError(
                'Erreur lors de la suppression',
                ['message' => 'L\'administrateur DGE ne peut pas être supprimé car il est lié à des opérations.'],
                409
            );
        }
    }

    // Méthodes spécifiques aux administrateurs DGE

    public function gererElection(Request $request, RoleAdminDGE $admin)
    {
        if ($admin->niveau_acces !== 'SUPER_ADMIN' && $admin->niveau_acces !== 'ADMIN') {
            return $this->sendError('Accès refusé', ['message' => 'Niveau d\'accès insuffisant.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'action' => 'required|in:CREER,MODIFIER,CLOTURER,PUBLIER',
            'election_id' => 'required_unless:action,CREER|exists:elections,id',
            'donnees' => 'required|array'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Logique de gestion des élections selon l'action
        // À implémenter selon les besoins spécifiques

        return $this->sendResponse(null, 'Action sur l\'élection effectuée avec succès.');
    }

    public function gererCandidatures(Request $request, RoleAdminDGE $admin)
    {
        if ($admin->niveau_acces !== 'SUPER_ADMIN' && $admin->niveau_acces !== 'ADMIN') {
            return $this->sendError('Accès refusé', ['message' => 'Niveau d\'accès insuffisant.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'action' => 'required|in:VALIDER,REJETER',
            'candidature_id' => 'required|exists:candidatures,id',
            'motif' => 'required_if:action,REJETER|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Logique de gestion des candidatures
        // À implémenter selon les besoins spécifiques

        return $this->sendResponse(null, 'Action sur la candidature effectuée avec succès.');
    }

    public function rapportGlobal(RoleAdminDGE $admin)
    {
        // Génération de statistiques globales
        $stats = [
            'elections' => [
                'total' => 0,
                'en_cours' => 0,
                'terminees' => 0
            ],
            'candidatures' => [
                'total' => 0,
                'validees' => 0,
                'rejetees' => 0,
                'en_attente' => 0
            ],
            'bureaux_vote' => [
                'total' => 0,
                'actifs' => 0
            ],
            'electeurs' => [
                'total' => 0,
                'ont_vote' => 0
            ]
        ];

        return $this->sendResponse($stats, 'Rapport global généré avec succès.');
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Models\RoleUtilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RoleUtilisateurController extends BaseController
{
    public function index()
    {
        $utilisateurs = RoleUtilisateur::with([
            'personne',
            'superviseurCENA',
            'personnelBV',
            'representant',
            'adminDGE'
        ])->get();

        return $this->sendResponse($utilisateurs, 'Liste des utilisateurs récupérée avec succès.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'personne_id' => 'required|exists:personnes,id',
            'email' => 'required|email|unique:role_utilisateurs',
            'mot_de_passe' => 'required|min:8',
            'est_actif' => 'boolean'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $data = $request->all();
        $data['mot_de_passe'] = Hash::make($request->mot_de_passe);

        $utilisateur = RoleUtilisateur::create($data);

        return $this->sendResponse($utilisateur, 'Utilisateur créé avec succès.', 201);
    }

    public function show(RoleUtilisateur $utilisateur)
    {
        $utilisateur->load([
            'personne',
            'superviseurCENA',
            'personnelBV',
            'representant',
            'adminDGE',
            'journalUtilisateur'
        ]);

        return $this->sendResponse($utilisateur, 'Détails de l\'utilisateur récupérés avec succès.');
    }

    public function update(Request $request, RoleUtilisateur $utilisateur)
    {
        $validator = Validator::make($request->all(), [
            'personne_id' => 'sometimes|required|exists:personnes,id',
            'email' => 'sometimes|required|email|unique:role_utilisateurs,email,' . $utilisateur->id,
            'mot_de_passe' => 'sometimes|required|min:8',
            'est_actif' => 'boolean'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $data = $request->all();
        if ($request->has('mot_de_passe')) {
            $data['mot_de_passe'] = Hash::make($request->mot_de_passe);
        }

        $utilisateur->update($data);

        return $this->sendResponse($utilisateur, 'Utilisateur mis à jour avec succès.');
    }

    public function destroy(RoleUtilisateur $utilisateur)
    {
        try {
            $utilisateur->delete();
            return $this->sendResponse(null, 'Utilisateur supprimé avec succès.');
        } catch (\Exception $e) {
            return $this->sendError('Erreur lors de la suppression',
                ['message' => 'L\'utilisateur ne peut pas être supprimé car il est lié à d\'autres entités.'], 409);
        }
    }

    // Méthodes supplémentaires
    public function toggleStatus(RoleUtilisateur $utilisateur)
    {
        $utilisateur->est_actif = !$utilisateur->est_actif;
        $utilisateur->save();

        return $this->sendResponse($utilisateur,
            'Statut de l\'utilisateur modifié avec succès. Nouveau statut : ' .
            ($utilisateur->est_actif ? 'Actif' : 'Inactif'));
    }

    public function journalActivites(RoleUtilisateur $utilisateur)
    {
        $journal = $utilisateur->journalUtilisateur()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return $this->sendResponse($journal, 'Journal des activités récupéré avec succès.');
    }

    public function changePassword(Request $request, RoleUtilisateur $utilisateur)
    {
        $validator = Validator::make($request->all(), [
            'ancien_mot_de_passe' => 'required',
            'nouveau_mot_de_passe' => 'required|min:8|different:ancien_mot_de_passe',
            'confirmation_mot_de_passe' => 'required|same:nouveau_mot_de_passe'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        if (!Hash::check($request->ancien_mot_de_passe, $utilisateur->mot_de_passe)) {
            return $this->sendError('Erreur', ['ancien_mot_de_passe' => ['Le mot de passe actuel est incorrect']], 422);
        }

        $utilisateur->update([
            'mot_de_passe' => Hash::make($request->nouveau_mot_de_passe)
        ]);

        return $this->sendResponse(null, 'Mot de passe modifié avec succès.');
    }
}

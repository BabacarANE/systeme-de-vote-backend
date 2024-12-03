<?php

namespace App\Http\Controllers\API;

use App\Models\Personne;
use App\Models\RoleAdminDGE;
use App\Models\RoleCandidat;
use App\Models\RoleElecteur;
use App\Models\RolePersonnelBV;
use App\Models\RoleRepresentant;
use App\Models\RoleSuperviseurCENA;
use App\Models\RoleUtilisateur;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
class CombinedRegistrationController extends BaseController
{
    public function createElecteur(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'date_naissance' => 'required|date',
            'sexe' => 'required|in:M,F',
            'adresse' => 'required|string',
            'numero_electeur' => 'required|string|unique:role_electeurs'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            $personne = Personne::create([
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'date_naissance' => $request->date_naissance,
                'sexe' => $request->sexe,
                'adresse' => $request->adresse
            ]);

            $electeur = RoleElecteur::create([
                'personne_id' => $personne->id,
                'numero_electeur' => $request->numero_electeur,
                'a_voter' => false
            ]);

            DB::commit();
            return $this->sendResponse(
                ['personne' => $personne, 'electeur' => $electeur],
                'Électeur créé avec succès',
                201
            );
        } catch (\Exception $e) {
            DB::rollback();
            return $this->sendError('Erreur lors de la création', ['message' => $e->getMessage()]);
        }
    }

    public function createCandidat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'date_naissance' => 'required|date',
            'sexe' => 'required|in:M,F',
            'adresse' => 'required|string',
            'parti' => 'required|string',
            'profession' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            $personne = Personne::create([
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'date_naissance' => $request->date_naissance,
                'sexe' => $request->sexe,
                'adresse' => $request->adresse
            ]);

            $candidat = RoleCandidat::create([
                'personne_id' => $personne->id,
                'parti' => $request->parti,
                'code' => 'CAND-' . uniqid(),
                'profession' => $request->profession
            ]);

            DB::commit();
            return $this->sendResponse(
                ['personne' => $personne, 'candidat' => $candidat],
                'Candidat créé avec succès',
                201
            );
        } catch (\Exception $e) {
            DB::rollback();
            return $this->sendError('Erreur lors de la création', ['message' => $e->getMessage()]);
        }
    }

    public function createSuperviseurCENA(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'date_naissance' => 'required|date',
            'sexe' => 'required|in:M,F',
            'adresse' => 'required|string',
            'email' => 'required|email|unique:role_utilisateurs',
            'mot_de_passe' => 'required|min:8'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            $personne = Personne::create([
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'date_naissance' => $request->date_naissance,
                'sexe' => $request->sexe,
                'adresse' => $request->adresse
            ]);

            $utilisateur = RoleUtilisateur::create([
                'personne_id' => $personne->id,
                'email' => $request->email,
                'mot_de_passe' => Hash::make($request->mot_de_passe),
                'est_actif' => true
            ]);

            $superviseur = RoleSuperviseurCENA::create([
                'role_utilisateur_id' => $utilisateur->id,
                'code' => 'SUP-' . uniqid()
            ]);

            DB::commit();
            return $this->sendResponse(
                ['personne' => $personne, 'utilisateur' => $utilisateur, 'superviseur' => $superviseur],
                'Superviseur CENA créé avec succès',
                201
            );
        } catch (\Exception $e) {
            DB::rollback();
            return $this->sendError('Erreur lors de la création', ['message' => $e->getMessage()]);
        }
    }

    public function createPersonnelBV(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'date_naissance' => 'required|date',
            'sexe' => 'required|in:M,F',
            'adresse' => 'required|string',
            'email' => 'required|email|unique:role_utilisateurs',
            'mot_de_passe' => 'required|min:8'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            $personne = Personne::create([
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'date_naissance' => $request->date_naissance,
                'sexe' => $request->sexe,
                'adresse' => $request->adresse
            ]);

            $utilisateur = RoleUtilisateur::create([
                'personne_id' => $personne->id,
                'email' => $request->email,
                'mot_de_passe' => Hash::make($request->mot_de_passe),
                'est_actif' => true
            ]);

            $personnel = RolePersonnelBV::create([
                'role_utilisateur_id' => $utilisateur->id,
                'code' => 'PERS-' . uniqid()
            ]);

            DB::commit();
            return $this->sendResponse(
                ['personne' => $personne, 'utilisateur' => $utilisateur, 'personnel' => $personnel],
                'Personnel BV créé avec succès',
                201
            );
        } catch (\Exception $e) {
            DB::rollback();
            return $this->sendError('Erreur lors de la création', ['message' => $e->getMessage()]);
        }
    }

    public function createRepresentant(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'date_naissance' => 'required|date',
            'sexe' => 'required|in:M,F',
            'adresse' => 'required|string',
            'email' => 'required|email|unique:role_utilisateurs',
            'mot_de_passe' => 'required|min:8'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            $personne = Personne::create([
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'date_naissance' => $request->date_naissance,
                'sexe' => $request->sexe,
                'adresse' => $request->adresse
            ]);

            $utilisateur = RoleUtilisateur::create([
                'personne_id' => $personne->id,
                'email' => $request->email,
                'mot_de_passe' => Hash::make($request->mot_de_passe),
                'est_actif' => true
            ]);

            $representant = RoleRepresentant::create([
                'role_utilisateur_id' => $utilisateur->id,
                'code' => 'REP-' . uniqid()
            ]);

            DB::commit();
            return $this->sendResponse(
                ['personne' => $personne, 'utilisateur' => $utilisateur, 'representant' => $representant],
                'Représentant créé avec succès',
                201
            );
        } catch (\Exception $e) {
            DB::rollback();
            return $this->sendError('Erreur lors de la création', ['message' => $e->getMessage()]);
        }
    }

    public function createAdminDGE(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'date_naissance' => 'required|date',
            'sexe' => 'required|in:M,F',
            'adresse' => 'required|string',
            'email' => 'required|email|unique:role_utilisateurs',
            'mot_de_passe' => 'required|min:8',
            'niveau_acces' => 'required|in:SUPER_ADMIN,ADMIN,MANAGER,CONSULTANT'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            $personne = Personne::create([
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'date_naissance' => $request->date_naissance,
                'sexe' => $request->sexe,
                'adresse' => $request->adresse
            ]);

            $utilisateur = RoleUtilisateur::create([
                'personne_id' => $personne->id,
                'email' => $request->email,
                'mot_de_passe' => Hash::make($request->mot_de_passe),
                'est_actif' => true
            ]);

            $admin = RoleAdminDGE::create([
                'role_utilisateur_id' => $utilisateur->id,
                'code' => 'DGE-' . uniqid(),
                'niveau_acces' => $request->niveau_acces
            ]);

            DB::commit();
            return $this->sendResponse(
                ['personne' => $personne, 'utilisateur' => $utilisateur, 'admin' => $admin],
                'Admin DGE créé avec succès',
                201
            );
        } catch (\Exception $e) {
            DB::rollback();
            return $this->sendError('Erreur lors de la création', ['message' => $e->getMessage()]);
        }
    }
}

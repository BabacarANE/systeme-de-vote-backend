<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Personne;
use App\Models\RoleUtilisateur;
use App\Models\RoleAdminDGE;
use Illuminate\Support\Facades\Hash;

class AdminDGESeeder extends Seeder
{
    public function run()
    {
        // Création de la personne
        $personne = Personne::create([
            'nom' => 'Admin',
            'prenom' => 'DGE',
            'date_naissance' => '1990-01-01',
            'sexe' => 'M',
            'adresse' => 'DGE Dakar'
        ]);

        // Création du rôle utilisateur
        $roleUtilisateur = RoleUtilisateur::create([
            'personne_id' => $personne->id,
            'email' => 'admin@dge.sn',
            'mot_de_passe' => Hash::make('password123'),
            'est_actif' => true
        ]);

        // Création du rôle admin DGE
        RoleAdminDGE::create([
            'role_utilisateur_id' => $roleUtilisateur->id,
            'code' => 'ADMIN-' . uniqid(),
            'niveau_acces' => 'SUPER_ADMIN'
        ]);
    }
}

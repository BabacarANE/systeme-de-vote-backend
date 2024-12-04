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
            'a_voter' => 'boolean',
             'liste_electorale_id' => 'sometimes|required|exists:listes_electorales,id'
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
            'a_voter' => 'boolean',
            'liste_electorale_id' => 'sometimes|required|exists:liste_electorales,id'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Vérifier si l'électeur est déjà inscrit sur une autre liste
        if ($request->has('liste_electorale_id')) {
            $existingInscription = RoleElecteur::where('id', '!=', $electeur->id)
                ->where('liste_electorale_id', $request->liste_electorale_id)
                ->exists();

            if ($existingInscription) {
                return $this->sendError(
                    'Erreur de validation',
                    ['message' => 'Cet électeur est déjà inscrit sur une autre liste électorale.'],
                    422
                );
            }
        }

        $electeur->update($request->all());

        // Recharger l'électeur avec ses relations
        $electeur->load(['personne', 'listeElectorale']);

        return $this->sendResponse($electeur, 'Électeur mis à jour avec succès.');
    }

    public function verifierElecteur($numeroElecteur)
    {
        $electeur = RoleElecteur::where('numero_electeur', $numeroElecteur)
            ->with(['personne', 'listeElectorale.bureauDeVote.centreDeVote.commune'])
            ->first();

        if (!$electeur) {
            return $this->sendError(
                'Électeur non trouvé',
                ['message' => 'Aucun électeur ne correspond à ce numéro.'],
                404
            );
        }

        // Préparer les informations pertinentes
        $data = [
            'electeur' => [
                'numero' => $electeur->numero_electeur,
                'nom' => $electeur->personne->nom,
                'prenom' => $electeur->personne->prenom,
                'date_naissance' => $electeur->personne->date_naissance,
            ],
            'statut_vote' => $electeur->a_voter,
            'lieu_vote' => null
        ];

        // Ajouter les informations sur le lieu de vote si disponibles
        if ($electeur->listeElectorale) {
            $data['lieu_vote'] = [
                'bureau_vote' => [
                    'nom' => $electeur->listeElectorale->bureauDeVote->nom,
                    'numero' => $electeur->listeElectorale->bureauDeVote->id
                ],
                'centre_vote' => [
                    'nom' => $electeur->listeElectorale->bureauDeVote->centreDeVote->nom,
                    'adresse' => $electeur->listeElectorale->bureauDeVote->centreDeVote->adresse
                ],
                'commune' => [
                    'nom' => $electeur->listeElectorale->bureauDeVote->centreDeVote->commune->nom
                ]
            ];
        }

        return $this->sendResponse($data, 'Informations de l\'électeur récupérées avec succès.');
    }
}

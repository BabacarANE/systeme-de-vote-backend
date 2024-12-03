<?php

namespace App\Http\Controllers\API;

use App\Models\ListeElectorale;
use App\Models\RoleElecteur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ListeElectoraleController extends BaseController
{
    public function index()
    {
        $listes = ListeElectorale::with([
            'bureauDeVote.centreDeVote',
            'electeurs.personne'
        ])->get();

        return $this->sendResponse($listes, 'Liste des listes électorales récupérée avec succès.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bureau_de_vote_id' => 'required|exists:bureaux_de_vote,id',
            'code' => 'sometimes|string|unique:listes_electorales',
            'date_creation' => 'required|date',
            'electeurs' => 'sometimes|array',
            'electeurs.*' => 'exists:role_electeurs,id'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Vérifier si le bureau a déjà une liste électorale
        $listeExistante = ListeElectorale::where('bureau_de_vote_id', $request->bureau_de_vote_id)->exists();
        if ($listeExistante) {
            return $this->sendError(
                'Erreur de validation',
                ['message' => 'Ce bureau de vote possède déjà une liste électorale.'],
                422
            );
        }

        $data = $request->all();
        if (!isset($data['code'])) {
            $data['code'] = 'LISTE-' . Str::upper(Str::random(8));
        }

        $liste = ListeElectorale::create($data);

        // Associer les électeurs si fournis
        if ($request->has('electeurs')) {
            $liste->electeurs()->attach($request->electeurs);
        }

        return $this->sendResponse(
            $liste->load(['bureauDeVote', 'electeurs.personne']),
            'Liste électorale créée avec succès.',
            201
        );
    }

    public function show(ListeElectorale $listeElectorale)
    {
        $listeElectorale->load([
            'bureauDeVote.centreDeVote',
            'electeurs.personne'
        ]);
        return $this->sendResponse($listeElectorale, 'Détails de la liste électorale récupérés avec succès.');
    }

    public function update(Request $request, ListeElectorale $listeElectorale)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'sometimes|string|unique:listes_electorales,code,' . $listeElectorale->id,
            'date_creation' => 'sometimes|date'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $listeElectorale->update($request->all());
        return $this->sendResponse(
            $listeElectorale->load(['bureauDeVote', 'electeurs.personne']),
            'Liste électorale mise à jour avec succès.'
        );
    }

    public function destroy(ListeElectorale $listeElectorale)
    {
        try {
            $listeElectorale->delete();
            return $this->sendResponse(null, 'Liste électorale supprimée avec succès.');
        } catch (\Exception $e) {
            return $this->sendError(
                'Erreur lors de la suppression',
                ['message' => 'La liste électorale ne peut pas être supprimée car elle contient des électeurs.'],
                409
            );
        }
    }

    // Méthodes additionnelles pour la gestion des électeurs
    public function ajouterElecteurs(Request $request, ListeElectorale $listeElectorale)
    {
        $validator = Validator::make($request->all(), [
            'electeurs' => 'required|array',
            'electeurs.*' => 'exists:role_electeurs,id'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Vérifier que les électeurs ne sont pas déjà dans une autre liste
        $electeursDejaInscrits = RoleElecteur::whereIn('id', $request->electeurs)
            ->whereNotNull('liste_electorale_id')
            ->where('liste_electorale_id', '!=', $listeElectorale->id)
            ->exists();

        if ($electeursDejaInscrits) {
            return $this->sendError(
                'Erreur de validation',
                ['message' => 'Certains électeurs sont déjà inscrits dans une autre liste électorale.'],
                422
            );
        }

        $listeElectorale->electeurs()->attach($request->electeurs);

        return $this->sendResponse(
            $listeElectorale->load('electeurs.personne'),
            'Électeurs ajoutés avec succès.'
        );
    }

    public function retirerElecteurs(Request $request, ListeElectorale $listeElectorale)
    {
        $validator = Validator::make($request->all(), [
            'electeurs' => 'required|array',
            'electeurs.*' => 'exists:role_electeurs,id'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Vérifier si des électeurs ont déjà voté
        $electeursAyantVote = RoleElecteur::whereIn('id', $request->electeurs)
            ->where('a_voter', true)
            ->exists();

        if ($electeursAyantVote) {
            return $this->sendError(
                'Erreur',
                ['message' => 'Impossible de retirer des électeurs ayant déjà voté.'],
                422
            );
        }

        $listeElectorale->electeurs()->detach($request->electeurs);

        return $this->sendResponse(null, 'Électeurs retirés avec succès.');
    }

    public function statistiques(ListeElectorale $listeElectorale)
    {
        $stats = [
            'total_electeurs' => $listeElectorale->electeurs()->count(),
            'ont_vote' => $listeElectorale->electeurs()->where('a_voter', true)->count(),
            'n_ont_pas_vote' => $listeElectorale->electeurs()->where('a_voter', false)->count(),
            'repartition_par_sexe' => [
                'hommes' => $listeElectorale->electeurs()
                    ->whereHas('personne', function($query) {
                        $query->where('sexe', 'M');
                    })->count(),
                'femmes' => $listeElectorale->electeurs()
                    ->whereHas('personne', function($query) {
                        $query->where('sexe', 'F');
                    })->count()
            ]
        ];

        return $this->sendResponse($stats, 'Statistiques de la liste électorale récupérées avec succès.');
    }
}

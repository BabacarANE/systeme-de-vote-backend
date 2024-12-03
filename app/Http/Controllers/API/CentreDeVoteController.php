<?php

namespace App\Http\Controllers\API;

use App\Models\CentreDeVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CentreDeVoteController extends BaseController
{
    public function index()
    {
        $centres = CentreDeVote::with([
            'commune.departement.region',
            'bureauxDeVote'
        ])->get();
        return $this->sendResponse($centres, 'Liste des centres de vote récupérée avec succès.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'commune_id' => 'required|exists:communes,id',
            'nom' => 'required|string|max:100',
            'adresse' => 'required|string',
            'nombre_de_bureau' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Vérifier l'unicité du nom dans la même commune
        $exists = CentreDeVote::where('commune_id', $request->commune_id)
            ->where('nom', $request->nom)
            ->exists();

        if ($exists) {
            return $this->sendError(
                'Erreur de validation',
                ['nom' => ['Un centre de vote avec ce nom existe déjà dans cette commune.']],
                422
            );
        }

        $centre = CentreDeVote::create($request->all());
        return $this->sendResponse(
            $centre->load('commune.departement.region'),
            'Centre de vote créé avec succès.',
            201
        );
    }

    public function show(CentreDeVote $centreDeVote)
    {
        $centreDeVote->load([
            'commune.departement.region',
            'bureauxDeVote.listeElectorale',
            'bureauxDeVote.resultats'
        ]);
        return $this->sendResponse($centreDeVote, 'Détails du centre de vote récupérés avec succès.');
    }

    public function update(Request $request, CentreDeVote $centreDeVote)
    {
        $validator = Validator::make($request->all(), [
            'commune_id' => 'sometimes|required|exists:communes,id',
            'nom' => 'sometimes|required|string|max:100',
            'adresse' => 'sometimes|required|string',
            'nombre_de_bureau' => 'sometimes|required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Vérifier l'unicité du nom dans la même commune si le nom est modifié
        if ($request->has('nom') && $request->nom !== $centreDeVote->nom) {
            $exists = CentreDeVote::where('commune_id', $request->commune_id ?? $centreDeVote->commune_id)
                ->where('nom', $request->nom)
                ->where('id', '!=', $centreDeVote->id)
                ->exists();

            if ($exists) {
                return $this->sendError(
                    'Erreur de validation',
                    ['nom' => ['Un centre de vote avec ce nom existe déjà dans cette commune.']],
                    422
                );
            }
        }

        // Vérifier si le nombre de bureaux peut être modifié
        if ($request->has('nombre_de_bureau') && $request->nombre_de_bureau < $centreDeVote->bureauxDeVote()->count()) {
            return $this->sendError(
                'Erreur de validation',
                ['nombre_de_bureau' => ['Le nombre de bureaux ne peut pas être inférieur au nombre de bureaux existants.']],
                422
            );
        }

        $centreDeVote->update($request->all());
        return $this->sendResponse(
            $centreDeVote->load('commune.departement.region'),
            'Centre de vote mis à jour avec succès.'
        );
    }

    public function destroy(CentreDeVote $centreDeVote)
    {
        try {
            $centreDeVote->delete();
            return $this->sendResponse(null, 'Centre de vote supprimé avec succès.');
        } catch (\Exception $e) {
            return $this->sendError(
                'Erreur lors de la suppression',
                ['message' => 'Le centre de vote ne peut pas être supprimé car il contient des bureaux de vote.'],
                409
            );
        }
    }

    // Méthodes additionnelles
    public function bureauxDeVote(CentreDeVote $centreDeVote)
    {
        return $this->sendResponse(
            $centreDeVote->bureauxDeVote()->with(['listeElectorale', 'resultats'])->get(),
            'Bureaux de vote récupérés avec succès.'
        );
    }

    public function statistiques(CentreDeVote $centreDeVote)
    {
        $stats = [
            'nombre_bureaux' => $centreDeVote->bureauxDeVote()->count(),
            'capacite_totale' => $centreDeVote->nombre_de_bureau,
            'bureaux_disponibles' => $centreDeVote->nombre_de_bureau - $centreDeVote->bureauxDeVote()->count(),
            'electeurs' => [
                'inscrits' => $centreDeVote->bureauxDeVote()->sum('nombre_inscrits'),
                'ayant_vote' => $centreDeVote->bureauxDeVote()
                    ->withSum('resultats', 'nombre_votants')
                    ->get()
                    ->sum('resultats_sum_nombre_votants')
            ]
        ];

        return $this->sendResponse($stats, 'Statistiques du centre de vote récupérées avec succès.');
    }
}

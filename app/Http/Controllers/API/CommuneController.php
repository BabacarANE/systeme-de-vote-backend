<?php

namespace App\Http\Controllers\API;

use App\Models\Commune;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommuneController extends BaseController
{
    public function index()
    {
        $communes = Commune::with([
            'departement.region.pays',
            'centresDeVote'
        ])->get();
        return $this->sendResponse($communes, 'Liste des communes récupérée avec succès.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'departement_id' => 'required|exists:departements,id',
            'nom' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:communes'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Vérifier l'unicité du nom dans le même département
        $exists = Commune::where('departement_id', $request->departement_id)
            ->where('nom', $request->nom)
            ->exists();

        if ($exists) {
            return $this->sendError(
                'Erreur de validation',
                ['nom' => ['Une commune avec ce nom existe déjà dans ce département.']],
                422
            );
        }

        $commune = Commune::create($request->all());
        return $this->sendResponse(
            $commune->load('departement.region.pays'),
            'Commune créée avec succès.',
            201
        );
    }

    public function show(Commune $commune)
    {
        $commune->load([
            'departement.region.pays',
            'centresDeVote.bureauxDeVote'
        ]);
        return $this->sendResponse($commune, 'Détails de la commune récupérés avec succès.');
    }

    public function update(Request $request, Commune $commune)
    {
        $validator = Validator::make($request->all(), [
            'departement_id' => 'sometimes|required|exists:departements,id',
            'nom' => 'sometimes|required|string|max:100',
            'code' => 'sometimes|required|string|max:50|unique:communes,code,' . $commune->id
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Vérifier l'unicité du nom dans le même département si le nom est modifié
        if ($request->has('nom') && $request->nom !== $commune->nom) {
            $exists = Commune::where('departement_id', $request->departement_id ?? $commune->departement_id)
                ->where('nom', $request->nom)
                ->where('id', '!=', $commune->id)
                ->exists();

            if ($exists) {
                return $this->sendError(
                    'Erreur de validation',
                    ['nom' => ['Une commune avec ce nom existe déjà dans ce département.']],
                    422
                );
            }
        }

        $commune->update($request->all());
        return $this->sendResponse(
            $commune->load('departement.region.pays'),
            'Commune mise à jour avec succès.'
        );
    }

    public function destroy(Commune $commune)
    {
        try {
            $commune->delete();
            return $this->sendResponse(null, 'Commune supprimée avec succès.');
        } catch (\Exception $e) {
            return $this->sendError(
                'Erreur lors de la suppression',
                ['message' => 'La commune ne peut pas être supprimée car elle est liée à des centres de vote.'],
                409
            );
        }
    }

    // Méthodes additionnelles
    public function centresDeVote(Commune $commune)
    {
        return $this->sendResponse(
            $commune->centresDeVote()->with('bureauxDeVote')->get(),
            'Centres de vote de la commune récupérés avec succès.'
        );
    }

    public function statistiquesElectorales(Commune $commune)
    {
        $stats = [
            'nombre_centres_vote' => $commune->centresDeVote()->count(),
            'nombre_bureaux_vote' => $commune->centresDeVote()
                ->sum('nombre_de_bureau'),
            'nombre_electeurs_inscrits' => $commune->centresDeVote()
                ->withSum('bureauxDeVote', 'nombre_inscrits')
                ->get()
                ->sum('bureaux_de_vote_sum_nombre_inscrits'),
            'participation' => [
                'votants' => $commune->centresDeVote()
                    ->withSum('bureauxDeVote.resultats', 'nombre_votants')
                    ->get()
                    ->sum('resultats_sum_nombre_votants'),
                'bulletins_nuls' => $commune->centresDeVote()
                    ->withSum('bureauxDeVote.resultats', 'bulletins_nuls')
                    ->get()
                    ->sum('resultats_sum_bulletins_nuls'),
                'bulletins_blancs' => $commune->centresDeVote()
                    ->withSum('bureauxDeVote.resultats', 'bulletins_blancs')
                    ->get()
                    ->sum('resultats_sum_bulletins_blancs')
            ]
        ];

        $stats['taux_participation'] = $stats['nombre_electeurs_inscrits'] > 0
            ? round(($stats['participation']['votants'] / $stats['nombre_electeurs_inscrits']) * 100, 2)
            : 0;

        return $this->sendResponse($stats, 'Statistiques électorales de la commune récupérées avec succès.');
    }

    public function resultatsElectoraux(Commune $commune, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'election_id' => 'required|exists:elections,id'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Récupérer les résultats pour une élection spécifique
        $resultats = $commune->centresDeVote()
            ->with(['bureauxDeVote.resultats' => function($query) use ($request) {
                $query->where('election_id', $request->election_id);
            }])
            ->get();

        return $this->sendResponse($resultats, 'Résultats électoraux de la commune récupérés avec succès.');
    }
}

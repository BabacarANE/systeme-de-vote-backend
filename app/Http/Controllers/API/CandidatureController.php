<?php

namespace App\Http\Controllers\API;

use App\Models\Candidature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class CandidatureController extends BaseController
{
    public function index()
    {
        $candidatures = Candidature::with([
            'election',
            'roleCandidat.personne',
            'voteCandidats'
        ])->get();

        return $this->sendResponse($candidatures, 'Liste des candidatures récupérée avec succès.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'election_id' => 'required|exists:elections,id',
            'role_candidat_id' => 'required|exists:role_candidats,id',
            'statut' => 'required|in:EN_ATTENTE,VALIDEE,REJETEE',
            'date_inscription' => 'required|date',
            'bulletin' => 'nullable|file|mimes:pdf|max:10240' // 10MB max
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Vérifier si l'élection accepte encore les candidatures
        $election = \App\Models\Election::find($request->election_id);
        if ($election->statut !== 'PLANIFIEE') {
            return $this->sendError(
                'Opération non autorisée',
                ['message' => 'Les candidatures ne sont plus acceptées pour cette élection.'],
                403
            );
        }

        // Vérifier si le candidat n'est pas déjà inscrit à cette élection
        $candidatureExistante = Candidature::where('election_id', $request->election_id)
            ->where('role_candidat_id', $request->role_candidat_id)
            ->exists();

        if ($candidatureExistante) {
            return $this->sendError(
                'Erreur de validation',
                ['message' => 'Ce candidat est déjà inscrit à cette élection.'],
                422
            );
        }

        $data = $request->all();

        // Gérer le bulletin de candidature
        if ($request->hasFile('bulletin')) {
            $data['bulletin'] = $request->file('bulletin')
                ->store('bulletins_candidature', 'public');
        }

        $candidature = Candidature::create($data);

        return $this->sendResponse(
            $candidature->load(['election', 'roleCandidat.personne']),
            'Candidature enregistrée avec succès.',
            201
        );
    }

    public function show(Candidature $candidature)
    {
        $candidature->load([
            'election',
            'roleCandidat.personne',
            'voteCandidats.resultatBureauVote'
        ]);

        return $this->sendResponse($candidature, 'Détails de la candidature récupérés avec succès.');
    }

    public function update(Request $request, Candidature $candidature)
    {
        // Vérifier si la candidature peut être modifiée
        if ($candidature->election->statut !== 'PLANIFIEE') {
            return $this->sendError(
                'Opération non autorisée',
                ['message' => 'Les candidatures ne peuvent plus être modifiées pour cette élection.'],
                403
            );
        }

        $validator = Validator::make($request->all(), [
            'statut' => 'sometimes|required|in:EN_ATTENTE,VALIDEE,REJETEE',
            'bulletin' => 'nullable|file|mimes:pdf|max:10240'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $data = $request->all();

        // Gérer le nouveau bulletin si fourni
        if ($request->hasFile('bulletin')) {
            // Supprimer l'ancien bulletin
            if ($candidature->bulletin) {
                Storage::disk('public')->delete($candidature->bulletin);
            }
            $data['bulletin'] = $request->file('bulletin')
                ->store('bulletins_candidature', 'public');
        }

        $candidature->update($data);

        return $this->sendResponse(
            $candidature->load(['election', 'roleCandidat.personne']),
            'Candidature mise à jour avec succès.'
        );
    }

    public function destroy(Candidature $candidature)
    {
        if ($candidature->election->statut !== 'PLANIFIEE') {
            return $this->sendError(
                'Opération non autorisée',
                ['message' => 'Les candidatures ne peuvent plus être supprimées pour cette élection.'],
                403
            );
        }

        try {
            // Supprimer le bulletin si existe
            if ($candidature->bulletin) {
                Storage::disk('public')->delete($candidature->bulletin);
            }

            $candidature->delete();
            return $this->sendResponse(null, 'Candidature supprimée avec succès.');
        } catch (\Exception $e) {
            return $this->sendError(
                'Erreur lors de la suppression',
                ['message' => 'La candidature ne peut pas être supprimée car elle contient des données liées.'],
                409
            );
        }
    }

    // Méthodes additionnelles

    public function validerCandidature(Request $request, Candidature $candidature)
    {
        if ($candidature->statut !== 'EN_ATTENTE') {
            return $this->sendError(
                'Opération non autorisée',
                ['message' => 'Seules les candidatures en attente peuvent être validées.'],
                403
            );
        }

        $validator = Validator::make($request->all(), [
            'commentaire' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $candidature->update([
            'statut' => 'VALIDEE',
            'commentaire_validation' => $request->commentaire,
            'date_validation' => now()
        ]);

        return $this->sendResponse($candidature, 'Candidature validée avec succès.');
    }

    public function rejeterCandidature(Request $request, Candidature $candidature)
    {
        if ($candidature->statut !== 'EN_ATTENTE') {
            return $this->sendError(
                'Opération non autorisée',
                ['message' => 'Seules les candidatures en attente peuvent être rejetées.'],
                403
            );
        }

        $validator = Validator::make($request->all(), [
            'motif_rejet' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $candidature->update([
            'statut' => 'REJETEE',
            'commentaire_validation' => $request->motif_rejet,
            'date_validation' => now()
        ]);

        return $this->sendResponse($candidature, 'Candidature rejetée avec succès.');
    }

    public function getBulletin(Candidature $candidature)
    {
        if (!$candidature->bulletin) {
            return $this->sendError(
                'Non trouvé',
                ['message' => 'Aucun bulletin n\'est disponible pour cette candidature.'],
                404
            );
        }

        return Storage::disk('public')->download(
            $candidature->bulletin,
            'bulletin_candidature_' . $candidature->roleCandidat->personne->nom . '.pdf'
        );
    }

    public function getResultats(Candidature $candidature)
    {
        if ($candidature->election->statut !== 'TERMINEE') {
            return $this->sendError(
                'Opération non autorisée',
                ['message' => 'Les résultats ne sont pas encore disponibles.'],
                403
            );
        }

        $resultats = [
            'total_voix' => $candidature->voteCandidats()->sum('nombre_voix'),
            'repartition_geographique' => [],
            'pourcentage_global' => 0
        ];

        // Calcul de la répartition géographique
        $resultats['repartition_geographique'] = $candidature->voteCandidats()
            ->join('resultats_bureau_vote', 'vote_candidats.resultat_bureau_vote_id', '=', 'resultats_bureau_vote.id')
            ->join('bureaux_de_vote', 'resultats_bureau_vote.bureau_de_vote_id', '=', 'bureaux_de_vote.id')
            ->join('centres_de_vote', 'bureaux_de_vote.centre_de_vote_id', '=', 'centres_de_vote.id')
            ->join('communes', 'centres_de_vote.commune_id', '=', 'communes.id')
            ->join('departements', 'communes.departement_id', '=', 'departements.id')
            ->join('regions', 'departements.region_id', '=', 'regions.id')
            ->select('regions.nom as region', 'departements.nom as departement',
                \DB::raw('SUM(vote_candidats.nombre_voix) as total_voix'))
            ->groupBy('regions.nom', 'departements.nom')
            ->get();

        // Calcul du pourcentage global
        $totalSuffragesExprimes = \App\Models\ResultatBureauVote::where('validite', true)
            ->sum('suffrages_exprimes');

        if ($totalSuffragesExprimes > 0) {
            $resultats['pourcentage_global'] = round(
                ($resultats['total_voix'] / $totalSuffragesExprimes) * 100,
                2
            );
        }

        return $this->sendResponse($resultats, 'Résultats de la candidature récupérés avec succès.');
    }

    public function getContestations(Candidature $candidature)
    {
        $contestations = \App\Models\Contestation::where('role_candidat_id', $candidature->role_candidat_id)
            ->with(['resultatBureauVote', 'roleRepresentant.roleUtilisateur.personne'])
            ->orderBy('date_soumission', 'desc')
            ->get();

        return $this->sendResponse($contestations, 'Contestations de la candidature récupérées avec succès.');
    }
}

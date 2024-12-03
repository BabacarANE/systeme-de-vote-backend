<?php

namespace App\Http\Controllers\API;

use App\Models\Contestation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ContestationController extends BaseController
{
    public function index()
    {
        $contestations = Contestation::with([
            'resultatBureauVote.bureauDeVote',
            'roleRepresentant.roleUtilisateur.personne',
            'roleCandidat.personne'
        ])->get();

        return $this->sendResponse($contestations, 'Liste des contestations récupérée avec succès.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'resultat_bureau_vote_id' => 'required|exists:resultats_bureau_vote,id',
            'role_representant_id' => 'required|exists:role_representants,id',
            'role_candidat_id' => 'required|exists:role_candidats,id',
            'motif' => 'required|string',
            'description' => 'required|string',
            'pieces_jointes' => 'nullable|array',
            'pieces_jointes.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120' // 5MB par fichier
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Vérifier si une contestation existe déjà pour ce représentant et ce bureau
        $contestationExistante = Contestation::where('resultat_bureau_vote_id', $request->resultat_bureau_vote_id)
            ->where('role_representant_id', $request->role_representant_id)
            ->where('role_candidat_id', $request->role_candidat_id)
            ->exists();

        if ($contestationExistante) {
            return $this->sendError(
                'Erreur de validation',
                ['message' => 'Une contestation existe déjà pour ce bureau de vote.'],
                422
            );
        }

        $data = $request->all();
        $data['statut'] = 'EN_ATTENTE';
        $data['date_soumission'] = now();

        // Gérer les pièces jointes
        if ($request->hasFile('pieces_jointes')) {
            $pieceJointes = [];
            foreach ($request->file('pieces_jointes') as $file) {
                $pieceJointes[] = $file->store('contestations', 'public');
            }
            $data['pieces_jointes'] = $pieceJointes;
        }

        $contestation = Contestation::create($data);

        return $this->sendResponse(
            $contestation->load(['resultatBureauVote', 'roleRepresentant', 'roleCandidat']),
            'Contestation enregistrée avec succès.',
            201
        );
    }

    public function show(Contestation $contestation)
    {
        $contestation->load([
            'resultatBureauVote.bureauDeVote',
            'roleRepresentant.roleUtilisateur.personne',
            'roleCandidat.personne'
        ]);

        return $this->sendResponse($contestation, 'Détails de la contestation récupérés avec succès.');
    }

    public function update(Request $request, Contestation $contestation)
    {
        if ($contestation->statut !== 'EN_ATTENTE') {
            return $this->sendError(
                'Opération non autorisée',
                ['message' => 'Seules les contestations en attente peuvent être modifiées.'],
                403
            );
        }

        $validator = Validator::make($request->all(), [
            'motif' => 'sometimes|required|string',
            'description' => 'sometimes|required|string',
            'pieces_jointes' => 'nullable|array',
            'pieces_jointes.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $data = $request->all();

        // Gérer les nouvelles pièces jointes
        if ($request->hasFile('pieces_jointes')) {
            // Supprimer les anciennes pièces jointes
            if ($contestation->pieces_jointes) {
                foreach ($contestation->pieces_jointes as $piece) {
                    Storage::disk('public')->delete($piece);
                }
            }

            $pieceJointes = [];
            foreach ($request->file('pieces_jointes') as $file) {
                $pieceJointes[] = $file->store('contestations', 'public');
            }
            $data['pieces_jointes'] = $pieceJointes;
        }

        $contestation->update($data);

        return $this->sendResponse(
            $contestation->load(['resultatBureauVote', 'roleRepresentant', 'roleCandidat']),
            'Contestation mise à jour avec succès.'
        );
    }

    public function destroy(Contestation $contestation)
    {
        if ($contestation->statut !== 'EN_ATTENTE') {
            return $this->sendError(
                'Opération non autorisée',
                ['message' => 'Seules les contestations en attente peuvent être supprimées.'],
                403
            );
        }

        try {
            // Supprimer les pièces jointes
            if ($contestation->pieces_jointes) {
                foreach ($contestation->pieces_jointes as $piece) {
                    Storage::disk('public')->delete($piece);
                }
            }

            $contestation->delete();
            return $this->sendResponse(null, 'Contestation supprimée avec succès.');
        } catch (\Exception $e) {
            return $this->sendError(
                'Erreur lors de la suppression',
                ['message' => 'Une erreur est survenue lors de la suppression.'],
                409
            );
        }
    }

    // Méthodes additionnelles pour la gestion des contestations

    public function traiterContestation(Request $request, Contestation $contestation)
    {
        if ($contestation->statut !== 'EN_ATTENTE') {
            return $this->sendError(
                'Opération non autorisée',
                ['message' => 'Cette contestation a déjà été traitée.'],
                403
            );
        }

        $validator = Validator::make($request->all(), [
            'decision' => 'required|in:ACCEPTEE,REJETEE',
            'commentaire' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $contestation->update([
            'statut' => $request->decision,
            'decision' => $request->commentaire,
            'date_traitement' => now()
        ]);

        // Si la contestation est acceptée, marquer le résultat comme non valide
        if ($request->decision === 'ACCEPTEE') {
            $contestation->resultatBureauVote->update([
                'validite' => false
            ]);
        }

        return $this->sendResponse($contestation, 'Contestation traitée avec succès.');
    }

    public function getPieceJointe($contestationId, $index)
    {
        $contestation = Contestation::findOrFail($contestationId);

        if (!$contestation->pieces_jointes || !isset($contestation->pieces_jointes[$index])) {
            return $this->sendError('Non trouvé', ['message' => 'Pièce jointe non trouvée.'], 404);
        }

        $pieceJointe = $contestation->pieces_jointes[$index];
        return Storage::disk('public')->download($pieceJointe);
    }

    public function statistiques()
    {
        $stats = [
            'total' => Contestation::count(),
            'par_statut' => [
                'en_attente' => Contestation::where('statut', 'EN_ATTENTE')->count(),
                'acceptees' => Contestation::where('statut', 'ACCEPTEE')->count(),
                'rejetees' => Contestation::where('statut', 'REJETEE')->count()
            ],
            'par_bureau' => Contestation::join('resultats_bureau_vote', 'contestations.resultat_bureau_vote_id', '=', 'resultats_bureau_vote.id')
                ->join('bureaux_de_vote', 'resultats_bureau_vote.bureau_de_vote_id', '=', 'bureaux_de_vote.id')
                ->select('bureaux_de_vote.nom', \DB::raw('count(*) as total'))
                ->groupBy('bureaux_de_vote.id', 'bureaux_de_vote.nom')
                ->get(),
            'delai_moyen_traitement' => Contestation::whereNotNull('date_traitement')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, date_soumission, date_traitement)) as delai_moyen')
                ->value('delai_moyen')
        ];

        return $this->sendResponse($stats, 'Statistiques des contestations récupérées avec succès.');
    }

    public function historique(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_debut' => 'sometimes|required|date',
            'date_fin' => 'sometimes|required|date|after:date_debut',
            'statut' => 'sometimes|required|in:EN_ATTENTE,ACCEPTEE,REJETEE'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $query = Contestation::with([
            'resultatBureauVote.bureauDeVote',
            'roleRepresentant.roleUtilisateur.personne',
            'roleCandidat.personne'
        ]);

        if ($request->has('date_debut')) {
            $query->whereDate('date_soumission', '>=', $request->date_debut);
        }

        if ($request->has('date_fin')) {
            $query->whereDate('date_soumission', '<=', $request->date_fin);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        $historique = $query->orderBy('date_soumission', 'desc')->paginate(15);

        return $this->sendResponse($historique, 'Historique des contestations récupéré avec succès.');
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Models\RoleRepresentant;
use App\Models\Contestation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RoleRepresentantController extends BaseController
{
    public function index()
    {
        $representants = RoleRepresentant::with([
            'roleUtilisateur.personne',
            'contestations'
        ])->get();

        return $this->sendResponse($representants, 'Liste des représentants récupérée avec succès.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_utilisateur_id' => 'required|exists:role_utilisateurs,id|unique:role_representants',
            'code' => 'sometimes|string|unique:role_representants'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $data = $request->all();
        if (!isset($data['code'])) {
            $data['code'] = 'REP-' . Str::upper(Str::random(8));
        }

        $representant = RoleRepresentant::create($data);

        return $this->sendResponse(
            $representant->load('roleUtilisateur.personne'),
            'Représentant créé avec succès.',
            201
        );
    }

    public function show(RoleRepresentant $representant)
    {
        $representant->load([
            'roleUtilisateur.personne',
            'contestations.resultatBureauVote',
            'contestations.roleCandidat'
        ]);

        return $this->sendResponse($representant, 'Détails du représentant récupérés avec succès.');
    }

    public function update(Request $request, RoleRepresentant $representant)
    {
        $validator = Validator::make($request->all(), [
            'role_utilisateur_id' => 'sometimes|required|exists:role_utilisateurs,id|unique:role_representants,role_utilisateur_id,' . $representant->id,
            'code' => 'sometimes|required|string|unique:role_representants,code,' . $representant->id
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $representant->update($request->all());

        return $this->sendResponse(
            $representant->load('roleUtilisateur.personne'),
            'Représentant mis à jour avec succès.'
        );
    }

    public function destroy(RoleRepresentant $representant)
    {
        try {
            $representant->delete();
            return $this->sendResponse(null, 'Représentant supprimé avec succès.');
        } catch (\Exception $e) {
            return $this->sendError(
                'Erreur lors de la suppression',
                ['message' => 'Le représentant ne peut pas être supprimé car il est lié à des contestations.'],
                409
            );
        }
    }

    // Méthodes spécifiques aux représentants

    public function contestations(RoleRepresentant $representant)
    {
        $contestations = $representant->contestations()
            ->with(['resultatBureauVote', 'roleCandidat'])
            ->orderBy('date_soumission', 'desc')
            ->get();

        return $this->sendResponse($contestations, 'Contestations du représentant récupérées avec succès.');
    }

    public function soumettreContestation(Request $request, RoleRepresentant $representant)
    {
        $validator = Validator::make($request->all(), [
            'resultat_bureau_vote_id' => 'required|exists:resultats_bureau_vote,id',
            'role_candidat_id' => 'required|exists:role_candidats,id',
            'motif' => 'required|string',
            'description' => 'required|string',
            'pieces_jointes' => 'nullable|array',
            'pieces_jointes.*' => 'file|mimes:pdf,jpg,jpeg,png|max:2048'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $contestation = new Contestation([
            'resultat_bureau_vote_id' => $request->resultat_bureau_vote_id,
            'role_candidat_id' => $request->role_candidat_id,
            'role_representant_id' => $representant->id,
            'motif' => $request->motif,
            'description' => $request->description,
            'statut' => 'EN_ATTENTE',
            'date_soumission' => now()
        ]);

        // Gestion des pièces jointes si présentes
        if ($request->hasFile('pieces_jointes')) {
            $pieces_jointes = [];
            foreach ($request->file('pieces_jointes') as $file) {
                $path = $file->store('contestations', 'public');
                $pieces_jointes[] = $path;
            }
            $contestation->pieces_jointes = $pieces_jointes;
        }

        $contestation->save();

        return $this->sendResponse(
            $contestation->load(['resultatBureauVote', 'roleCandidat']),
            'Contestation soumise avec succès.',
            201
        );
    }

    public function statutContestations(RoleRepresentant $representant)
    {
        $stats = [
            'total' => $representant->contestations()->count(),
            'en_attente' => $representant->contestations()->where('statut', 'EN_ATTENTE')->count(),
            'acceptees' => $representant->contestations()->where('statut', 'ACCEPTEE')->count(),
            'rejetees' => $representant->contestations()->where('statut', 'REJETEE')->count()
        ];

        return $this->sendResponse($stats, 'Statistiques des contestations récupérées avec succès.');
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Models\BureauDeVote;
use App\Models\ResultatBureauVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;

class GestionBureauVoteController extends BaseController
{
    public function ouvrirBureau(BureauDeVote $bureauDeVote)
    {
        // Vérifier si l'élection est en cours
        if (!$bureauDeVote->election || $bureauDeVote->election->statut !== 'EN_COURS') {
            return $this->sendError(
                'Opération non autorisée',
                ['message' => 'Le bureau ne peut être ouvert que pendant une élection en cours.'],
                403
            );
        }

        // Vérifier si le bureau n'est pas déjà ouvert
        if ($bureauDeVote->statut === 'OUVERT') {
            return $this->sendError(
                'Opération non autorisée',
                ['message' => 'Le bureau est déjà ouvert.'],
                403
            );
        }

        $bureauDeVote->update([
            'statut' => 'OUVERT',
            'heure_ouverture' => now()
        ]);

        return $this->sendResponse($bureauDeVote, 'Bureau de vote ouvert avec succès.');
    }

    public function fermerBureau(Request $request, BureauDeVote $bureauDeVote)
    {
        // Vérifier si le bureau est ouvert
        if ($bureauDeVote->statut !== 'OUVERT') {
            return $this->sendError(
                'Opération non autorisée',
                ['message' => 'Le bureau doit être ouvert pour pouvoir être fermé.'],
                403
            );
        }

        // Valider les données finales
        $validator = Validator::make($request->all(), [
            'nombre_votants' => 'required|integer|min:0|max:' . $bureauDeVote->nombre_inscrits,
            'bulletins_nuls' => 'required|integer|min:0',
            'bulletins_blancs' => 'required|integer|min:0',
            'observations' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        // Mettre à jour le résultat du bureau
        $resultat = ResultatBureauVote::where('bureau_de_vote_id', $bureauDeVote->id)
            ->first();

        if (!$resultat) {
            return $this->sendError(
                'Erreur',
                ['message' => 'Aucun résultat trouvé pour ce bureau.'],
                404
            );
        }

        $resultat->update([
            'nombre_votants' => $request->nombre_votants,
            'bulletins_nuls' => $request->bulletins_nuls,
            'bulletins_blancs' => $request->bulletins_blancs,
            'suffrages_exprimes' => $request->nombre_votants - $request->bulletins_nuls - $request->bulletins_blancs,
            'observations' => $request->observations
        ]);

        // Générer le PV
        $pv = $this->genererPV($bureauDeVote, $resultat);

        // Mettre à jour le bureau de vote
        $bureauDeVote->update([
            'statut' => 'FERME',
            'heure_fermeture' => now()
        ]);

        return $this->sendResponse([
            'bureau' => $bureauDeVote,
            'resultat' => $resultat,
            'pv' => base64_encode($pv->output())
        ], 'Bureau de vote fermé avec succès et PV généré.');
    }

    private function genererPV($bureauDeVote, $resultat)
    {
        $data = [
            'bureau' => $bureauDeVote->load(['centreDeVote.commune', 'listeElectorale']),
            'resultat' => $resultat->load(['voteCandidats.candidature.roleCandidat.personne']),
            'date' => now()->format('d/m/Y'),
            'heure_ouverture' => $bureauDeVote->heure_ouverture,
            'heure_fermeture' => $bureauDeVote->heure_fermeture,
            'membres_bureau' => $bureauDeVote->affectations()
                ->with('rolePersonnelBV.roleUtilisateur.personne')
                ->get()
        ];

        $pdf = PDF::loadView('pdfs.pv_bureau_vote', $data);

        // Sauvegarder le PV
        $nomFichier = 'pv_' . $bureauDeVote->id . '_' . now()->format('Ymd_His') . '.pdf';
        $resultat->update(['pv' => $nomFichier]);

        return $pdf;
    }

    public function getBureauStatus(BureauDeVote $bureauDeVote)
    {
        return $this->sendResponse([
            'bureau' => $bureauDeVote->load(['centreDeVote.commune']),
            'resultat' => $bureauDeVote->resultats()->latest()->first(),
            'statut' => $bureauDeVote->statut,
            'heure_ouverture' => $bureauDeVote->heure_ouverture,
            'heure_fermeture' => $bureauDeVote->heure_fermeture
        ], 'Statut du bureau récupéré avec succès.');
    }
}

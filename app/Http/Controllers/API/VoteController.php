<?php

namespace App\Http\Controllers\API;

use App\Models\RoleElecteur;
use App\Models\BureauDeVote;
use App\Models\ResultatBureauVote;
use App\Models\VoteCandidat;
use App\Models\JournalVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VoteController extends BaseController
{
    public function voter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'numero_electeur' => 'required|exists:role_electeurs,numero_electeur',
            'bureau_vote_id' => 'required|exists:bureau_de_votes,id',
            'candidature_id' => 'required|exists:candidatures,id'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            // Vérifier si l'électeur existe et n'a pas déjà voté
            $electeur = RoleElecteur::where('numero_electeur', $request->numero_electeur)
                ->first();

            if ($electeur->a_voter) {
                return $this->sendError(
                    'Vote non autorisé',
                    ['message' => 'Cet électeur a déjà voté.'],
                    403
                );
            }

            // Vérifier si l'électeur appartient au bureau de vote
            if ($electeur->listeElectorale->bureau_de_vote_id !== $request->bureau_vote_id) {
                return $this->sendError(
                    'Vote non autorisé',
                    ['message' => 'Cet électeur n\'est pas inscrit dans ce bureau de vote.'],
                    403
                );
            }

            // Vérifier si une élection est en cours dans ce bureau
            $bureauDeVote = BureauDeVote::find($request->bureau_vote_id);
            if ($bureauDeVote->statut !== 'ACTIF') {
                return $this->sendError(
                    'Vote non autorisé',
                    ['message' => 'Ce bureau de vote n\'est pas actif.'],
                    403
                );
            }

            // Récupérer le résultat du bureau de vote
            $resultat = ResultatBureauVote::where('bureau_de_vote_id', $request->bureau_vote_id)
                ->where('validite', false)
                ->first();

            if (!$resultat) {
                return $this->sendError(
                    'Vote impossible',
                    ['message' => 'Aucun résultat en cours dans ce bureau de vote.'],
                    404
                );
            }

            // Incrémenter le nombre de votants et suffrages exprimés
            $resultat->increment('nombre_votants');
            $resultat->increment('suffrages_exprimes');

            // Incrémenter le nombre de voix pour le candidat
            $voteCandidat = VoteCandidat::where('resultat_bureau_vote_id', $resultat->id)
                ->where('candidature_id', $request->candidature_id)
                ->first();

            if (!$voteCandidat) {
                return $this->sendError(
                    'Vote impossible',
                    ['message' => 'Candidat non trouvé dans ce bureau de vote.'],
                    404
                );
            }

            $voteCandidat->increment('nombre_voix');

            // Marquer l'électeur comme ayant voté
            $electeur->update(['a_voter' => true]);

            // Enregistrer dans le journal des votes
            JournalVote::create([
                'bureau_de_vote_id' => $request->bureau_vote_id,
                'numero_electeur' => $request->numero_electeur,
                'horodatage' => now(),
                'ip_address' => $request->ip()
            ]);

            DB::commit();
            return $this->sendResponse(null, 'Vote enregistré avec succès.');

        } catch (\Exception $e) {
            DB::rollback();
            return $this->sendError('Erreur lors du vote', ['message' => $e->getMessage()]);
        }
    }

    // Méthode pour voter blanc
    public function voterBlanc(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'numero_electeur' => 'required|exists:role_electeurs,numero_electeur',
            'bureau_vote_id' => 'required|exists:bureaux_de_vote,id'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            // Mêmes vérifications que pour le vote normal
            $electeur = RoleElecteur::where('numero_electeur', $request->numero_electeur)
                ->first();

            if ($electeur->a_voter) {
                return $this->sendError(
                    'Vote non autorisé',
                    ['message' => 'Cet électeur a déjà voté.'],
                    403
                );
            }

            // Autres vérifications similaires...

            $resultat = ResultatBureauVote::where('bureau_de_vote_id', $request->bureau_vote_id)
                ->where('validite', false)
                ->first();

            // Incrémenter le nombre de votants et bulletins blancs
            $resultat->increment('nombre_votants');
            $resultat->increment('bulletins_blancs');

            // Marquer l'électeur comme ayant voté
            $electeur->update(['a_voter' => true]);

            // Journal des votes
            JournalVote::create([
                'bureau_de_vote_id' => $request->bureau_vote_id,
                'numero_electeur' => $request->numero_electeur,
                'horodatage' => now(),
                'ip_address' => $request->ip()
            ]);

            DB::commit();
            return $this->sendResponse(null, 'Vote blanc enregistré avec succès.');

        } catch (\Exception $e) {
            DB::rollback();
            return $this->sendError('Erreur lors du vote', ['message' => $e->getMessage()]);
        }
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BureauDeVote;
use App\Models\Election;
use App\Models\RoleElecteur;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getPublicDashboard()
    {
        $electionEnCours = Election::where('statut', 'EN_COURS')->first();

        $stats = [
            'election' => $electionEnCours ? [
                'titre' => $electionEnCours->titre,
                'date' => $electionEnCours->date,
                'nombre_candidats' => $electionEnCours->candidatures()->count(),
                'nombre_inscrits' => RoleElecteur::count(),
                'nombre_votants' => RoleElecteur::where('a_voter', true)->count(),
                'taux_participation' => $this->calculateParticipationRate(),
                'progression_depouillement' => $this->calculateDepouillementProgress()
            ] : null,
            'bureaux_vote' => [
                'total' => BureauDeVote::count(),
                'ouverts' => BureauDeVote::where('statut', 'OUVERT')->count(),
                'fermes' => BureauDeVote::where('statut', 'FERME')->count(),
                'progression' => $this->calculateBureauProgress()
            ],
            'resultats_provisoires' => $electionEnCours ? [
                'global' => $this->getResultatsProvisoires($electionEnCours),
                'par_region' => $this->getResultatsParRegion(),
                'derniere_mise_a_jour' => now()->format('Y-m-d H:i:s')
            ] : null,
            'carte_participation' => $this->getParticipationMap()
        ];

        return $this->sendResponse($stats, 'Tableau de bord public récupéré avec succès.');
    }
    public function getAdminDashboard()
    {
        $electionEnCours = Election::where('statut', 'EN_COURS')->first();

        $stats = [
            'election_en_cours' => $electionEnCours ? [
                'informations_generales' => [
                    'titre' => $electionEnCours->titre,
                    'date' => $electionEnCours->date,
                    'statut' => $electionEnCours->statut,
                    'nombre_candidats' => $electionEnCours->candidatures()->count()
                ],
                'resultats' => $this->getResultatsDetailles($electionEnCours)
            ] : null,
            'statistiques_generales' => [
                'electeurs' => $this->getStatistiquesElecteurs(),
                'participation' => $this->getStatistiquesParticipation(),
                'bureaux_vote' => $this->getStatistiquesBureaux(),
                'personnel' => $this->getStatistiquesPersonnel()
            ],
            'analyses_regionales' => [
                'participation' => $this->getAnalyseParticipationRegionale(),
                'progression' => $this->getProgressionRegionale(),
                'incidents' => $this->getIncidentsParRegion()
            ],
            'alertes_et_notifications' => [
                'incidents_majeurs' => $this->getIncidentsMajeurs(),
                'alertes_securite' => $this->getAlerteSecurite(),
                'notifications_importantes' => $this->getNotificationsImportantes()
            ],
            'tendances_et_analyses' => [
                'evolution_participation' => $this->getEvolutionParticipation(),
                'comparaison_historique' => $this->getComparaisonHistorique(),
                'analyse_demographique' => $this->getAnalyseDemographique()
            ]
        ];

        return $this->sendResponse($stats, 'Tableau de bord administrateur récupéré avec succès.');
    }
    public function getSuperviseurDashboard()
    {
        $stats = [
            'surveillance_temps_reel' => [
                'bureaux_vote' => $this->getSurveillanceBureaux(),
                'incidents' => $this->getSurveillanceIncidents(),
                'contestations' => $this->getSurveillanceContestations()
            ],
            'validation_resultats' => [
                'etat_transmission' => $this->getEtatTransmission(),
                'resultats_en_attente' => $this->getResultatsEnAttente(),
                'resultats_valides' => $this->getResultatsValides(),
                'anomalies_detectees' => $this->getAnomaliesDetectees()
            ],
            'gestion_contestations' => [
                'synthese' => $this->getSyntheseContestations(),
                'contestations_recentes' => $this->getContestationsRecentes(),
                'analyses_motifs' => $this->getAnalysesMotifsContestation()
            ],
            'rapports_observateurs' => [
                'incidents_signales' => $this->getIncidentsSignales(),
                'observations_terrain' => $this->getObservationsTerrain(),
                'recommandations' => $this->getRecommandations()
            ],
            'statistiques_validation' => [
                'temps_moyen_validation' => $this->getTempsValidation(),
                'taux_rejet' => $this->getTauxRejet(),
                'motifs_invalidation' => $this->getMotifsInvalidation()
            ]
        ];

        return $this->sendResponse($stats, 'Tableau de bord superviseur récupéré avec succès.');
    }
}

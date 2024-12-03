<?php

use App\Http\Controllers\API\CombinedRegistrationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PersonneController;
use App\Http\Controllers\API\RoleElecteurController;
use App\Http\Controllers\API\RoleCandidatController;
use App\Http\Controllers\API\RoleUtilisateurController;
use App\Http\Controllers\API\RoleSuperviseurCENAController;
use App\Http\Controllers\API\RolePersonnelBVController;
use App\Http\Controllers\API\RoleRepresentantController;
use App\Http\Controllers\API\RoleAdminDGEController;
use App\Http\Controllers\API\PaysController;
use App\Http\Controllers\API\RegionController;
use App\Http\Controllers\API\DepartementController;
use App\Http\Controllers\API\CommuneController;
use App\Http\Controllers\API\CentreDeVoteController;
use App\Http\Controllers\API\BureauDeVoteController;
use App\Http\Controllers\API\ListeElectoraleController;
use App\Http\Controllers\API\ResultatBureauVoteController;
use App\Http\Controllers\API\VoteCandidatController;
use App\Http\Controllers\API\ElectionController;
use App\Http\Controllers\API\CandidatureController;
use App\Http\Controllers\API\ContestationController;
use App\Http\Controllers\API\AffectationController;
use App\Http\Controllers\API\JournalUtilisateurController;
use App\Http\Controllers\API\JournalVoteController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Routes publiques
Route::post('/login', [AuthController::class, 'login']);

// Routes protégées
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Routes pour la gestion des personnes et des rôles
    Route::apiResource('personnes', PersonneController::class);
    Route::apiResource('electeurs', RoleElecteurController::class);
    Route::apiResource('candidats', RoleCandidatController::class);
    Route::apiResource('utilisateurs', RoleUtilisateurController::class);
    Route::apiResource('superviseurs-cena', RoleSuperviseurCENAController::class);
    Route::apiResource('personnel-bv', RolePersonnelBVController::class);
    Route::apiResource('representants', RoleRepresentantController::class);
    Route::apiResource('admin-dge', RoleAdminDGEController::class);

    // Routes pour les entités administratives
    Route::apiResource('pays', PaysController::class);
    Route::apiResource('regions', RegionController::class);
    Route::apiResource('departements', DepartementController::class);
    Route::apiResource('communes', CommuneController::class);

    // Routes pour le processus électoral
    Route::apiResource('centres-de-vote', CentreDeVoteController::class);
    Route::apiResource('bureaux-de-vote', BureauDeVoteController::class);
    Route::apiResource('listes-electorales', ListeElectoraleController::class);
    Route::apiResource('resultats-bureau-vote', ResultatBureauVoteController::class);
    Route::apiResource('vote-candidats', VoteCandidatController::class);
    Route::apiResource('elections', ElectionController::class);
    Route::apiResource('candidatures', CandidatureController::class);
    Route::apiResource('contestations', ContestationController::class);
    Route::apiResource('affectations', AffectationController::class);
    Route::apiResource('journal-utilisateurs', JournalUtilisateurController::class);
    Route::apiResource('journal-votes', JournalVoteController::class);

    // Routes additionnelles pour les électeurs
    Route::prefix('electeurs')->group(function () {
        Route::get('verification/{numeroElecteur}', [RoleElecteurController::class, 'verifierElecteur']);
        Route::post('import', [RoleElecteurController::class, 'importerElecteurs']);
        Route::get('export', [RoleElecteurController::class, 'exporterElecteurs']);
        Route::get('statistiques', [RoleElecteurController::class, 'statistiques']);
    });

    // Routes additionnelles pour les candidats
    Route::prefix('candidats')->group(function () {
        Route::get('{candidat}/resultats', [RoleCandidatController::class, 'resultats']);
        Route::get('{candidat}/contestations', [RoleCandidatController::class, 'contestations']);
    });

    // Routes additionnelles pour les élections
    Route::prefix('elections')->group(function () {
        Route::post('{election}/demarrer', [ElectionController::class, 'demarrerElection']);
        Route::post('{election}/terminer', [ElectionController::class, 'terminerElection']);
        Route::post('{election}/annuler', [ElectionController::class, 'annulerElection']);
        Route::get('{election}/resultats-provisoires', [ElectionController::class, 'getResultatsProvisoires']);
        Route::get('{election}/statistiques', [ElectionController::class, 'getStatistiques']);
        Route::get('{election}/candidatures', [ElectionController::class, 'getCandidatures']);
    });

    // Routes additionnelles pour les bureaux de vote
    Route::prefix('bureaux-de-vote')->group(function () {
        Route::get('{bureauDeVote}/personnel', [BureauDeVoteController::class, 'personnelAffecte']);
        Route::get('{bureauDeVote}/journal-votes', [BureauDeVoteController::class, 'journalVotes']);
        Route::get('{bureauDeVote}/statistiques', [BureauDeVoteController::class, 'statistiquesParticipation']);
    });

    // Routes additionnelles pour les résultats
    Route::prefix('resultats')->group(function () {
        Route::post('{resultatBureauVote}/valider', [ResultatBureauVoteController::class, 'valider']);
        Route::get('{resultatBureauVote}/contestations', [ResultatBureauVoteController::class, 'getContestations']);
        Route::get('{resultatBureauVote}/pv', [ResultatBureauVoteController::class, 'getPv']);
        Route::get('finalises', [ResultatBureauVoteController::class, 'resultatsFinalises']);
    });

    // Routes pour les contestations
    Route::prefix('contestations')->group(function () {
        Route::post('{contestation}/traiter', [ContestationController::class, 'traiterContestation']);
        Route::get('statistiques', [ContestationController::class, 'statistiques']);
        Route::get('historique', [ContestationController::class, 'historique']);
    });

    // Routes pour les affectations
    Route::prefix('affectations')->group(function () {
        Route::get('actives', [AffectationController::class, 'getAffectationsActives']);
        Route::get('bureau/{bureauDeVoteId}', [AffectationController::class, 'getAffectationsParBureauDeVote']);
        Route::get('personnel/{personnelId}', [AffectationController::class, 'getAffectationsParPersonnel']);
        Route::get('statistiques', [AffectationController::class, 'statistiques']);
    });

    // Routes pour les journaux
    Route::prefix('journaux')->group(function () {
        Route::get('utilisateurs/recherche', [JournalUtilisateurController::class, 'recherche']);
        Route::get('utilisateurs/statistiques', [JournalUtilisateurController::class, 'statistiques']);
        Route::get('utilisateurs/export', [JournalUtilisateurController::class, 'exporterCSV']);
        Route::get('votes/recherche', [JournalVoteController::class, 'recherche']);
        Route::get('votes/statistiques', [JournalVoteController::class, 'statistiques']);
    });
    Route::prefix('register')->group(function () {
        Route::post('/electeur', [CombinedRegistrationController::class, 'createElecteur']);
        Route::post('/candidat', [CombinedRegistrationController::class, 'createCandidat']);
        Route::post('/superviseur-cena', [CombinedRegistrationController::class, 'createSuperviseurCENA']);
        Route::post('/personnel-bv', [CombinedRegistrationController::class, 'createPersonnelBV']);
        Route::post('/representant', [CombinedRegistrationController::class, 'createRepresentant']);
        Route::post('/admin-dge', [CombinedRegistrationController::class, 'createAdminDGE']);
    });
});

// Route de fallback pour les routes non trouvées
Route::fallback(function(){
    return response()->json([
        'message' => 'Route non trouvée. Vérifiez l\'URL et la méthode HTTP.'],
        404);
});

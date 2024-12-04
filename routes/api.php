<?php

use App\Http\Controllers\API\CombinedRegistrationController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\GestionBureauVoteController;
use App\Http\Controllers\API\VoteController;
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
Route::get('/dashboard/public', [DashboardController::class, 'getPublicDashboard']);


// Routes protégées par authentification
Route::middleware('auth:sanctum')->group(function () {
    // Routes communes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Routes Admin DGE
    Route::middleware(['check.access:admin'])->group(function () {
        // Gestion des entités administratives
        Route::apiResource('pays', PaysController::class);
        Route::apiResource('regions', RegionController::class);
        Route::apiResource('departements', DepartementController::class);
        Route::apiResource('communes', CommuneController::class);
        Route::apiResource('centres-de-vote', CentreDeVoteController::class);
        Route::apiResource('bureaux-de-vote', BureauDeVoteController::class);

        // Gestion des listes électorales
        Route::apiResource('listes-electorales', ListeElectoraleController::class);

        // Gestion des personnes et rôles
        Route::apiResource('personnes', PersonneController::class);
        Route::apiResource('electeurs', RoleElecteurController::class);
        Route::apiResource('candidats', RoleCandidatController::class);
        Route::apiResource('utilisateurs', RoleUtilisateurController::class);
        Route::apiResource('superviseurs-cena', RoleSuperviseurCENAController::class);
        Route::apiResource('personnel-bv', RolePersonnelBVController::class);
        Route::apiResource('representants', RoleRepresentantController::class);
        Route::apiResource('admin-dge', RoleAdminDGEController::class);

        // Gestion des élections
        Route::apiResource('elections', ElectionController::class);
        Route::apiResource('candidatures', CandidatureController::class);
        Route::prefix('elections')->group(function () {
            Route::post('{election}/demarrer', [ElectionController::class, 'demarrerElection']);
            Route::post('{election}/terminer', [ElectionController::class, 'terminerElection']);
            Route::post('{election}/annuler', [ElectionController::class, 'annulerElection']);
            Route::get('{election}/resultats-provisoires', [ElectionController::class, 'getResultatsProvisoires']);
            Route::get('{election}/statistiques', [ElectionController::class, 'getStatistiques']);
            Route::get('{election}/candidatures', [ElectionController::class, 'getCandidatures']);
        });

        // Gestion des affectations
        Route::apiResource('affectations', AffectationController::class);
        Route::prefix('affectations')->group(function () {
            Route::get('actives', [AffectationController::class, 'getAffectationsActives']);
            Route::get('bureau/{bureauDeVoteId}', [AffectationController::class, 'getAffectationsParBureauDeVote']);
            Route::get('personnel/{personnelId}', [AffectationController::class, 'getAffectationsParPersonnel']);
            Route::get('statistiques', [AffectationController::class, 'statistiques']);
        });

        // Routes d'enregistrement combiné
        Route::prefix('register')->group(function () {
            Route::post('/electeur', [CombinedRegistrationController::class, 'createElecteur']);
            Route::post('/candidat', [CombinedRegistrationController::class, 'createCandidat']);
            Route::post('/superviseur-cena', [CombinedRegistrationController::class, 'createSuperviseurCENA']);
            Route::post('/personnel-bv', [CombinedRegistrationController::class, 'createPersonnelBV']);
            Route::post('/representant', [CombinedRegistrationController::class, 'createRepresentant']);
            Route::post('/admin-dge', [CombinedRegistrationController::class, 'createAdminDGE']);
        });
    });

    // Routes Superviseur CENA
    Route::middleware(['check.access:superviseur'])->group(function () {
        // Consultation des journaux
        Route::get('journal-utilisateurs', [JournalUtilisateurController::class, 'index']);
        Route::get('journaux/utilisateurs/recherche', [JournalUtilisateurController::class, 'recherche']);
        Route::get('journaux/utilisateurs/statistiques', [JournalUtilisateurController::class, 'statistiques']);

        // Gestion des résultats
        Route::get('resultats-bureau-vote', [ResultatBureauVoteController::class, 'index']);
        Route::post('resultats/{resultatBureauVote}/valider', [ResultatBureauVoteController::class, 'valider']);
        Route::get('resultats/finalises', [ResultatBureauVoteController::class, 'resultatsFinalises']);

        // Gestion des contestations
        Route::get('contestations', [ContestationController::class, 'index']);
        Route::get('contestations/statistiques', [ContestationController::class, 'statistiques']);
        Route::post('contestations/{contestation}/traiter', [ContestationController::class, 'traiterContestation']);
    });

    // Routes Personnel BV
    Route::middleware(['check.access:personnel'])->group(function () {
        Route::prefix('bureaux-vote')->group(function () {
            Route::post('/{bureauDeVote}/ouvrir', [GestionBureauVoteController::class, 'ouvrirBureau']);
            Route::post('/{bureauDeVote}/fermer', [GestionBureauVoteController::class, 'fermerBureau']);
            Route::get('/{bureauDeVote}/status', [GestionBureauVoteController::class, 'getBureauStatus']);

            // Gestion des votes
            Route::post('/vote', [VoteController::class, 'voter']);
            Route::post('/vote/blanc', [VoteController::class, 'voterBlanc']);
            Route::get('/{bureauDeVote}/journal-votes', [JournalVoteController::class, 'journalBureau']);
        });
    });

    // Routes Représentant
    Route::middleware(['check.access:representant'])->group(function () {
        Route::prefix('contestations')->group(function () {
            Route::post('/', [ContestationController::class, 'store']);
            Route::get('/', [ContestationController::class, 'index']);
            Route::get('/mes-contestations', [ContestationController::class, 'mesContestations']);
            Route::get('/{contestation}', [ContestationController::class, 'show']);
        });
    });
    Route::get('/dashboard/admin', [DashboardController::class, 'getAdminDashboard'])
        ->middleware('check.access:admin');

    // Dashboard Superviseur CENA
    Route::get('/dashboard/superviseur', [DashboardController::class, 'getSuperviseurDashboard'])
        ->middleware('check.access:superviseur');
});

// Route de fallback
Route::fallback(function() {
    return response()->json([
        'message' => 'Route non trouvée. Vérifiez l\'URL et la méthode HTTP.'
    ], 404);
});

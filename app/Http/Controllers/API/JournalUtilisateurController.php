<?php

namespace App\Http\Controllers\API;

use App\Models\JournalUtilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JournalUtilisateurController extends BaseController
{
    public function index()
    {
        $journaux = JournalUtilisateur::with([
            'roleUtilisateur.personne'
        ])
            ->orderBy('horodatage', 'desc')
            ->paginate(50);

        return $this->sendResponse($journaux, 'Liste du journal des utilisateurs récupérée avec succès.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_utilisateur_id' => 'required|exists:role_utilisateurs,id',
            'action' => 'required|string',
            'donnees_additionnelles' => 'nullable|array',
            'ip_address' => 'nullable|ip',
            'user_agent' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $data = $request->all();
        $data['horodatage'] = now();

        // Récupérer automatiquement l'IP et le User Agent si non fournis
        if (!isset($data['ip_address'])) {
            $data['ip_address'] = $request->ip();
        }
        if (!isset($data['user_agent'])) {
            $data['user_agent'] = $request->userAgent();
        }

        $journal = JournalUtilisateur::create($data);

        return $this->sendResponse($journal, 'Entrée de journal créée avec succès.', 201);
    }

    public function show(JournalUtilisateur $journalUtilisateur)
    {
        $journalUtilisateur->load('roleUtilisateur.personne');
        return $this->sendResponse($journalUtilisateur, 'Détails de l\'entrée de journal récupérés avec succès.');
    }

    // Méthodes de recherche et filtrage
    public function recherche(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_debut' => 'sometimes|required|date',
            'date_fin' => 'sometimes|required|date|after:date_debut',
            'utilisateur_id' => 'sometimes|exists:role_utilisateurs,id',
            'action' => 'sometimes|string',
            'ip_address' => 'sometimes|ip'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $query = JournalUtilisateur::with('roleUtilisateur.personne');

        if ($request->has('date_debut')) {
            $query->whereDate('horodatage', '>=', $request->date_debut);
        }

        if ($request->has('date_fin')) {
            $query->whereDate('horodatage', '<=', $request->date_fin);
        }

        if ($request->has('utilisateur_id')) {
            $query->where('role_utilisateur_id', $request->utilisateur_id);
        }

        if ($request->has('action')) {
            $query->where('action', 'LIKE', "%{$request->action}%");
        }

        if ($request->has('ip_address')) {
            $query->where('ip_address', $request->ip_address);
        }

        $journaux = $query->orderBy('horodatage', 'desc')->paginate(50);

        return $this->sendResponse($journaux, 'Résultats de la recherche récupérés avec succès.');
    }

    public function statistiques(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_debut' => 'sometimes|required|date',
            'date_fin' => 'sometimes|required|date|after:date_debut'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $query = JournalUtilisateur::query();

        if ($request->has('date_debut')) {
            $query->whereDate('horodatage', '>=', $request->date_debut);
        }

        if ($request->has('date_fin')) {
            $query->whereDate('horodatage', '<=', $request->date_fin);
        }

        $stats = [
            'total_actions' => $query->count(),
            'actions_par_type' => $query->select('action')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('action')
                ->get(),
            'actions_par_utilisateur' => $query->select('role_utilisateur_id')
                ->selectRaw('COUNT(*) as total')
                ->with('roleUtilisateur.personne')
                ->groupBy('role_utilisateur_id')
                ->get(),
            'activite_par_heure' => $query->selectRaw('HOUR(horodatage) as heure')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('heure')
                ->orderBy('heure')
                ->get(),
            'ips_frequentes' => $query->select('ip_address')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('ip_address')
                ->orderBy('total', 'desc')
                ->limit(10)
                ->get()
        ];

        return $this->sendResponse($stats, 'Statistiques du journal récupérées avec succès.');
    }

    public function exporterCSV(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        $journaux = JournalUtilisateur::with('roleUtilisateur.personne')
            ->whereBetween('horodatage', [$request->date_debut, $request->date_fin])
            ->orderBy('horodatage')
            ->get();

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=journal_utilisateurs.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = ['Date', 'Utilisateur', 'Action', 'IP', 'User Agent', 'Données additionnelles'];

        $callback = function() use ($journaux, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($journaux as $journal) {
                fputcsv($file, [
                    $journal->horodatage,
                    $journal->roleUtilisateur->personne->nom . ' ' . $journal->roleUtilisateur->personne->prenom,
                    $journal->action,
                    $journal->ip_address,
                    $journal->user_agent,
                    json_encode($journal->donnees_additionnelles)
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>PV Bureau de Vote</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .section {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        .signatures {
            margin-top: 50px;
        }
    </style>
</head>
<body>
<div class="header">
    <h2>PROCÈS VERBAL</h2>
    <h3>Résultats du Bureau de Vote</h3>
    <p>Date: {{ $date }}</p>
</div>

<div class="section">
    <h4>Informations du Bureau</h4>
    <p>Bureau de vote : {{ $bureau->nom }}</p>
    <p>Centre de vote : {{ $bureau->centreDeVote->nom }}</p>
    <p>Commune : {{ $bureau->centreDeVote->commune->nom }}</p>
    <p>Heure d'ouverture : {{ $bureau->heure_ouverture->format('H:i') }}</p>
    <p>Heure de fermeture : {{ $bureau->heure_fermeture->format('H:i') }}</p>
</div>

<div class="section">
    <h4>Résultats</h4>
    <table>
        <tr>
            <th>Nombre d'inscrits</th>
            <td>{{ $bureau->nombre_inscrits }}</td>
        </tr>
        <tr>
            <th>Nombre de votants</th>
            <td>{{ $resultat->nombre_votants }}</td>
        </tr>
        <tr>
            <th>Bulletins nuls</th>
            <td>{{ $resultat->bulletins_nuls }}</td>
        </tr>
        <tr>
            <th>Bulletins blancs</th>
            <td>{{ $resultat->bulletins_blancs }}</td>
        </tr>
        <tr>
            <th>Suffrages exprimés</th>
            <td>{{ $resultat->suffrages_exprimes }}</td>
        </tr>
    </table>

    <h4>Résultats par candidat</h4>
    <table>
        <tr>
            <th>Candidat</th>
            <th>Parti</th>
            <th>Nombre de voix</th>
        </tr>
        @foreach($resultat->voteCandidats as $vote)
            <tr>
                <td>{{ $vote->candidature->roleCandidat->personne->nom }} {{ $vote->candidature->roleCandidat->personne->prenom }}</td>
                <td>{{ $vote->candidature->roleCandidat->parti }}</td>
                <td>{{ $vote->nombre_voix }}</td>
            </tr>
        @endforeach
    </table>
</div>

<div class="section">
    <h4>Observations</h4>
    <p>{{ $resultat->observations ?? 'Aucune observation' }}</p>
</div>

<div class="signatures">
    <h4>Signatures des membres du bureau</h4>
    <table>
        <tr>
            <th>Fonction</th>
            <th>Nom et Prénom</th>
            <th>Signature</th>
        </tr>
        @foreach($membres_bureau as $membre)
            <tr>
                <td>{{ $membre->code_role }}</td>
                <td>{{ $membre->rolePersonnelBV->roleUtilisateur->personne->nom }}
                    {{ $membre->rolePersonnelBV->roleUtilisateur->personne->prenom }}</td>
                <td></td>
            </tr>
        @endforeach
    </table>
</div>

<div class="signatures">
    <h4>Signatures des représentants des candidats</h4>
    <table>
        <tr>
            <th>Candidat représenté</th>
            <th>Nom et Prénom du représentant</th>
            <th>Signature</th>
            <th>Observations</th>
        </tr>
        @foreach($resultat->voteCandidats as $vote)
            <tr>
                <td>
                    {{ $vote->candidature->roleCandidat->personne->nom }}
                    {{ $vote->candidature->roleCandidat->personne->prenom }}
                    ({{ $vote->candidature->roleCandidat->parti }})
                </td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endforeach
    </table>
</div>

<!-- Ajout d'une section pour les réclamations éventuelles -->
<div class="section">
    <h4>Réclamations et observations des représentants</h4>
    <p style="min-height: 100px; border: 1px solid #000; padding: 10px;">

    </p>
</div>
</body>
</html>

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckUserAccess
{
    public function handle(Request $request, Closure $next, $role)
    {
        $user = $request->user();

        switch ($role) {
            case 'admin':
                if (!$user->adminDGE) {
                    return response()->json(['message' => 'Accès réservé aux administrateurs DGE'], 403);
                }
                break;

            case 'superviseur':
                if (!$user->superviseurCENA) {
                    return response()->json(['message' => 'Accès réservé aux superviseurs CENA'], 403);
                }
                break;

            case 'personnel':
                if (!$user->personnelBV) {
                    return response()->json(['message' => 'Accès réservé au personnel de bureau de vote'], 403);
                }
                break;

            case 'representant':
                if (!$user->representant) {
                    return response()->json(['message' => 'Accès réservé aux représentants'], 403);
                }
                break;
        }

        return $next($request);
    }
}

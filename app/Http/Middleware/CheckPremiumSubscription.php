<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPremiumSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Vérifier si l'utilisateur est connecté
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentification requise'
            ], 401);
        }

        // Vérifier si l'utilisateur a un abonnement actif
        $subscription = $user->subscription;
        
        if (!$subscription || $subscription->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Abonnement Premium requis pour cette action',
                'premium_required' => true
            ], 403);
        }

        // Vérifier si l'abonnement n'a pas expiré
        if ($subscription->expires_at && $subscription->expires_at < now()) {
            return response()->json([
                'success' => false,
                'message' => 'Votre abonnement Premium a expiré',
                'premium_expired' => true
            ], 403);
        }

        return $next($request);
    }
}

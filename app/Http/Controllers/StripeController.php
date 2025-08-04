<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class StripeController extends Controller
{
    public function createCheckoutSession(Request $request)
    {
        \Log::info('Stripe session creation started');
        
        Stripe::setApiKey(config('services.stripe.secret'));
        
        $user = Auth::user();
        
        // ⭐ DEBUG ET VALIDATION DES URLS
        $frontUrl = config('services.app.front_url') ?: env('FRONT_URL', 'http://127.0.0.1:4200');
        
        \Log::info('URL Configuration Debug:', [
            'config_front_url' => config('services.app.front_url'),
            'env_front_url' => env('FRONT_URL'),
            'final_front_url' => $frontUrl,
            'app_env' => env('APP_ENV')
        ]);

        // ⭐ VALIDATION DES URLS AVANT ENVOI À STRIPE
        $successUrl = $frontUrl . '/dashboard?payment=success';
        $cancelUrl = $frontUrl . '/dashboard?payment=cancel';
        
        // Valider que les URLs sont correctes
        if (!filter_var($successUrl, FILTER_VALIDATE_URL)) {
            \Log::error('Invalid success URL:', ['url' => $successUrl]);
            return response()->json(['error' => 'Configuration URL invalide'], 500);
        }
        
        if (!filter_var($cancelUrl, FILTER_VALIDATE_URL)) {
            \Log::error('Invalid cancel URL:', ['url' => $cancelUrl]);
            return response()->json(['error' => 'Configuration URL invalide'], 500);
        }

        try {
            $session = Session::create([
                'payment_method_types' => ['card'],
                'mode' => 'subscription',
                'customer_email' => $user->email,
                'line_items' => [[
                    'price' => 'price_1RjG4KPLliFaFqSY0jYpClS2',
                    'quantity' => 1,
                ]],
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata' => [
                    'user_id' => $user->id,
                ],
            ]);

            \Log::info('Stripe session created successfully:', [
                'session_id' => $session->id,
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl
            ]);

            return response()->json(['url' => $session->url]);
            
        } catch (\Exception $e) {
            \Log::error('Stripe session creation failed:', [
                'error' => $e->getMessage(),
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl
            ]);
            
            return response()->json(['error' => 'Erreur lors de la création de la session Stripe'], 500);
        }
    }

    public function webhook(Request $request)
    {
        \Log::info('Stripe webhook received - bypassing verification for testing');
        
        $payload = $request->getContent();
        
        // ⭐ TEMPORAIRE : SKIP LA VÉRIFICATION POUR TESTER
        try {
            $event = json_decode($payload, true);
            \Log::info('Webhook payload decoded:', ['type' => $event['type'] ?? 'unknown']);
            
            // Traiter l'événement checkout.session.completed
            if (isset($event['type']) && $event['type'] === 'checkout.session.completed') {
                $session = $event['data']['object'];
                $userId = $session['metadata']['user_id'] ?? null;
                
                \Log::info('Processing checkout completion:', [
                    'session_id' => $session['id'] ?? 'unknown',
                    'user_id' => $userId
                ]);
                
                if ($userId) {
                    // Mettre à jour l'abonnement
                    \App\Models\Subscription::updateOrCreate(
                        ['user_id' => $userId],
                        [
                            'plan_type' => 'premium',
                            'status' => 'active',
                            'starts_at' => now(),
                            'expires_at' => now()->addMonth(),
                        ]
                    );
                    
                    \Log::info('Subscription updated for user:', ['user_id' => $userId]);
                }
            }
            
            return response('Webhook handled successfully', 200);
            
        } catch (\Exception $e) {
            \Log::error('Webhook processing failed:', ['error' => $e->getMessage()]);
            return response('Webhook processing failed', 400);
        }
    }
}
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
        \Log::info('Debug Stripe config:', [
    'stripe_secret' => env('STRIPE_SECRET') ? 'SET' : 'NOT SET',
    'app_env' => env('APP_ENV'),
    'config_cached' => config('app.env')
]);
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $user = Auth::user();
        
        $session = Session::create([
            'payment_method_types' => ['card'],
            'mode' => 'subscription',
            'customer_email' => $user->email,
            'line_items' => [[
                'price' => 'price_1RjG4KPLliFaFqSY0jYpClS2',
                'quantity' => 1,
            ]],
            'success_url' => 'http://82.112.255.241:3001/dashboard?payment=success',
            'cancel_url' => 'http://82.112.255.241:3001/dashboard?payment=cancel',
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);

        \Log::info('Stripe session created:', [
            'session_id' => $session->id,
            'url' => $session->url
        ]);

        return response()->json(['url' => $session->url]);
    }

    public function webhook(Request $request)
    {
        \Log::info('Stripe webhook received');
        
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $event = null;

        // ⭐ UTILISER LE BON WEBHOOK SECRET SELON L'ENVIRONNEMENT
        $webhookSecret = env('APP_ENV') === 'production' 
            ? env('STRIPE_WEBHOOK_SECRET_PRODUCTION')
            : env('STRIPE_WEBHOOK_SECRET');

        \Log::info('Using webhook secret for env:', ['env' => env('APP_ENV')]);

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, 
                $sig_header, 
                $webhookSecret // ⭐ SECRET DYNAMIQUE
            );
            
            \Log::info('Webhook event verified:', ['type' => $event->type]);
            
        } catch(\Exception $e) {
            \Log::error('Webhook verification failed:', ['error' => $e->getMessage()]);
            return response('Webhook verification failed', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $userId = $session->metadata->user_id ?? null;
            
            \Log::info('Processing checkout completion:', [
                'session_id' => $session->id,
                'user_id' => $userId
            ]);
            
            if ($userId) {
                // Crée ou update la souscription en BDD
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

        return response('Webhook handled', 200);
    }
}
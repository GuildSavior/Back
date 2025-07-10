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
        \Log::info('All env vars:', ['all' => $_ENV]);
        \Log::info('Stripe secret:', ['secret' => config('app.stripe.secret')]);
        \Log::info('Stripe webhook:', ['webhook' => config('app.stripe.webhook')]);
        Stripe::setApiKey(config('app.stripe.secret'));
        $user = Auth::user();

        $session = Session::create([
            'payment_method_types' => ['card'],
            'mode' => 'subscription',
            'customer_email' => $user->email,
            'line_items' => [[
                'price' => 'price_1RjG4KPLliFaFqSY0jYpClS2',
                'quantity' => 1,
            ]],
            'success_url' => 'http://127.0.0.1:4200/dashboard?payment=success',
            'cancel_url' => 'http://127.0.0.1:4200/dashboard?payment=cancel',
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);

        return response()->json(['url' => $session->url]);
    }

    public function webhook(Request $request)
    {
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, config('app.stripe.webhook')
            );
        } catch(\Exception $e) {
            return response('', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $userId = $session->metadata->user_id ?? null;
            if ($userId) {
                // Crée ou update la souscription en BDD
                \App\Models\Subscription::updateOrCreate(
                    ['user_id' => $userId],
                    [
                        'plan_type' => 'premium',
                        'status' => 'active',
                        'starts_at' => now(),
                        'expires_at' => now()->addMonth(), // Ajoute 1 mois
                    ]
                );
            }
        }

        return response('Webhook handled', 200);
    }
}
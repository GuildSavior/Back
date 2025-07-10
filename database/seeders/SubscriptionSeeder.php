<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Subscription;
use Carbon\Carbon;

class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer des abonnements freemium pour tous les utilisateurs existants
        User::whereDoesntHave('subscription')->each(function ($user) {
            Subscription::create([
                'user_id' => $user->id,
                'plan_type' => 'freemium',
                'status' => 'active',
                'starts_at' => Carbon::now(),
                'expires_at' => null, // Freemium n'expire pas
                'price' => 0,
            ]);
        });
    }
}

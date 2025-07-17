<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Subscription;

class TestUsersSeeder extends Seeder
{
    public function run()
    {
        // User 1 : Premium Owner
        $user1 = User::create([
            'discord_id' => '111111111111111111',
            'username' => 'TestOwner',
            'email' => 'owner@test.com',
            'avatar' => 'https://cdn.discordapp.com/embed/avatars/0.png',
        ]);

        // Subscription premium pour user1
        Subscription::create([
            'user_id' => $user1->id,
            'plan_type' => 'premium',
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
        ]);

        // User 2 : Member normal
        $user2 = User::create([
            'discord_id' => '222222222222222222',
            'username' => 'TestMember1',
            'email' => 'member1@test.com',
            'avatar' => 'https://cdn.discordapp.com/embed/avatars/1.png',
        ]);

        // User 3 : Autre member
        $user3 = User::create([
            'discord_id' => '333333333333333333',
            'username' => 'TestMember2',
            'email' => 'member2@test.com',
            'avatar' => 'https://cdn.discordapp.com/embed/avatars/2.png',
        ]);

        echo "✅ Utilisateurs de test créés !\n";
        echo "User 1 (Premium): {$user1->username} - ID: {$user1->id}\n";
        echo "User 2 (Normal): {$user2->username} - ID: {$user2->id}\n";
        echo "User 3 (Normal): {$user3->username} - ID: {$user3->id}\n";
    }
}
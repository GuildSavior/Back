<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Guild;
use App\Models\GuildMember;
use App\Models\GuildMemberDkp;
use App\Models\Role;

class DkpSystemTest extends TestCase
{
    use RefreshDatabase;

    protected $defaultRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // ⭐ CRÉER LE RÔLE ET RÉCUPÉRER SON ID
        $this->defaultRole = Role::create(['name' => 'user']);
    }

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    // Test pour le système DKP
    public function test_user_gets_correct_guild_dkp()
    {
        // ⭐ CRÉER UTILISATEUR AVEC LE BON RÔLE (OVERRIDE LA FACTORY)
        $user = User::factory()->create(['role_id' => $this->defaultRole->id]);
        
        // ⭐ CRÉER LES GUILDES AVEC UN OWNER SPÉCIFIQUE
        $guild1 = Guild::factory()->create(['owner_id' => $user->id]);
        $guild2 = Guild::factory()->create(['owner_id' => $user->id]);

        // ⭐ AJOUTER L'UTILISATEUR À LA GUILDE VIA guild_members
        GuildMember::create([
            'user_id' => $user->id,
            'guild_id' => $guild1->id,
            'role' => 'member',
            'joined_at' => now()
        ]);

        // DKP dans guilde 1
        GuildMemberDkp::create([
            'user_id' => $user->id,
            'guild_id' => $guild1->id,
            'dkp' => 100,
            'events_joined' => 5
        ]);

        // DKP dans guilde 2  
        GuildMemberDkp::create([
            'user_id' => $user->id,
            'guild_id' => $guild2->id,
            'dkp' => 50,
            'events_joined' => 2
        ]);

        // ⭐ VÉRIFIER LES DKP DE LA GUILDE ACTUELLE
        $this->assertEquals(100, $user->getCurrentGuildDkp());
    }

    public function test_user_gets_zero_dkp_when_no_guild()
    {
        // ⭐ UTILISATEUR SANS GUILDE (OVERRIDE LA FACTORY)
        $user = User::factory()->create(['role_id' => $this->defaultRole->id]);
        
        $this->assertEquals(0, $user->getCurrentGuildDkp());
    }

    public function test_user_gets_zero_dkp_when_no_record_for_guild()
    {
        $user = User::factory()->create(['role_id' => $this->defaultRole->id]);
        $guild = Guild::factory()->create(['owner_id' => $user->id]);
        
        // ⭐ AJOUTER À LA GUILDE MAIS SANS DKP
        GuildMember::create([
            'user_id' => $user->id,
            'guild_id' => $guild->id,
            'role' => 'member',
            'joined_at' => now()
        ]);

        // Pas de record DKP pour cette guilde
        $this->assertEquals(0, $user->getCurrentGuildDkp());
    }
}

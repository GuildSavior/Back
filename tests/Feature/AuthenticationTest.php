<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'user']);
    }

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }


    public function test_login_with_invalid_credentials()
    {
        $response = $this->postJson('/api/login', [
            'username' => 'wronguser',
            'password' => 'wrongpass'
        ]);

        $response->assertStatus(401)
                 ->assertJson(['message' => __('lang.login.bad_credentials')]);
    }

    public function test_protected_route_requires_authentication()
    {
        $response = $this->getJson('/api/user');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_access_protected_route()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user, 'sanctum')
                         ->getJson('/api/user');
                         
        $response->assertStatus(200);
    }
}

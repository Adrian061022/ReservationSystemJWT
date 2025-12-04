<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_current_user_profile()
    {
        // ARRANGE: Felhasználó létrehozása és bejelentkeztetése
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'name' => 'Test User'
        ]);

        // ACT: /users/me endpoint meghívása autentifikáció nélkül
        $response = $this->actingAs($user)->getJson('/api/users/me');

        // ASSERT: Ellenőrizzük a választ
        $response->assertStatus(200)
                 ->assertJsonStructure(['id', 'name', 'email'])
                 ->assertJson([
                     'name' => 'Test User',
                     'email' => 'user@example.com'
                 ]);
    }

    public function test_update_user_profile()
    {
        // ARRANGE: Felhasználó létrehozása
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'name' => 'Old Name'
        ]);

        // ACT: Profil frissítése
        $response = $this->actingAs($user)->putJson('/api/users/me', [
            'name' => 'New Name',
            'phone' => '+36201234567'
        ]);

        // ASSERT: Ellenőrizzük a módosítást
        $response->assertStatus(200)
                 ->assertJson([
                     'name' => 'New Name',
                     'phone' => '+36201234567'
                 ]);

        // Ellenőrizzük az adatbázist
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name'
        ]);
    }

    public function test_admin_list_all_users()
    {
        // ARRANGE: Admin felhasználó és normál felhasználók létrehozása
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->count(3)->create(['is_admin' => false]);

        // ACT: Felhasználók lekérése adminként
        $response = $this->actingAs($admin)->getJson('/api/users');

        // ASSERT: Ellenőrizzük a választ
        $response->assertStatus(200)
                 ->assertJsonCount(4); // 1 admin + 3 normál
    }

    public function test_non_admin_cannot_list_users()
    {
        // ARRANGE: Normál felhasználó
        $user = User::factory()->create(['is_admin' => false]);

        // ACT: Próbálunk felhasználókat lekérni
        $response = $this->actingAs($user)->getJson('/api/users');

        // ASSERT: Ellenőrizzük az elutasítást
        $response->assertStatus(403)
                 ->assertJson(['message' => 'Forbidden']);
    }

    public function test_admin_show_specific_user()
    {
        // ARRANGE: Admin és cél felhasználó
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['name' => 'Target User']);

        // ACT: Felhasználó lekérése
        $response = $this->actingAs($admin)->getJson("/api/users/{$user->id}");

        // ASSERT: Ellenőrizzük a választ
        $response->assertStatus(200)
                 ->assertJson(['name' => 'Target User']);
    }

    public function test_admin_delete_user()
    {
        // ARRANGE: Admin és törlendő felhasználó
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        // ACT: Felhasználó törlése
        $response = $this->actingAs($admin)->deleteJson("/api/users/{$user->id}");

        // ASSERT: Ellenőrizzük az eredményt
        $response->assertStatus(200)
                 ->assertJson(['message' => 'User deleted']);

        // Ellenőrizzük az adatbázist
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_unauthenticated_cannot_access_user_endpoints()
    {
        // ACT & ASSERT: Próbálunk hozzáférni autentifikáció nélkül
        $this->getJson('/api/users/me')->assertStatus(401);
        $this->putJson('/api/users/me', [])->assertStatus(401);
        $this->getJson('/api/users')->assertStatus(401);
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Resource;
use Tymon\JWTAuth\Facades\JWTAuth;

class ResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsJWT(User $user)
    {
        $token = JWTAuth::fromUser($user);
        return $this->withHeader('Authorization', 'Bearer ' . $token);
    }

    public function test_list_all_resources()
    {
        // ARRANGE: Erőforrások létrehozása
        Resource::factory()->count(3)->create();
        
        $user = User::factory()->create();

        // ACT: Erőforrások lekérése JWT tokennel
        $response = $this->actingAsJWT($user)->getJson('/api/resources');

        // ASSERT: Ellenőrizzük a választ
        $response->assertStatus(200)
                 ->assertJsonCount(3);
    }

    public function test_show_specific_resource()
    {
        // ARRANGE: Erőforrás és felhasználó létrehozása
        $resource = Resource::factory()->create([
            'name' => 'Meeting Room A',
            'type' => 'room'
        ]);
        
        $user = User::factory()->create();

        // ACT: Erőforrás lekérése JWT tokennel
        $response = $this->actingAsJWT($user)->getJson("/api/resources/{$resource->id}");

        // ASSERT: Ellenőrizzük a választ
        $response->assertStatus(200)
                 ->assertJson([
                     'name' => 'Meeting Room A',
                     'type' => 'room'
                 ]);
    }

    public function test_admin_create_resource()
    {
        // ARRANGE: Admin felhasználó
        $admin = User::factory()->create(['is_admin' => true]);

        // ACT: Erőforrás létrehozása JWT tokennel
        $response = $this->actingAsJWT($admin)->postJson('/api/resources', [
            'name' => 'New Resource',
            'type' => 'equipment',
            'description' => 'A test resource',
            'available' => true
        ]);

        // ASSERT: Ellenőrizzük az eredményt
        $response->assertStatus(201)
                 ->assertJsonStructure(['id', 'name', 'type', 'description', 'available']);

        // Ellenőrizzük az adatbázist
        $this->assertDatabaseHas('resources', [
            'name' => 'New Resource',
            'type' => 'equipment'
        ]);
    }

    public function test_non_admin_cannot_create_resource()
    {
        // ARRANGE: Normál felhasználó
        $user = User::factory()->create(['is_admin' => false]);

        // ACT: Erőforrás létrehozási kísérlете JWT tokennel
        $response = $this->actingAsJWT($user)->postJson('/api/resources', [
            'name' => 'Unauthorized Resource',
            'type' => 'equipment'
        ]);

        // ASSERT: Ellenőrizzük az elutasítást
        $response->assertStatus(403)
                 ->assertJson(['message' => 'Nincs jogosultságod erőforrás létrehozására.']);
    }

    public function test_admin_update_resource()
    {
        // ARRANGE: Admin és erőforrás
        $admin = User::factory()->create(['is_admin' => true]);
        $resource = Resource::factory()->create([
            'name' => 'Old Name',
            'available' => true
        ]);

        // ACT: Erőforrás frissítése JWT tokennel
        $response = $this->actingAsJWT($admin)->putJson("/api/resources/{$resource->id}", [
            'name' => 'Updated Name',
            'available' => false
        ]);

        // ASSERT: Ellenőrizzük az eredményt
        $response->assertStatus(200)
                 ->assertJson([
                     'name' => 'Updated Name',
                     'available' => false
                 ]);

        // Ellenőrizzük az adatbázist
        $this->assertDatabaseHas('resources', [
            'id' => $resource->id,
            'name' => 'Updated Name'
        ]);
    }

    public function test_admin_delete_resource()
    {
        // ARRANGE: Admin és törlendő erőforrás
        $admin = User::factory()->create(['is_admin' => true]);
        $resource = Resource::factory()->create();

        // ACT: Erőforrás törlése JWT tokennel
        $response = $this->actingAsJWT($admin)->deleteJson("/api/resources/{$resource->id}");

        // ASSERT: Ellenőrizzük az eredményt
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Erőforrás törölve.']);

        // assertSoftDeleted: ellenőrzi, hogy a deleted_at mező ki van töltve
        $this->assertSoftDeleted('resources', ['id' => $resource->id]);
    }

    public function test_non_admin_cannot_update_resource()
    {
        // ARRANGE: Normál felhasználó és erőforrás
        $user = User::factory()->create(['is_admin' => false]);
        $resource = Resource::factory()->create();

        // ACT: Erőforrás módosítási kísérlете JWT tokennel
        $response = $this->actingAsJWT($user)->putJson("/api/resources/{$resource->id}", [
            'name' => 'Hacked Name'
        ]);

        // ASSERT: Ellenőrizzük az elutasítást
        $response->assertStatus(403)
                 ->assertJson(['message' => 'Nincs jogosultságod erőforrás módosítására.']);
    }

    public function test_unauthenticated_cannot_create_resource()
    {
        // ACT & ASSERT: Próbálunk erőforrást létrehozni autentifikáció nélkül
        $this->postJson('/api/resources', [
            'name' => 'Test',
            'type' => 'room'
        ])->assertStatus(401);
    }
}

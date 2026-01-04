<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Resource;
use App\Models\Reservation;
use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsJWT(User $user)
    {
        $token = JWTAuth::fromUser($user);
        return $this->withHeader('Authorization', 'Bearer ' . $token);
    }

    public function test_user_list_own_reservations()
    {
        // ARRANGE: Felhasználó, erőforrás és foglalások
        $user = User::factory()->create();
        $resource = Resource::factory()->create();
        
        Reservation::factory()->count(2)->create([
            'user_id' => $user->id,
            'resource_id' => $resource->id
        ]);

        // ACT: Foglalások lekérése JWT tokennel
        $response = $this->actingAsJWT($user)->getJson('/api/reservations');

        // ASSERT: Csak a saját foglalásait látja
        $response->assertStatus(200)
                 ->assertJsonCount(2);
    }

    public function test_admin_list_all_reservations()
    {
        // ARRANGE: Admin és többféle foglalás
        $admin = User::factory()->create(['is_admin' => true]);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $resource = Resource::factory()->create();

        Reservation::factory()->create(['user_id' => $user1->id, 'resource_id' => $resource->id]);
        Reservation::factory()->create(['user_id' => $user2->id, 'resource_id' => $resource->id]);

        // ACT: Foglalások lekérése adminként JWT tokennel
        $response = $this->actingAsJWT($admin)->getJson('/api/reservations');

        // ASSERT: Összes foglalást látja
        $response->assertStatus(200)
                 ->assertJsonCount(2);
    }

    public function test_show_own_reservation()
    {
        // ARRANGE: Felhasználó és foglalása
        $user = User::factory()->create();
        $resource = Resource::factory()->create();
        $reservation = Reservation::factory()->create([
            'user_id' => $user->id,
            'resource_id' => $resource->id
        ]);

        // ACT: Saját foglalás megtekintése JWT tokennel
        $response = $this->actingAsJWT($user)->getJson("/api/reservations/{$reservation->id}");

        // ASSERT: Megjeleníti a foglalást
        $response->assertStatus(200)
                 ->assertJsonStructure(['id', 'user_id', 'resource_id', 'start_time', 'end_time', 'status']);
    }

    public function test_user_cannot_view_other_user_reservation()
    {
        // ARRANGE: Két felhasználó és egy foglalás
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $resource = Resource::factory()->create();
        $reservation = Reservation::factory()->create([
            'user_id' => $user2->id,
            'resource_id' => $resource->id
        ]);

        // ACT: Másik felhasználó foglalásának megtekintése JWT tokennel
        $response = $this->actingAsJWT($user1)->getJson("/api/reservations/{$reservation->id}");

        // ASSERT: Elutasítás
        $response->assertStatus(403)
                 ->assertJson(['message' => 'Nincs jogosultságod megtekinteni ezt a foglalást!']);
    }

    public function test_create_reservation()
    {
        // ARRANGE: Felhasználó és erőforrás
        $user = User::factory()->create();
        $resource = Resource::factory()->create();
        
        $startTime = Carbon::now()->addHours(2);
        $endTime = $startTime->copy()->addHours(1);

        // ACT: Foglalás létrehozása JWT tokennel
        $response = $this->actingAsJWT($user)->postJson('/api/reservations', [
            'resource_id' => $resource->id,
            'start_time' => $startTime,
            'end_time' => $endTime
        ]);

        // ASSERT: Foglalás létrejön
        $response->assertStatus(201)
                 ->assertJsonStructure(['id', 'user_id', 'resource_id', 'start_time', 'end_time', 'status'])
                 ->assertJson(['status' => 'pending']);

        // Ellenőrizzük az adatbázist
        $this->assertDatabaseHas('reservations', [
            'user_id' => $user->id,
            'resource_id' => $resource->id
        ]);
    }

    public function test_cannot_create_reservation_in_past()
    {
        // ARRANGE: Felhasználó és erőforrás
        $user = User::factory()->create();
        $resource = Resource::factory()->create();
        
        $startTime = Carbon::now()->subHours(1);
        $endTime = $startTime->copy()->addHours(1);

        // ACT: Múltbeli foglalás kísérlете JWT tokennel
        $response = $this->actingAsJWT($user)->postJson('/api/reservations', [
            'resource_id' => $resource->id,
            'start_time' => $startTime,
            'end_time' => $endTime
        ]);

        // ASSERT: Validációs hiba
        $response->assertStatus(422);
    }

    public function test_user_update_own_reservation()
    {
        // ARRANGE: Felhasználó és foglalása
        $user = User::factory()->create();
        $resource = Resource::factory()->create();
        $reservation = Reservation::factory()->create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => Carbon::now()->addHours(3),
            'end_time' => Carbon::now()->addHours(4)
        ]);

        $newStartTime = Carbon::now()->addHours(5);
        $newEndTime = $newStartTime->copy()->addHours(2);

        // ACT: Foglalás frissítése JWT tokennel
        $response = $this->actingAsJWT($user)->putJson("/api/reservations/{$reservation->id}", [
            'start_time' => $newStartTime,
            'end_time' => $newEndTime
        ]);

        // ASSERT: Foglalás frissül (de a status nem változhat)
        $response->assertStatus(200);

        // Frissítjük az objektumot az adatbázisból
        $reservation->refresh();
        
        // Ellenőrizzük, hogy az idők frissültek (ISO formátumban)
        $this->assertEquals($newStartTime->format('Y-m-d H:i'), $reservation->start_time->format('Y-m-d H:i'));
        $this->assertEquals($newEndTime->format('Y-m-d H:i'), $reservation->end_time->format('Y-m-d H:i'));
    }

    public function test_admin_can_change_reservation_status()
    {
        // ARRANGE: Admin és foglalás
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $resource = Resource::factory()->create();
        $reservation = Reservation::factory()->create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'status' => 'pending'
        ]);

        // ACT: Status módosítása adminként JWT tokennel
        $response = $this->actingAsJWT($admin)->putJson("/api/reservations/{$reservation->id}", [
            'status' => 'approved'
        ]);

        // ASSERT: Status megváltozik
        $response->assertStatus(200)
                 ->assertJson(['status' => 'approved']);
    }

    public function test_user_cannot_change_reservation_status()
    {
        // ARRANGE: Felhasználó és foglalása
        $user = User::factory()->create();
        $resource = Resource::factory()->create();
        $reservation = Reservation::factory()->create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'status' => 'pending'
        ]);

        // ACT: Status módosítási kísérlете JWT tokennel
        $response = $this->actingAsJWT($user)->putJson("/api/reservations/{$reservation->id}", [
            'status' => 'approved'
        ]);

        // ASSERT: Status nem változik
        $response->assertStatus(200);
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'pending'
        ]);
    }

    public function test_delete_reservation()
    {
        // ARRANGE: Felhasználó és foglalása
        $user = User::factory()->create();
        $resource = Resource::factory()->create();
        $reservation = Reservation::factory()->create([
            'user_id' => $user->id,
            'resource_id' => $resource->id
        ]);

        // ACT: Foglalás törlése JWT tokennel
        $response = $this->actingAsJWT($user)->deleteJson("/api/reservations/{$reservation->id}");

        // ASSERT: Foglalás törlődik
        $response->assertStatus(200);

        // assertSoftDeleted: ellenőrzi, hogy a deleted_at mező ki van töltve
        $this->assertSoftDeleted('reservations', ['id' => $reservation->id]);
    }

    public function test_unauthenticated_cannot_create_reservation()
    {
        // ACT & ASSERT: Próbálunk foglalást létrehozni autentifikáció nélkül
        $this->postJson('/api/reservations', [
            'resource_id' => 1,
            'start_time' => Carbon::now()->addHours(1),
            'end_time' => Carbon::now()->addHours(2)
        ])->assertStatus(401);
    }
}

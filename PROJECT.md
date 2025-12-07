# Reservation System - Projekt Dokumentáció

## Projekt Áttekintése

base_url: http://127.0.0.1/reservationSystem/public/api vagy http://127.0.0.1:8000/api

A **Reservation System** egy Laravel-alapú API alkalmazás, amely lehetővé teszi a felhasználók számára erőforrások foglalását (például meetingtermek, felszerelések). Az alkalmazás felhasználó-autentifikáción alapul és szerep-alapú hozzáférés-vezérlést (RBAC) alkalmaz adminisztrációs funkciók megvédésére.

## Technológia Stack

- **Backend Framework**: Laravel 11
- **Autentifikáció**: Laravel Sanctum (API tokenek)
- **Adatbázis**: MySQL
- **Testing**: PHPUnit
- **Package Manager**: Composer, npm

## Projekt Szerkezete

```
reservationSystem/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── AuthController.php       # Regisztráció, bejelentkezés, kijelentkezés
│   │       ├── UserController.php       # Felhasználó profil kezelés (admin funkciók)
│   │       ├── ResourceController.php   # Erőforrás CRUD operációk
│   │       └── ReservationController.php # Foglalás CRUD operációk
│   ├── Models/
│   │   ├── User.php                    # Felhasználó model
│   │   ├── Resource.php                # Erőforrás model
│   │   └── Reservation.php             # Foglalás model
│   └── Providers/
│       └── AppServiceProvider.php
├── database/
│   ├── factories/                      # Factory-k tesztadatok generálásához
│   ├── migrations/                     # Adatbázis sémamigráció
│   └── seeders/                        # Adatbázis seed-ek
├── routes/
│   └── api.php                         # API végpontok definíciója
├── tests/
│   ├── Feature/
│   │   ├── AuthTest.php               # Auth endpoint tesztek
│   │   ├── UserTest.php               # User endpoint tesztek
│   │   ├── ResourceTest.php           # Resource endpoint tesztek
│   │   └── ReservationTest.php        # Reservation endpoint tesztek
│   └── Unit/
└── resources/
    ├── css/
    ├── js/
    └── views/
```

## Adatmodell

### User (Felhasználó)
- `id`: Elsődleges kulcs
- `name`: Felhasználó neve
- `email`: E-mail cím (egyedi)
- `password`: Hash-elt jelszó
- `phone`: Telefonszám (opcionális)
- `is_admin`: Admin jogok jelzője (boolean)
- `created_at`, `updated_at`: Időbélyegek

### Resource (Erőforrás)
- `id`: Elsődleges kulcs
- `name`: Erőforrás neve
- `type`: Erőforrás típusa (pl. "room", "equipment")
- `description`: Erőforrás leírása (opcionális)
- `available`: Elérhetőség (boolean)
- `created_at`, `updated_at`: Időbélyegek

### Reservation (Foglalás)
- `id`: Elsődleges kulcs
- `user_id`: Foglalást készítő felhasználó (FK)
- `resource_id`: Foglalt erőforrás (FK)
- `start_time`: Foglalás kezdete (datetime)
- `end_time`: Foglalás vége (datetime)
- `status`: Foglalás státusza (pending, approved, rejected, cancelled)
- `created_at`, `updated_at`: Időbélyegek

## API Végpontok

### Autentifikáció

#### `POST /api/register` - Regisztráció
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "SecurePassword123",
  "phone": "+36201234567"
}
```
**Válasz**: `201 Created` - Új felhasználó és üzenet

#### `POST /api/login` - Bejelentkezés
```json
{
  "email": "john@example.com",
  "password": "SecurePassword123"
}
```
**Válasz**: `200 OK`
```json
{
  "access_token": "token_here",
  "token_type": "Bearer"
}
```

#### `POST /api/logout` - Kijelentkezés (autentifikált)
**Válasz**: `200 OK`

---

### Felhasználó Kezelés

#### `GET /api/users/me` - Aktuális profil (autentifikált)
**Válasz**: `200 OK` - Aktuális felhasználó adatai

#### `PUT /api/users/me` - Profil frissítése (autentifikált)
```json
{
  "name": "New Name",
  "email": "newemail@example.com",
  "password": "NewPassword123",
  "phone": "+36209876543"
}
```

#### `GET /api/users` - Összes felhasználó listázása (admin csak)
**Válasz**: `200 OK` - Felhasználók tömbje

#### `GET /api/users/{id}` - Konkrét felhasználó (admin csak)
**Válasz**: `200 OK` - Felhasználó adatai

#### `DELETE /api/users/{id}` - Felhasználó törlése (admin csak)
**Válasz**: `200 OK`

---

### Erőforrások

#### `GET /api/resources` - Összes erőforrás listázása (autentifikált)
**Válasz**: `200 OK` - Erőforrások tömbje

#### `GET /api/resources/{id}` - Konkrét erőforrás (autentifikált)
**Válasz**: `200 OK` - Erőforrás adatai

#### `POST /api/resources` - Erőforrás létrehozása (admin csak)
```json
{
  "name": "Meeting Room A",
  "type": "room",
  "description": "Large conference room",
  "available": true
}
```

#### `PUT /api/resources/{id}` - Erőforrás módosítása (admin csak)
```json
{
  "name": "Updated Name",
  "available": false
}
```

#### `DELETE /api/resources/{id}` - Erőforrás törlése (admin csak)

---

### Foglalások

#### `GET /api/reservations` - Foglalások listázása (autentifikált)
- Normál felhasználó: csak saját foglalásait látja
- Admin: összes foglalást lát

#### `GET /api/reservations/{id}` - Konkrét foglalás (autentifikált)
- Normál felhasználó: csak saját foglalásait érheti el
- Admin: bármely foglalást megtekinthet

#### `POST /api/reservations` - Foglalás létrehozása (autentifikált)
```json
{
  "resource_id": 1,
  "start_time": "2025-12-10T14:00:00",
  "end_time": "2025-12-10T15:00:00"
}
```
**Válasz**: `201 Created` - Új foglalás (`pending` státusszal)

#### `PUT /api/reservations/{id}` - Foglalás módosítása (autentifikált)
```json
{
  "resource_id": 2,
  "start_time": "2025-12-11T14:00:00",
  "end_time": "2025-12-11T15:00:00",
  "status": "approved"
}
```
- Normál felhasználó: nem módosíthatja a `status` mezőt
- Admin: módosíthat mindent

#### `DELETE /api/reservations/{id}` - Foglalás törlése (autentifikált)

---

## Autentifikáció és Jogosultságok

### Token-alapú Autentifikáció
- Minden autentifikált endpoint `Authorization: Bearer {token}` header-t igényel
- A token bejelentkezéskor jön vissza
- A tokeneket a `personal_access_tokens` táblában tároljuk

### Szerepek (Roles)
1. **Normál felhasználó** (`is_admin = false`)
   - Saját profil megtekintése és módosítása
   - Erőforrások megtekintése
   - Saját foglalások CRUD
   - Saját foglalások státusza nem módosítható

2. **Administrator** (`is_admin = true`)
   - Összes felhasználó kezelése
   - Erőforrások teljes kezelése
   - Összes foglalás megtekintése és kezelése
   - Foglalás státuszának módosítása

## Tesztek

### Teszt Lefedettség

#### AuthTest.php (4 teszt)
- `test_ping_endpoint_returns_ok()` - API állapot ellenőrzés
- `test_register_creates_user()` - Regisztráció funkció
- `test_login_with_valid_credentials()` - Sikeres bejelentkezés
- `test_login_with_invalid_credentials()` - Hibás bejelentkezés

#### UserTest.php (7 teszt)
- `test_get_current_user_profile()` - Profil lekérés
- `test_update_user_profile()` - Profil módosítás
- `test_admin_list_all_users()` - Admin - összes felhasználó
- `test_non_admin_cannot_list_users()` - Jogosultság ellenőrzés
- `test_admin_show_specific_user()` - Admin - konkrét felhasználó
- `test_admin_delete_user()` - Admin - felhasználó törlés
- `test_unauthenticated_cannot_access_user_endpoints()` - Autentifikáció ellenőrzés

#### ResourceTest.php (8 teszt)
- `test_list_all_resources()` - Erőforrások listázása
- `test_show_specific_resource()` - Konkrét erőforrás
- `test_admin_create_resource()` - Admin - erőforrás létrehozás
- `test_non_admin_cannot_create_resource()` - Jogosultság ellenőrzés
- `test_admin_update_resource()` - Admin - erőforrás módosítás
- `test_admin_delete_resource()` - Admin - erőforrás törlés
- `test_non_admin_cannot_update_resource()` - Jogosultság ellenőrzés
- `test_unauthenticated_cannot_create_resource()` - Autentifikáció ellenőrzés

#### ReservationTest.php (11 teszt)
- `test_user_list_own_reservations()` - Felhasználó saját foglalásai
- `test_admin_list_all_reservations()` - Admin összes foglalása
- `test_show_own_reservation()` - Saját foglalás megtekintés
- `test_user_cannot_view_other_user_reservation()` - Jogosultság ellenőrzés
- `test_create_reservation()` - Foglalás létrehozás
- `test_cannot_create_reservation_in_past()` - Múltbeli foglalás tilalmazás
- `test_user_update_own_reservation()` - Felhasználó foglalás módosítás
- `test_admin_can_change_reservation_status()` - Admin status módosítás
- `test_user_cannot_change_reservation_status()` - Jogosultság ellenőrzés
- `test_delete_reservation()` - Foglalás törlés
- `test_unauthenticated_cannot_create_reservation()` - Autentifikáció ellenőrzés

**Összesen: 32 teszt, 100% pass rate**

### Tesztek futtatása

```bash
# Összes teszt futtatása
php artisan test

# Konkrét teszt fájl
php artisan test tests/Feature/AuthTest.php

# Konkrét teszt metódus
php artisan test tests/Feature/AuthTest.php --filter test_register_creates_user
```

## Telepítés és Futtatás

### Előfeltételek
- PHP 8.2+
- MySQL 8.0+
- Composer
- Node.js (npm)

### Lépések

1. **Projekt klónozása**
   ```bash
   git clone <repository-url>
   cd reservationSystem
   ```

2. **Composer függőségek telepítése**
   ```bash
   composer install
   ```

3. **NPM függőségek telepítése**
   ```bash
   npm install
   ```

4. **Env fájl konfigurálása**
   ```bash
   cp .env.example .env
   ```
   Állítsa be az adatbázis adatait a `.env` fájlban:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=reservation_system
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Alkalmazás kulcs generálása**
   ```bash
   php artisan key:generate
   ```

6. **Adatbázis migráció**
   ```bash
   php artisan migrate
   ```

7. **Adatbázis seed-elés (opcionális)**
   ```bash
   php artisan db:seed
   ```

8. **Development szerver futtatása**
   ```bash
   php artisan serve
   ```
   Az API a `http://localhost:8000` címen érhető el.


## Fejlesztési Munkafolyamat

### Model → Migration → Controller → Route → Test

1. **Model létrehozása**: `php artisan make:model ModelName -mfsc`
   - `-m`: Migration
   - `-f`: Factory
   - `-s`: Seeder
   - `-c`: Controller

2. **Migration szerkesztése**: `database/migrations/`

3. **Controller metódusok**: `app/Http/Controllers/`

4. **Route-ok**: `routes/api.php`

5. **Tesztek írása**: `tests/Feature/`

6. **Tesztek futtatása**: `php artisan test`

## Biztonsági Jellegzetességek

✅ **Jelszó Hash-elés**: Bcrypt hash-elés `Hash::make()`
✅ **Token Autentifikáció**: Laravel Sanctum API tokenek
✅ **Role-Based Access Control**: Admin és felhasználó szerepek
✅ **Input Validáció**: Request validáció minden endpointon
✅ **SQL Injekció Védelem**: Eloquent ORM paraméteres lekérdezések
✅ **Jogosultság Ellenőrzés**: Endpoint-szintű autentifikáció

## Gyakori Kérdések

### Hogyan lehet admin felhasználót létrehozni?
Az adatbázisban közvetlenül módosítsa az `is_admin` mező értékét 1-re, vagy használja az adatbázis seed-et.

### Hogyan működik a foglalás státusza?
- `pending`: Újabb foglalás, még nem jóváhagyva
- `approved`: Admin által jóváhagyva
- `rejected`: Admin által elutasítva
- `cancelled`: Felhasználó által visszavonva



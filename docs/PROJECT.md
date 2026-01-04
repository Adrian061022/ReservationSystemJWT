# Reservation System - Projekt Dokumentáció

## Projekt Áttekintése

A **Reservation System** egy Laravel-alapú REST API alkalmazás, amely lehetővé teszi a felhasználók számára erőforrások foglalását (például meetingtermek, felszerelések, berendezések). Az alkalmazás felhasználó-autentifikáción alapul (JWT tokenek) és szerep-alapú hozzáférés-vezérlést (RBAC) alkalmaz az adminisztrációs funkciók megvédésére.

**Base URL:** `http://127.0.0.1:8000/api` (local development)
vagy `http://localhost/reservationSystem/public/api` (XAMPP)

## Technológia Stack

- **Backend Framework**: Laravel 11
- **Autentifikáció**: JWT (tymon/jwt-auth)
- **Adatbázis**: MySQL 8.0+
- **Testing**: PHPUnit (Feature tesztek)
- **Package Manager**: Composer, npm
- **PHP verzió**: 8.2+

## Adatbázis Terv

```
+---------------------+       +-------------------+        +---------------+
|        users        |       |   reservations    |        |  resources    |
+---------------------+       +-------------------+        +---------------+
| id (PK)             |1__    | id (PK)           |     __1| id (PK)       |
| name                |   \__N| user_id (FK)      |    /   | name          |
| email (unique)      |       | resource_id (FK)  |M__/    | type          |
| password            |       | start_time        |        | description   |
| phone (nullable)    |       | end_time          |        | available     |
| is_admin (boolean)  |       | status            |        | created_at    |
| created_at          |       | created_at        |        | updated_at    |
| updated_at          |       | updated_at        |        | deleted_at    |
| deleted_at          |       | deleted_at        |        +---------------+
+---------------------+       +-------------------+
```

**Megjegyzés:** Mindhárom tábla használja a **Soft Delete** funkciót:
- A `deleted_at` mező `NULL` értékű, ha a rekord aktív
- Törléskor a `deleted_at` mező kitöltődik az aktuális timestamp-pel
- A törölt rekordok alapértelmezetten nem jelennek meg a lekérdezésekben


## Projekt Szerkezete

```
reservationSystem/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── AuthController.php          # Regisztráció, bejelentkezés, kijelentkezés, refresh
│   │       ├── UserController.php          # Felhasználó profil kezelés (admin funkciók)
│   │       ├── ResourceController.php      # Erőforrás CRUD operációk
│   │       └── ReservationController.php   # Foglalás CRUD operációk
│   ├── Models/
│   │   ├── User.php                        # Felhasználó model (JWT)
│   │   ├── Resource.php                    # Erőforrás model
│   │   └── Reservation.php                 # Foglalás model
│   └── Providers/
│       └── AppServiceProvider.php
├── database/
│   ├── factories/                          # Factory-k tesztadatok generálásához
│   │   ├── UserFactory.php
│   │   ├── ResourceFactory.php
│   │   └── ReservationFactory.php
│   ├── migrations/                         # Adatbázis sémamigráció
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 2025_12_04_080202_create_resources_table.php
│   │   └── 2025_12_04_080404_create_reservations_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── UserSeeder.php
│       ├── ResourceSeeder.php
│       └── ReservationSeeder.php
├── routes/
│   └── api.php                             # REST API végpontok definíciója
├── tests/
│   ├── Feature/
│   │   ├── AuthTest.php                    # Autentifikáció tesztek
│   │   ├── UserTest.php                    # Felhasználó kezelés tesztek
│   │   ├── ResourceTest.php                # Erőforrás kezelés tesztek
│   │   ├── ReservationTest.php             # Foglalás kezelés tesztek
│   │   └── ExampleTest.php
│   └── Unit/
├── config/
│   ├── auth.php                            # Autentifikáció konfiguráció (JWT guard)
│   └── jwt.php                             # JWT konfiguráció
├── .env                                    # Környezeti változók (JWT_SECRET)
├── composer.json                           # PHP függőségek (tymon/jwt-auth)
├── phpunit.xml                             # PHPUnit konfiguráció
└── README.md
```

## Projekt Telepítése és Beállítása

### Előfeltételek
- PHP 8.2 vagy újabb
- Composer
- MySQL 8.0 vagy újabb
- XAMPP (opcionális, local fejlesztéshez)

### Telepítési Lépések

1. **Projekt letöltése**
```bash
git clone <repository-url>
cd reservationSystem
```

2. **Függőségek telepítése**
```bash
composer install
npm install
```

3. **JWT csomag telepítése**
```bash
composer require tymon/jwt-auth
```

4. **Környezeti változók beállítása**
```bash
cp .env.example .env
php artisan key:generate
```

5. **JWT konfigurációs fájl publikálása és titkos kulcs generálása**
```bash
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret
```

Ez beállítja a `JWT_SECRET` kulcsot a `.env` fájlban.

6. **Adatbázis beállítása**

Szerkeszd a `.env` fájlt:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=reservation_system
DB_USERNAME=root
DB_PASSWORD=
```

7. **Adatbázis migráció és seedek futtatása**
```bash
php artisan migrate:fresh --seed
```

8. **Fejlesztői szerver indítása**
```bash
php artisan serve
```

Az API elérhető: `http://127.0.0.1:8000/api`

### JWT Konfiguráció

A JWT autentikáció konfigurációja a `config/auth.php` fájlban található:

```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'api' => [
        'driver' => 'jwt',  // JWT guard használata az API-hoz
        'provider' => 'users',
    ],
],
```

A `User` model implementálja a `JWTSubject` interface-t:

```php
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
```

### JWT Token Jellemzők
- **Érvényességi idő**: 60 perc (3600 mp)
- **Token formátum**: `header.payload.signature` (3 rész)
- **Refresh mechanizmus**: `/api/refresh` endpoint
- **Blacklist**: Logout-nál a token invalidálódik

## Adatmodell

### User (Felhasználó)
```php
{
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "password": "hashed_password", // Bcrypt hash
    "phone": "+36201234567", // nullable
    "is_admin": false,
    "created_at": "2025-12-04T10:00:00Z",
    "updated_at": "2025-12-04T10:00:00Z",
    "deleted_at": null // Soft delete: null ha nem törölt, timestamp ha törölt
}
```

**Fontos jellemzők:**
- **SoftDeletes trait**: A User model használja a `SoftDeletes` trait-et
- **deleted_at mező**: Törléskor nem törlődik fizikailag, csak a `deleted_at` mező töltődik ki timestamp-pel
- **Lekérdezések**: A törölt felhasználók alapértelmezetten nem jelennek meg a lekérdezésekben
- **JWT autentikáció**: Implementálja a `JWTSubject` interface-t

### Resource (Erőforrás)
```php
{
    "id": 1,
    "name": "Meeting Room A",
    "type": "room",
    "description": "Large conference room for meetings",
    "available": true,
    "created_at": "2025-12-04T10:00:00Z",
    "updated_at": "2025-12-04T10:00:00Z",
    "deleted_at": null // Soft delete: null ha nem törölve, timestamp ha törölve
}
```

**Fontos jellemzők:**
- **SoftDeletes trait**: A Resource model használja a `SoftDeletes` trait-et
- **deleted_at mező**: Törléskor nem törlődik fizikailag, csak a `deleted_at` mező töltődik ki timestamp-pel
- **Lekérdezések**: A törölt erőforrások alapértelmezetten nem jelennek meg a lekérdezésekben

### Reservation (Foglalás)
```php
{
    "id": 1,
    "user_id": 5,
    "resource_id": 1,
    "start_time": "2025-12-10T14:00:00Z",
    "end_time": "2025-12-10T15:00:00Z",
    "status": "pending", // pending, approved, rejected, cancelled
    "created_at": "2025-12-04T12:00:00Z",
    "updated_at": "2025-12-04T12:00:00Z",
    "deleted_at": null // Soft delete: null ha nem törölve, timestamp ha törölve
}
```

**Fontos jellemzők:**
- **SoftDeletes trait**: A Reservation model használja a `SoftDeletes` trait-et
- **deleted_at mező**: Törléskor nem törlődik fizikailag, csak a `deleted_at` mező töltődik ki timestamp-pel
- **Lekérdezések**: A törölt foglalások alapértelmezetten nem jelennek meg a lekérdezésekben
- **Státusz kezelés**: A `status` mező lehetséges értékei: pending, approved, rejected, cancelled

## API Végpontok

### Nem védett végpontok:
- **GET** `/hello` - API teszteléshez
- **POST** `/register` - Regisztrációhoz (visszaad JWT tokent)
- **POST** `/login` - Bejelentkezéshez (visszaad JWT tokent)

<img width="538" height="131" alt="image" src="https://github.com/user-attachments/assets/f8dbb8d2-7c56-42d0-807c-4e988b251b73" />

### Védett végpontok (JWT token szükséges):
- **POST** `/logout` - Kijelentkezés (token invalidálás)
- **POST** `/refresh` - JWT token frissítése
- **GET** `/users/me` - Saját profil lekérése
- **PUT** `/users/me` - Saját profil módosítása
- **GET** `/users` - Összes user (admin)
- **GET** `/users/{id}` - User részletei (admin)
- **DELETE** `/users/{id}` - User törlése (admin)
- **GET** `/resources` - Összes erőforrás
- **GET** `/resources/{id}` - Erőforrás részletei
- **POST** `/resources` - Új erőforrás (admin)
- **PUT** `/resources/{id}` - Erőforrás módosítása (admin)
- **DELETE** `/resources/{id}` - Erőforrás törlése (admin)
- **GET** `/reservations` - Összes foglalás
- **GET** `/reservations/{id}` - Foglalás részletei
- **POST** `/reservations` - Új foglalás
- **PUT** `/reservations/{id}` - Foglalás módosítása
- **DELETE** `/reservations/{id}` - Foglalás törlése




### Hibák kezelése:
- **400 Bad Request**: A kérés hibás formátumú vagy hiányoznak a szükséges mezők
- **401 Unauthorized**: Érvénytelen vagy hiányzó token
- **403 Forbidden**: A felhasználó nem rendelkezik megfelelő jogosultságokkal
- **404 Not Found**: A kért erőforrás nem található
- **409 Conflict**: Az erőforrás már létezik vagy egy már bekövetkezett állapotot próbálnak ismét végrehajtani
- **422 Unprocessable Entity**: Validációs hibák a kérésben

---

## Felhasználókezelés

### **POST** `/register` - Regisztráció

Új felhasználó regisztrálása. Az új felhasználók alapértelmezetten normál felhasználók (`is_admin = false`).
<img width="847" height="271" alt="image" src="https://github.com/user-attachments/assets/834e3eb2-5524-457e-a8da-d34c109f9114" />



**Kérés Törzse:**
```json
{
    "name": "Test User",
    "email": "test2@example.com",
    "password": "password123",
    "phone": "1234567890"
}
```

**Válasz (sikeres regisztráció):** `201 Created`
```json
{
    "message": "User registered successfully",
    "user": {
        "name": "Test User",
        "email": "test2@example.com",
        "phone": "1234567890",
        "updated_at": "2026-01-04T22:01:25.000000Z",
        "created_at": "2026-01-04T22:01:25.000000Z",
        "id": 23
    },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBpL3JlZ2lzdGVyIiwiaWF0IjoxNzY3NTY0MDg1LCJleHAiOjE3Njc1Njc2ODUsIm5iZiI6MTc2NzU2NDA4NSwianRpIjoiak5qdU9xTlpVd1B1aFRMYyIsInN1YiI6IjIzIiwicHJ2IjoiMjNiZDVjODk0OWY2MDBhZGIzOWU3MDFjNDAwODcyZGI3YTU5NzZmNyJ9.E8DoGlQm19tUOj4DjM0uVTHo-3pDOh3uReVl5G5sMrs",
    "token_type": "bearer",
    "expires_in": 3600
}
```

**Válasz (e-mail már foglalt):** `422 Unprocessable Entity`
```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

---

### **POST** `/login` - Bejelentkezés

Bejelentkezés e-mail címmel és jelszóval.
<img width="836" height="223" alt="image" src="https://github.com/user-attachments/assets/60ed20cb-dcdc-4616-b3a5-1f93c5655492" />

**Kérés Törzse:**
```json
{
    "email": "admin@example.com",
    "password": "admin123"
}
```

**Válasz (sikeres bejelentkezés):** `200 OK`
```json
{
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBpL2xvZ2luIiwiaWF0IjoxNzY3NTYyOTk5LCJleHAiOjE3Njc1NjY1OTksIm5iZiI6MTc2NzU2Mjk5OSwianRpIjoiRUNHMkhjQnVIc2ZaZ2Z1QiIsInN1YiI6IjIxIiwicHJ2IjoiMjNiZDVjODk0OWY2MDBhZGIzOWU3MDFjNDAwODcyZGI3YTU5NzZmNyJ9.Sc_UOIg5qcGrFXyzjou1643TDF6Q47clf1nJRelL-Bc",
    "token_type": "bearer",
    "expires_in": 3600,
    "user": {
        "id": 21,
        "name": "Admin User",
        "email": "admin@example.com",
        "phone": "+1.458.537.2618",
        "is_admin": 1,
        "email_verified_at": "2026-01-04T21:42:28.000000Z",
        "created_at": "2026-01-04T21:42:28.000000Z",
        "updated_at": "2026-01-04T21:42:28.000000Z",
        "deleted_at": null
    }
}
```

**Válasz (hibás bejelentkezés):** `401 Unauthorized`
```json
{
  "message": "Invalid credentials"
}
```

---

> Innen kezdve minden végpont **autentifikált**, tehát a kérés `Authorization` headerében meg kell adni a JWT tokent:
> 
> `Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...`
>
> **JWT Token jellemzők:**
> - 3 részből áll (header.payload.signature)
> - Tartalmazza az érvényességi időt (expires_in: 3600 mp = 1 óra)
> - Token frissíthető a `/refresh` endpoint-tal
> - Logout-nál invalidálódik (blacklist)

---
<img width="731" height="525" alt="image" src="https://github.com/user-attachments/assets/9a32e9b8-06f1-4730-885d-e69868def9ae" />


### **POST** `/logout` - Kijelentkezés

A jelenlegi autentikált felhasználó kijelentkeztetése és JWT tokenjének invalidálása.
<img width="848" height="104" alt="image" src="https://github.com/user-attachments/assets/e6df9f0c-e262-4711-841f-26261210da36" />



**Válasz (sikeres kijelentkezés):** `200 OK`
```json
{
  "message": "Successfully logged out"
}
```

---

### **POST** `/refresh` - JWT Token Frissítése

Új JWT token generálása a régi token alapján, anélkül hogy újra be kellene jelentkezni.
<img width="831" height="117" alt="image" src="https://github.com/user-attachments/assets/64e26782-e846-4d0a-8789-ce8b7632702f" />


**Válasz:** `200 OK`
```json
{
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBpL3JlZnJlc2giLCJpYXQiOjE3Njc1NjQzODIsImV4cCI6MTc2NzU2Nzk5MCwibmJmIjoxNzY3NTY0MzkwLCJqdGkiOiJBTTJ1Z2RidmFnRWlTWjVCIiwic3ViIjoiMjEiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3In0.pJwhMQe21zWAaG1zMj0LHqpSbyhEAQZTfUD0QQ-Nc-8",
    "token_type": "bearer",
    "expires_in": 3600,
    "user": {
        "id": 21,
        "name": "Admin User",
        "email": "admin@example.com",
        "phone": "+1.458.537.2618",
        "is_admin": 1,
        "email_verified_at": "2026-01-04T21:42:28.000000Z",
        "created_at": "2026-01-04T21:42:28.000000Z",
        "updated_at": "2026-01-04T21:42:28.000000Z",
        "deleted_at": null
    }
}
```

---

### **GET** `/users/me` - Aktuális Profil

Saját felhasználói profil adatainak lekérése.
<img width="828" height="120" alt="image" src="https://github.com/user-attachments/assets/20f3b7b0-5435-4ff1-80bd-36d54181a1f7" />


**Válasz:** `200 OK`
```json
{
    "id": 21,
    "name": "Admin User",
    "email": "admin@example.com",
    "phone": "+1.458.537.2618",
    "is_admin": 1,
    "email_verified_at": "2026-01-04T21:42:28.000000Z",
    "created_at": "2026-01-04T21:42:28.000000Z",
    "updated_at": "2026-01-04T21:42:28.000000Z",
    "deleted_at": null
}
```

---

### **PUT** `/users/me` - Profil Frissítése

Saját felhasználói adatok módosítása (név, e-mail, jelszó, telefonszám).
<img width="828" height="109" alt="image" src="https://github.com/user-attachments/assets/f6311195-2da6-4245-807c-20967f355500" />




**Kérés Törzse:**
```json
{
    "name": "Updated Name",
    "phone": "9876543210"
}
```

**Válasz (sikeres frissítés):** `200 OK`
```json

    "id": 21,
    "name": "Updated Name",
    "email": "admin@example.com",
    "phone": "9876543210",
    "is_admin": 1,
    "email_verified_at": "2026-01-04T21:42:28.000000Z",
    "created_at": "2026-01-04T21:42:28.000000Z",
    "updated_at": "2026-01-04T22:08:01.000000Z",
    "deleted_at": null
}
```

**Válasz (validációs hiba):** `422 Unprocessable Entity`

---

### **GET** `/users` - Összes Felhasználó Listázása (Admin Csak)

Az összes felhasználó adatainak lekérése adminisztratív célokra.
<img width="836" height="127" alt="image" src="https://github.com/user-attachments/assets/fc854d0a-b985-4eef-a44f-6fb9a07ce6e1" />


**Válasz:** `200 OK`
```json
[
   {
        "id": 1,
        "name": "Wallace Lynch",
        "email": "royal95@example.net",
        "phone": "(815) 623-6624",
        "is_admin": 0,
        "email_verified_at": "2026-01-04T21:38:35.000000Z",
        "created_at": "2026-01-04T21:38:36.000000Z",
        "updated_at": "2026-01-04T21:38:36.000000Z",
        "deleted_at": null
    },
    {
        "id": 2,
        "name": "Clementina Moen",
        "email": "andreane.turner@example.org",
        "phone": "+1 (229) 450-5078",
        "is_admin": 0,
        "email_verified_at": "2026-01-04T21:38:36.000000Z",
        "created_at": "2026-01-04T21:38:36.000000Z",
        "updated_at": "2026-01-04T21:38:36.000000Z",
        "deleted_at": null
    },
]
```

**Válasz (nem admin):** `403 Forbidden`
```json
{
  "message": "Forbidden"
}
```

---

### **GET** `/users/{id}` - Konkrét Felhasználó Megtekintése (Admin Csak)

Bármely felhasználó adatainak lekérése az ID alapján.
<img width="855" height="148" alt="image" src="https://github.com/user-attachments/assets/eccaaaaf-8852-45cb-a0bd-9e382e9c6ab4" />

**Válasz:** `200 OK`
```json
{
    "id": 1,
    "name": "Wallace Lynch",
    "email": "royal95@example.net",
    "phone": "(815) 623-6624",
    "is_admin": 0,
    "email_verified_at": "2026-01-04T21:38:35.000000Z",
    "created_at": "2026-01-04T21:38:36.000000Z",
    "updated_at": "2026-01-04T21:38:36.000000Z",
    "deleted_at": null
}
```

**Válasz (felhasználó nem található):** `404 Not Found`

**Válasz (nem admin):** `403 Forbidden`

---

### **DELETE** `/users/{id}` - Felhasználó Törlése (Admin Csak)

Felhasználó (soft delete) törlése az adatbázisból.
<img width="848" height="162" alt="image" src="https://github.com/user-attachments/assets/a8e37e2a-d157-47da-9779-34094c49338e" />

**Válasz (sikeres törlés):** `200 OK`
```json
{
  "message": "User deleted"
}
```

**Válasz (felhasználó nem található):** `404 Not Found`

---

## Erőforrások Kezelése

### **GET** `/resources` - Összes Erőforrás Listázása

Az összes elérhető erőforrás listájának lekérése (autentifikált felhasználók).
<img width="860" height="147" alt="image" src="https://github.com/user-attachments/assets/6395df56-b658-447d-85c1-4c35926d1e92" />

**Válasz:** `200 OK`
```json
[
    {
        "id": 1,
        "name": "Meeting Room A",
        "type": "room",
        "description": "Large conference room",
        "available": true,
        "created_at": "2025-12-04T10:30:00Z",
        "updated_at": "2025-12-04T10:30:00Z"
    },
    {
        "id": 2,
        "name": "Projektor",
        "type": "equipment",
        "description": "4K Projector",
        "available": true,
        "created_at": "2025-12-04T10:30:00Z",
        "updated_at": "2025-12-04T10:30:00Z"
    }
]
```

---

### **GET** `/resources/{id}` - Konkrét Erőforrás Megtekintése

Egy específikus erőforrás részleteinak lekérése.
<img width="850" height="160" alt="image" src="https://github.com/user-attachments/assets/db14553d-3a6e-4d93-b66a-f1d96732a1bc" />

**Válasz:** `200 OK`
```json
{
    "id": 1,
    "name": "Meeting Room A",
    "type": "room",
    "description": "Large conference room",
    "available": true,
    "created_at": "2025-12-04T10:30:00Z",
    "updated_at": "2025-12-04T10:30:00Z"
}
```

**Válasz (nem található):** `404 Not Found`

---

### **POST** `/resources` - Erőforrás Létrehozása (Admin Csak)

Új erőforrás hozzáadása a rendszerhez.
<img width="823" height="118" alt="image" src="https://github.com/user-attachments/assets/0663e2fa-f348-4ce6-b3ca-c11dd0502294" />

**Kérés Törzse:**
```json
{
    "name": "Meeting Room B",
    "type": "room",
    "description": "Small conference room",
    "available": true
}
```

**Válasz:** `201 Created`
```json
{
    "id": 3,
    "name": "Meeting Room B",
    "type": "room",
    "description": "Small conference room",
    "available": true,
    "created_at": "2025-12-04T11:00:00Z",
    "updated_at": "2025-12-04T11:00:00Z"
}
```

**Válasz (nem admin):** `403 Forbidden`
```json
{
  "message": "Nincs jogosultságod erőforrás létrehozására."
}
```

---

### **PUT** `/resources/{id}` - Erőforrás Módosítása (Admin Csak)

Meglévő erőforrás adatainak frissítése.
<img width="836" height="120" alt="image" src="https://github.com/user-attachments/assets/a68949db-8c57-468b-8711-ee647ebf61a8" />


**Kérés Törzse:**
```json
{
    "name": "Updated Room Name",
    "available": false
}
```

**Válasz:** `200 OK`
```json
{
    "id": 3,
    "name": "Updated Room Name",
    "type": "room",
    "description": "Small conference room",
    "available": false,
    "created_at": "2025-12-04T11:00:00Z",
    "updated_at": "2025-12-04T11:15:00Z"
}
```

**Válasz (nem admin):** `403 Forbidden`

---

### **DELETE** `/resources/{id}` - Erőforrás Törlése (Admin Csak)

Erőforrás eltávolítása a rendszerből.
<img width="839" height="119" alt="image" src="https://github.com/user-attachments/assets/e9ab3299-4068-4d96-b550-644f5f98d4e7" />


**Válasz:** `200 OK`
```json
{
  "message": "Erőforrás törölve."
}
```

**Válasz (nem admin):** `403 Forbidden`

---

## Foglalások Kezelése

### **GET** `/reservations` - Foglalások Listázása

- **Normál felhasználó**: Csak saját foglalásait látja
- **Admin**: Összes foglalást lát
- <img width="859" height="163" alt="image" src="https://github.com/user-attachments/assets/ead4d7b1-f14b-42d9-8876-ed2cad02c482" />


**Válasz:** `200 OK`
```json
[
    {
        "id": 1,
        "user_id": 5,
        "resource_id": 1,
        "start_time": "2025-12-10T14:00:00Z",
        "end_time": "2025-12-10T15:00:00Z",
        "status": "pending",
        "created_at": "2025-12-04T12:00:00Z",
        "updated_at": "2025-12-04T12:00:00Z"
    }
]
```

---

### **GET** `/reservations/{id}` - Konkrét Foglalás Megtekintése

- **Normál felhasználó**: Csak saját foglalásait érheti el
- **Admin**: Bármely foglalást megtekinthet
<img width="853" height="160" alt="image" src="https://github.com/user-attachments/assets/afa8725b-0dc2-414f-8370-58cf7c7a66e4" />

**Válasz:** `200 OK`
```json
{
    "id": 1,
    "user_id": 5,
    "resource_id": 1,
    "start_time": "2025-12-10T14:00:00Z",
    "end_time": "2025-12-10T15:00:00Z",
    "status": "pending",
    "created_at": "2025-12-04T12:00:00Z",
    "updated_at": "2025-12-04T12:00:00Z"
}
```

**Válasz (jogosultság hiánya):** `403 Forbidden`
```json
{
  "message": "Nincs jogosultságod megtekinteni ezt a foglalást!"
}
```

---

### **POST** `/reservations` - Foglalás Létrehozása

Új foglalás létrehozása egy erőforrásra.
<img width="862" height="274" alt="image" src="https://github.com/user-attachments/assets/5fb49fbe-d6b7-4a25-a3c2-6f7fd68ab0db" />

**Kérés Törzse:**
```json
{
    "resource_id": 1,
    "start_time": "2025-12-10T14:00:00",
    "end_time": "2025-12-10T15:00:00"
}
```

**Válasz:** `201 Created`
```json
{
    "id": 5,
    "user_id": 5,
    "resource_id": 1,
    "start_time": "2025-12-10T14:00:00Z",
    "end_time": "2025-12-10T15:00:00Z",
    "status": "pending",
    "created_at": "2025-12-04T12:30:00Z",
    "updated_at": "2025-12-04T12:30:00Z"
}
```

**Válasz (múltbeli foglalás):** `422 Unprocessable Entity`
```json
{
  "message": "Validation failed"
}
```

---

### **PATCH** `/reservations/{id}` - Foglalás Módosítása

Meglévő foglalás adatainak frissítése.

- **Normál felhasználó**: Módosíthatja az időpontokat, de **nem** a `status` mezőt
- **Admin**: Módosíthat mindent, beleértve a státuszt is
<img width="845" height="243" alt="image" src="https://github.com/user-attachments/assets/99538372-b732-4f69-b348-5559a1e2aaf8" />

**Kérés Törzse:**
```json
{
    "status": "approved"
}
```

**Válasz:** `200 OK`
```json
{
    "id": 5,
    "user_id": 5,
    "resource_id": 1,
    "start_time": "2025-12-11T14:00:00Z",
    "end_time": "2025-12-11T15:00:00Z",
    "status": "approved",
    "created_at": "2025-12-04T12:30:00Z",
    "updated_at": "2025-12-04T13:00:00Z"
}
```

---

### **DELETE** `/reservations/{id}` - Foglalás Törlése

Foglalás eltávolítása.
<img width="849" height="188" alt="image" src="https://github.com/user-attachments/assets/1ffc1303-0329-42a4-9aea-b7a49a5de7a9" />


**Válasz:** `200 OK`
```json
{
  "message": "Reservation deleted"
}
```

---

## Foglalás Státuszok

A foglalás a következő státuszok lehetnek:

- **pending**: Újabb foglalás, még nem jóváhagyva
- **approved**: Admin által jóváhagyva
- **rejected**: Admin által elutasítva
- **cancelled**: Felhasználó által visszavonva

---

## Összefoglalás - Végpontok Táblázata

| HTTP | Útvonal | Jogosultság | Státusz | Leírás |
|------|---------|-------------|--------|--------|
| GET | `/hello` | Nyilvános | 200 OK | API teszteléshez |
| POST | `/register` | Nyilvános | 201 Created, 422 | Regisztráció (JWT token) |
| POST | `/login` | Nyilvános | 200 OK, 401 | Bejelentkezés (JWT token) |
| POST | `/logout` | Auth (JWT) | 200 OK, 401 | Kijelentkezés (token invalidálás) |
| POST | `/refresh` | Auth (JWT) | 200 OK, 401 | JWT token frissítése |
| GET | `/users/me` | Auth (JWT) | 200 OK, 401 | Saját profil |
| PUT | `/users/me` | Auth (JWT) | 200 OK, 401, 422 | Profil frissítés |
| GET | `/users` | Admin | 200 OK, 403, 401 | Összes felhasználó |
| GET | `/users/{id}` | Admin | 200 OK, 403, 404, 401 | Konkrét felhasználó |
| DELETE | `/users/{id}` | Admin | 200 OK, 403, 404, 401 | Felhasználó törlés(soft delete) |
| GET | `/resources` | Auth (JWT) | 200 OK, 401 | Erőforrások |
| GET | `/resources/{id}` | Auth (JWT) | 200 OK, 401, 404 | Konkrét erőforrás |
| POST | `/resources` | Admin | 201 Created, 403, 401 | Erőforrás létrehozás |
| PUT | `/resources/{id}` | Admin | 200 OK, 403, 401 | Erőforrás módosítás |
| DELETE | `/resources/{id}` | Admin | 200 OK, 403, 401 | Erőforrás törlés |
| GET | `/reservations` | Auth (JWT) | 200 OK, 401 | Foglalások |
| GET | `/reservations/{id}` | Auth (JWT) | 200 OK, 401, 403, 404 | Konkrét foglalás |
| POST | `/reservations` | Auth (JWT) | 201 Created, 401, 422 | Foglalás létrehozás |
| PUT | `/reservations/{id}` | Auth (JWT) | 200 OK, 401, 403, 422 | Foglalás módosítás |
| DELETE | `/reservations/{id}` | Auth (JWT) | 200 OK, 401, 403, 404 | Foglalás törlés |

## Autentifikáció és Jogosultságok

### Token-alapú Autentifikáció
- Minden autentifikált endpoint `Authorization: Bearer {token}` header-t igényel
- A JWT token bejelentkezéskor/regisztrációkor jön vissza
- A tokenek memóriában vannak, invalidáláskor blacklist-re kerülnek
- Érvénytelen token esetén: `401 Unauthorized`

### Szerepek (Roles)

1. **Normál felhasználó** (`is_admin = false`)
   - ✓ Saját profil megtekintése és módosítása
   - ✓ Erőforrások megtekintése
   - ✓ Saját foglalások CRUD operációk
   - ✗ Saját foglalások státusza nem módosítható
   - ✗ Más felhasználók adataihoz nincs hozzáférése
   - ✗ Erőforrásokat nem módosíthatja

2. **Administrator** (`is_admin = true`)
   - ✓ Összes felhasználó kezelése
   - ✓ Erőforrások teljes kezelése (létrehozás, módosítás, törlés)
   - ✓ Összes foglalás megtekintése és kezelése
   - ✓ Foglalás státuszának módosítása
   - ✓ Felhasználók törlése

## Modellek

- User — Felhasználó entitás, tartalmaz kapcsolatot a foglalásokkal.
```php
protected $fillable = [
    'name',
    'email',
    'password',
    'phone'
];

public function reservations()
{
    return $this->hasMany(Reservation::class);
}
```

- Resource — Erőforrás (pl. terem/eszkoz) entitás, rendelkezésre állás jelzővel.
```php
protected $fillable = [
    'name',
    'type',
    'description',
    'available',
];

protected $casts = [
    'available' => 'boolean',
];

public function reservations()
{
    return $this->hasMany(Reservation::class);
}
```

- Reservation — Foglalás entitás, tartalmaz időpontokat és státuszt.
```php
protected $fillable = [
    'user_id',
    'resource_id',
    'start_time',
    'end_time',
    'status',
];

protected $casts = [
    'start_time' => 'datetime',
    'end_time' => 'datetime',
];
```

## Factory-k

- UserFactory — Generál name, email, phone, bcrypt-elt alap jelszó.
```php
'password' => bcrypt('password'),
'phone' => fake()->phoneNumber(),
```

- ResourceFactory — Generál név, típus, leírás és available boolean értéket.
```php
'name' => fake()->words(2, true),
'description' => fake()->sentence(),
'type' => fake()->randomElement(['room','car','projector','other']),
'available' => $this->faker->boolean(80),
```

- ReservationFactory — Generál user_id, resource_id, start_time, end_time és status.
```php
'user_id' => User::factory(),
'resource_id' => Resource::factory(),
'start_time' => $this->faker->dateTimeBetween('now', '+2 days'),
'end_time' => $this->faker->dateTimeBetween('+3 days', '+5 days'),
'status' => 'pending',
```

## Seeder-ek

- UserSeeder — Létrehoz egy admin felhasználót a seederben.
```php
User::factory()->create([
  'name' => 'Admin2',
  'email' => 'admin2@example.com',
  'password' => 'admin',
  'is_admin' => true,
]);
```

- ResourceSeeder — Létrehoz tömegesen erőforrásokat a factory-val.
```php
Resource::factory(5)->create();
```

- ReservationSeeder — Létrehoz több foglalást a ReservationFactory segítségével.
```php
Reservation::factory(20)->create();
```
## Controller

A Laravel controller-ek az MVC (Model-View-Controller) architektúra része. A controller-ek felelősek a HTTP kérések fogadásáért, az üzleti logika végrehajtásáért, és a válaszok visszaküldéséért. A Reservation System API-ja négy fő controller-t használ:

### 1. AuthController - Autentifikáció Kezelése

Az `AuthController` felelős a felhasználók regisztrációjáért, bejelentkezéséért, kijelentkezéséért és token frissítéséért. JWT (JSON Web Token) tokent használ az API autentifikációhoz.

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Regisztráció - új felhasználó létrehozása
     * 
     * Validálja a bejövő adatokat, létrehoz egy új felhasználót,
     * hash-eli a jelszót és automatikusan generál JWT tokent.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        // JWT token generálása az új felhasználóhoz
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ], 201);
    }

    /**
     * Bejelentkezés - JWT token generálás
     * 
     * Ellenőrzi a felhasználó email és jelszó kombinációját.
     * Sikeres bejelentkezés esetén JWT tokent generál.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = JWTAuth::user();

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => $user
        ]);
    }

    /**
     * Kijelentkezés - JWT token invalidálása
     * 
     * A tokent invalidálja (blacklist-re teszi), így
     * a korábbi token többé nem használható.
     */
    public function logout()
    {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Token frissítése
     * 
     * Új JWT tokent generál a régi token alapján,
     * anélkül hogy újra be kellene jelentkezni.
     */
    public function refresh()
    {
        $token = auth('api')->refresh();

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => auth('api')->user()
        ]);
    }
}
```

**Főbb Funkciók:**
- **register()**: Hash-eli a jelszót (`Hash::make()`), validál minden input mezőt, és automatikusan JWT tokent generál
- **login()**: JWT token generálás (`JWTAuth::attempt()`), amely ellenőrzi az email/jelszó kombinációt
- **logout()**: Invalidálja a JWT tokent (blacklist-re kerül)
- **refresh()**: Új JWT token generálása a régi alapján, session megőrzése

---

### 2. UserController - Felhasználó Kezelés

A `UserController` a felhasználói profilok kezelését végzi. Tartalmaz normál felhasználói és admin funkciókat egyaránt.

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * GET /users/me - Aktuális felhasználó profiljának lekérése
     * 
     * A bejelentkezett felhasználó adatait adja vissza.
     * A $request->user() metódus automatikusan visszaadja az
     * autentifikált felhasználót a JWT token alapján.
     */
    public function me(Request $request)
    {
        return response()->json($request->user(), 200);
    }

    /**
     * PUT /users/me - Saját profil frissítése
     * 
     * A felhasználó módosíthatja saját nevét, email címét,
     * jelszavát és telefonszámát. A 'sometimes' validációs szabály
     * csak akkor aktiválódik, ha a mező jelen van a kérésben.
     */
    public function updateMe(Request $request)
    {
        $user = $request->user();

        // Validáció: 'sometimes' = csak akkor kötelező, ha jelen van
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id, // saját email kivéve
            'password' => 'sometimes|nullable|min:6',
            'phone' => 'sometimes|nullable|string',
        ]);

        // Csak azok a mezők frissülnek, amelyek jelen vannak a kérésben
        if ($request->has('name')) $user->name = $request->name;
        if ($request->has('email')) $user->email = $request->email;
        if ($request->filled('password')) $user->password = Hash::make($request->password);
        if ($request->has('phone')) $user->phone = $request->phone;

        $user->save();

        return response()->json($user, 200);
    }

    /**
     * GET /users - Összes felhasználó listázása (Admin csak)
     * 
     * Csak admin jogosultsággal rendelkező felhasználók férhetnek hozzá.
     * Ellenőrzi az is_admin boolean mezőt az aktuális felhasználónál.
     */
    public function index(Request $request)
    {
        // Jogosultság ellenőrzés: csak admin férhet hozzá
        if (!$request->user()->is_admin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Összes felhasználó visszaadása
        return response()->json(User::all(), 200);
    }

    /**
     * GET /users/{id} - Konkrét felhasználó megtekintése (Admin csak)
     * 
     * Admin felhasználók bármely felhasználó adatait megtekinthetik.
     */
    public function show(Request $request, $id)
    {
        // Jogosultság ellenőrzés
        if (!$request->user()->is_admin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Felhasználó keresése ID alapján
        $user = User::find($id);
        if (!$user) return response()->json(['message' => 'Not found'], 404);

        return response()->json($user, 200);
    }

    /**
     * DELETE /users/{id} - Felhasználó törlése (Admin csak)
     * 
     * Soft delete: a felhasználó nem törlődik teljesen,
     * hanem a deleted_at mező kitöltésre kerül.
     * A SoftDeletes trait használata a User modellben szükséges.
     */
    public function destroy(Request $request, $id)
    {
        // Jogosultság ellenőrzés
        if (!$request->user()->is_admin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $user = User::find($id);
        if (!$user) return response()->json(['message' => 'Not found'], 404);

        // Soft delete: deleted_at mező kitöltése
        $user->delete();
        return response()->json(['message' => 'User deleted'], 200);
    }
}
```

**Főbb Funkciók:**
- **me()**: Visszaadja az autentifikált felhasználó adatait
- **updateMe()**: Részleges frissítés támogatása (`sometimes` validáció), jelszó hash-elés
- **index(), show(), destroy()**: Admin-only műveletek, jogosultság ellenőrzéssel (`is_admin` mező)
- **Soft Delete**: A `delete()` metódus nem törli teljesen a rekordot, csak a `deleted_at` mezőt állítja be

---

### 3. ResourceController - Erőforrás Kezelés

A `ResourceController` kezeli az erőforrások (meeting room, equipment, stb.) CRUD műveleteit. Az erőforrások létrehozása, módosítása és törlése csak admin jogosultságú felhasználóknak engedélyezett.

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Resource;
use App\Models\Reservation;

class ResourceController extends Controller
{
    /**
     * GET /resources - Összes erőforrás listázása
     * 
     * Minden autentifikált felhasználó megtekintheti az elérhető erőforrásokat.
     */
    public function index(Request $request)
    {
        $resources = Resource::all();
        return response()->json($resources, 200);
    }

    /**
     * GET /resources/{resource} - Konkrét erőforrás megtekintése
     * 
     * Laravel Route Model Binding: automatikusan lekéri a Resource modellt
     * az ID alapján, és beinjektálja a controller metódusba.
     */
    public function show(Request $request, Resource $resource)
    {
        return response()->json($resource, 200);
    }

    /**
     * POST /resources - Erőforrás létrehozása (Admin csak)
     * 
     * Új erőforrás hozzáadása a rendszerhez. Csak admin jogosultsággal.
     */
    public function store(Request $request)
    {
        // Jogosultság ellenőrzés
        if (!$request->user()->is_admin) {
            return response()->json([
                'message' => 'Nincs jogosultságod erőforrás létrehozására.'
            ], 403);
        }

        // Validáció
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'available' => 'sometimes|boolean', // alapértelmezett: true
        ]);

        // Erőforrás létrehozása az adatbázisban
        $resource = Resource::create($validated);

        return response()->json($resource, 201);
    }

    /**
     * PUT/PATCH /resources/{resource} - Erőforrás módosítása (Admin csak)
     * 
     * Meglévő erőforrás adatainak frissítése.
     * A 'sometimes' szabály lehetővé teszi a részleges frissítést.
     */
    public function update(Request $request, Resource $resource)
    {
        // Jogosultság ellenőrzés
        if (!$request->user()->is_admin) {
            return response()->json([
                'message' => 'Nincs jogosultságod erőforrás módosítására.'
            ], 403);
        }

        // Validáció: sometimes = csak akkor kötelező, ha jelen van
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'available' => 'sometimes|boolean',
        ]);

        // Erőforrás frissítése
        $resource->update($validated);

        // fresh() metódus: frissített adatok lekérése az adatbázisból
        return response()->json($resource->fresh(), 200);
    }

    /**
     * DELETE /resources/{resource} - Erőforrás törlése (Admin csak)
     * 
     * Erőforrás eltávolítása a rendszerből.
     * Soft delete: csak a deleted_at mező kitöltődik, az adatok megmaradnak.
     */
    public function destroy(Request $request, Resource $resource)
    {
        // Jogosultság ellenőrzés
        if (!$request->user()->is_admin) {
            return response()->json([
                'message' => 'Nincs jogosultságod erőforrás törlésére.'
            ], 403);
        }

        // Erőforrás törlése
        $resource->delete();

        return response()->json(['message' => 'Erőforrás törölve.'], 200);
    }
}
```

**Főbb Funkciók:**
- **index(), show()**: Minden autentifikált felhasználó számára elérhető
- **store(), update(), destroy()**: Csak admin jogosultsággal
- **Route Model Binding**: A `Resource $resource` paraméter automatikusan lekéri a modellt
- **Validáció**: A `sometimes` szabály részleges frissítést tesz lehetővé (PATCH)

---

### 4. ReservationController - Foglalás Kezelés

A `ReservationController` kezeli a foglalások létrehozását, lekérését, módosítását és törlését. Tartalmaz komplex jogosultság-ellenőrzési logikát: normál felhasználók csak saját foglalásaikat érhetik el, admin felhasználók pedig mindenhez hozzáférhetnek.

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;

class ReservationController extends Controller
{
    /**
     * GET /reservations - Foglalások listázása
     * 
     * Normál felhasználó: csak saját foglalásai
     * Admin: összes foglalás
     */
    public function index(Request $request){
        $user = $request->user();

        // Jogosultság alapú szűrés
        if($user->is_admin){
            $reservations = Reservation::all();
        } else {
            // Csak a felhasználó saját foglalásai
            $reservations = Reservation::where('user_id', $user->id)->get();
        }
        
        return response()->json($reservations, 200);
    }

    /**
     * GET /reservations/{id} - Konkrét foglalás megtekintése
     * 
     * Normál felhasználó: csak saját foglalását tekintheti meg
     * Admin: bármely foglalást megtekinthet
     */
    public function show(Request $request, $id){
        $user = $request->user();

        // Foglalás keresése, 404 hiba ha nem létezik
        $reservation = Reservation::findOrFail($id);

        // Jogosultság ellenőrzés: nem admin és nem sajátja a foglalás
        if(!$user->is_admin && $reservation->user_id != $user->id){
            return response()->json([
                'message' => 'Nincs jogosultságod megtekinteni ezt a foglalást!'
            ], 403);
        }

        return response()->json($reservation, 200);
    }

    /**
     * POST /reservations - Foglalás létrehozása
     * 
     * Új foglalás létrehozása. A user_id automatikusan az aktuális
     * felhasználó ID-je lesz. Alapértelmezett status: 'pending'.
     */
    public function store(Request $request)
    {
        // Validáció
        $validated = $request->validate([
            'resource_id' => 'required|exists:resources,id', // létező erőforrás
            'start_time' => 'required|date|after_or_equal:now', // nem múltbeli
            'end_time'   => 'required|date|after:start_time', // vége > kezdet
        ]);

        // Foglalás létrehozása
        $reservation = Reservation::create([
            'user_id' => $request->user()->id, // automatikus user_id kitöltés
            'resource_id' => $validated['resource_id'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'status' => 'pending', // alapértelmezett státusz
        ]);

        return response()->json($reservation, 201);
    }

    /**
     * PUT/PATCH /reservations/{id} - Foglalás módosítása
     * 
     * Normál felhasználó: módosíthatja az időpontokat, de nem a státuszt
     * Admin: mindent módosíthat, beleértve a státuszt is
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $reservation = Reservation::findOrFail($id);

        // Jogosultság ellenőrzés: csak admin vagy a foglalás tulajdonosa
        if (!$user->is_admin && $reservation->user_id !== $user->id) {
            return response()->json([
                'message' => 'Nincs jogosultságod módosítani ezt a foglalást!'
            ], 403);
        }

        // Validáció
        $validated = $request->validate([
            'resource_id' => 'sometimes|required|exists:resources,id',
            'start_time' => 'sometimes|required|date|after_or_equal:now',
            'end_time'   => 'sometimes|required|date|after:start_time',
            'status'     => 'sometimes|in:pending,approved,rejected,cancelled',
        ]);

        // Ha nem admin, töröljük a status mezőt a validált adatokból
        if (!$user->is_admin) {
            unset($validated['status']);
        }

        // Foglalás frissítése
        $reservation->update($validated);

        // fresh() metódus: frissített adatok lekérése
        return response()->json($reservation->fresh(), 200);
    }

    /**
     * DELETE /reservations/{id} - Foglalás törlése
     * 
     * Normál felhasználó: csak saját foglalását törölheti
     * Admin: bármely foglalást törölhet
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $reservation = Reservation::findOrFail($id);

        // Jogosultság ellenőrzés
        if (!$user->is_admin && $reservation->user_id !== $user->id) {
            return response()->json([
                'message' => 'Nincs jogosultságod törölni ezt a foglalást!'
            ], 403);
        }

        // Foglalás törlése (soft delete)
        $reservation->delete();

        return response()->json(['message' => 'Foglalás törölve.'], 200);
    }
}
```

**Főbb Funkciók:**
- **Dinamikus Jogosultság Ellenőrzés**: A `is_admin` mező alapján más adatok jelennek meg
- **Status Védelem**: Normál felhasználók nem módosíthatják a foglalás státuszát
- **Validációs Szabályok**:
  - `after_or_equal:now`: nem lehet múltbeli foglalás
  - `after:start_time`: a vége mindig későbbi mint a kezdet
  - `exists:resources,id`: csak létező erőforrásra lehet foglalni
- **findOrFail()**: Automatikus 404 válasz, ha nem létezik a rekord

---

## Tesztek 
A Laravel PHPUnit alapú tesztelési keretrendszert használ. A feature tesztek az API végpontokat tesztelik valós HTTP kérésekkel. A `RefreshDatabase` trait biztosítja, hogy minden teszt előtt tiszta adatbázis állapot legyen.

### 1. AuthTest - Autentifikációs Tesztek

Az `AuthTest` a regisztráció, bejelentkezés és ping végpont működését ellenőrzi.

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthTest extends TestCase
{
    use RefreshDatabase; // Adatbázis újratöltése minden teszt előtt

    /**
     * Ping endpoint teszt
     * 
     * Ellenőrzi, hogy az API él-e és válaszol-e.
     * Ez egy egyszerű health check endpoint.
     */
    public function test_ping_endpoint_returns_ok()
    {
        // HTTP GET kérés az /api/hello endpointra
        $response = $this->getJson('/api/hello');
        
        // Ellenőrizzük a HTTP státusz kódot és a válasz struktúráját
        $response->assertStatus(200)
                ->assertJson(['message' => 'API works!']);
    }

    /**
     * Regisztráció teszt
     * 
     * Ellenőrzi, hogy új felhasználó létrehozható-e.
     * Teszteli az input validációt és az adatbázis műveletet.
     */
    public function test_register_creates_user()
    {
        // ARRANGE: Teszt adatok előkészítése
        $payload = [
            'name' => 'Teszt Elek',
            'email' => 'teszt@example.com',
            'password' => 'Jelszo_2025'
        ];

        // ACT: HTTP POST kérés a regisztrációs endpointra
        $response = $this->postJson('/api/register', $payload);
        
        // ASSERT: Ellenőrzések
        $response->assertStatus(201) // 201 Created státusz
                ->assertJsonStructure([
                    'message', 
                    'user' => ['id', 'name', 'email']
                ]);
        
        // Ellenőrizzük, hogy a felhasználó tényleg létrejött az adatbázisban
        $this->assertDatabaseHas('users', [
            'email' => 'teszt@example.com',
        ]);
    }

    /**
     * Sikeres bejelentkezés teszt
     * 
     * Ellenőrzi, hogy helyes email és jelszó kombinációval
     * token generálódik-e.
     */
    public function test_login_with_valid_credentials()
    {
        // ARRANGE: Felhasználó létrehozása a factory-val
        $password = 'Jelszo_2025';
        $user = User::factory()->create([
            'email' => 'validuser@example.com',
            'password' => Hash::make($password), // Hash-elt jelszó
        ]);

        // ACT: Bejelentkezési kérés helyes adatokkal
        $response = $this->postJson('/api/login', [
            'email' => 'validuser@example.com',
            'password' => $password, // Plain text jelszó
        ]);

        // ASSERT: Ellenőrizzük a sikeres választ és a JWT struktúráját
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'access_token',  // JWT token
                     'token_type',     // "bearer"
                     'expires_in'      // Másodpercek (3600)
                 ]);
    }

    /**
     * Sikertelen bejelentkezés teszt
     * 
     * Ellenőrzi, hogy helytelen jelszóval elutasításra kerül-e
     * a bejelentkezési kísérlet.
     */
    public function test_login_with_invalid_credentials()
    {
        // ARRANGE: Létező felhasználó a helyes jelszóval
        $user = User::factory()->create([
            'email' => 'existing@example.com',
            'password' => Hash::make('CorrectPassword'), 
        ]);

        // ACT: Helytelen jelszóval próbálkozás
        $response = $this->postJson('/api/login', [
            'email' => 'existing@example.com',
            'password' => 'wrongpass' // Rossz jelszó
        ]);

        // ASSERT: 401 Unauthorized válasz ellenőrzése
        $response->assertStatus(401)
                 ->assertJson(['message' => 'Invalid credentials']);
    }
}
```

**Tesztelési Minták:**
- **AAA Pattern**: Arrange-Act-Assert (Előkészítés-Végrehajtás-Ellenőrzés)
- **RefreshDatabase**: Minden teszt tiszta adatbázissal kezdődik
- **Factory Usage**: `User::factory()->create()` teszt adatokat generál
- **assertDatabaseHas()**: Ellenőrzi, hogy létezik-e rekord az adatbázisban
- **assertJsonStructure()**: JSON válasz struktúrájának ellenőrzése

---

### 2. UserTest - Felhasználó Kezelés Tesztek

A `UserTest` a felhasználói profil műveletek és admin funkciók tesztelését végzi.

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper metódus JWT token generálásához és authentikált kérésekhez
     */
    protected function actingAsJWT(User $user)
    {
        $token = JWTAuth::fromUser($user);
        return $this->withHeader('Authorization', 'Bearer ' . $token);
    }

    /**
     * Profil lekérés teszt
     * 
     * Ellenőrzi, hogy a bejelentkezett felhasználó
     * le tudja-e kérni saját profilját JWT tokennel.
     */
    public function test_get_current_user_profile()
    {
        // ARRANGE: Felhasználó létrehozása
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'name' => 'Test User'
        ]);

        // ACT: Profil lekérése JWT tokennel
        $response = $this->actingAsJWT($user)->getJson('/api/users/me');

        // ASSERT: Ellenőrizzük a választ
        $response->assertStatus(200)
                 ->assertJsonStructure(['id', 'name', 'email'])
                 ->assertJson([
                     'name' => 'Test User',
                     'email' => 'user@example.com'
                 ]);
    }

    /**
     * Profil módosítás teszt
     * 
     * Ellenőrzi, hogy a felhasználó módosíthatja-e
     * saját profiljának adatait.
     */
    public function test_update_user_profile()
    {
        // ARRANGE: Felhasználó létrehozása régi adatokkal
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'name' => 'Old Name'
        ]);

        // ACT: Profil frissítése új adatokkal JWT tokennel
        $response = $this->actingAsJWT($user)->putJson('/api/users/me', [
            'name' => 'New Name',
            'phone' => '+36201234567'
        ]);

        // ASSERT: Ellenőrizzük a választ és az adatbázist
        $response->assertStatus(200)
                 ->assertJson([
                     'name' => 'New Name',
                     'phone' => '+36201234567'
                 ]);

        // Ellenőrizzük, hogy az adatbázisban is frissült
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name'
        ]);
    }

    /**
     * Admin felhasználó listázás teszt
     * 
     * Ellenőrzi, hogy admin jogosultsággal rendelkező
     * felhasználó láthatja-e az összes felhasználót JWT tokennel.
     */
    public function test_admin_list_all_users()
    {
        // ARRANGE: Admin és normál felhasználók létrehozása
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->count(3)->create(['is_admin' => false]);

        // ACT: Felhasználók lekérése adminként JWT tokennel
        $response = $this->actingAsJWT($admin)->getJson('/api/users');

        // ASSERT: Ellenőrizzük, hogy 4 felhasználó van (1 admin + 3 normál)
        $response->assertStatus(200)
                 ->assertJsonCount(4);
    }

    /**
     * Nem admin felhasználó hozzáférés teszt
     * 
     * Ellenőrzi, hogy normál felhasználó nem férhet hozzá
     * az admin funkciókhoz JWT authentikációval sem.
     */
    public function test_non_admin_cannot_list_users()
    {
        // ARRANGE: Normál felhasználó (nem admin)
        $user = User::factory()->create(['is_admin' => false]);

        // ACT: Próbálja lekérni az összes felhasználót JWT tokennel
        $response = $this->actingAsJWT($user)->getJson('/api/users');

        // ASSERT: 403 Forbidden válasz
        $response->assertStatus(403)
                 ->assertJson(['message' => 'Forbidden']);
    }

    /**
     * Admin konkrét felhasználó megtekintés teszt
     * 
     * Admin felhasználó bármely más felhasználó
     * adatait megtekintheti JWT tokennel.
     */
    public function test_admin_show_specific_user()
    {
        // ARRANGE: Admin és cél felhasználó
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['name' => 'Target User']);

        // ACT: Konkrét felhasználó lekérése JWT tokennel
        $response = $this->actingAsJWT($admin)->getJson("/api/users/{$user->id}");

        // ASSERT: Ellenőrizzük a választ
        $response->assertStatus(200)
                 ->assertJson(['name' => 'Target User']);
    }

    /**
     * Admin felhasználó törlés teszt
     * 
     * Ellenőrzi, hogy admin törölhet-e felhasználót JWT authentikációval.
     * Soft delete ellenőrzés: deleted_at mező kitöltve.
     */
    public function test_admin_delete_user()
    {
        // ARRANGE: Admin és törlendő felhasználó
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        // ACT: Felhasználó törlése JWT tokennel
        $response = $this->actingAsJWT($admin)->deleteJson("/api/users/{$user->id}");

        // ASSERT: Sikeres törlés és soft delete ellenőrzés
        $response->assertStatus(200)
                 ->assertJson(['message' => 'User deleted']);

        // assertSoftDeleted: ellenőrzi, hogy a deleted_at mező ki van töltve
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    /**
     * Nem autentifikált hozzáférés teszt
     * 
     * Ellenőrzi, hogy autentifikáció nélkül
     * nem érhetők el a protected endpointok.
     */
    public function test_unauthenticated_cannot_access_user_endpoints()
    {
        // ACT & ASSERT: Token nélkül 401 Unauthorized
        $this->getJson('/api/users/me')->assertStatus(401);
        $this->putJson('/api/users/me', [])->assertStatus(401);
        $this->getJson('/api/users')->assertStatus(401);
    }
}
```

**Tesztelési Technikák:**
- **actingAsJWT()**: JWT token generálása és Bearer authentikáció (Sanctum actingAs() helyett)
- **JWTAuth::fromUser()**: JWT token generálása adott felhasználóhoz
- **withHeader()**: Authorization fejléc hozzáadása Bearer tokennel
- **assertJsonCount()**: Ellenőrzi a JSON array elemszámát
- **assertSoftDeleted()**: Soft delete ellenőrzés (deleted_at mező)
- **count()**: Factory metódus, több rekord létrehozására

---

### 3. ResourceTest - Erőforrás Kezelés Tesztek

A `ResourceTest` az erőforrások CRUD műveleteit és jogosultság-kezelését teszteli JWT autentifikációval.

```php
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

    /**
     * Erőforrások listázás teszt
     * 
     * Minden autentifikált felhasználó láthatja az erőforrásokat JWT tokennel.
     */
    public function test_list_all_resources()
    {
        // ARRANGE: 3 erőforrás létrehozása factory-val
        Resource::factory()->count(3)->create();
        $user = User::factory()->create();

        // ACT: Erőforrások lekérése JWT tokennel
        $response = $this->actingAsJWT($user)->getJson('/api/resources');

        // ASSERT: 3 erőforrás a válaszban
        $response->assertStatus(200)
                 ->assertJsonCount(3);
    }

    /**
     * Konkrét erőforrás megtekintés teszt
     * 
     * Teszteli a Route Model Binding működését JWT authentikációval.
     */
    public function test_show_specific_resource()
    {
        // ARRANGE: Erőforrás létrehozása specifikus adatokkal
        $resource = Resource::factory()->create([
            'name' => 'Meeting Room A',
            'type' => 'room'
        ]);
        $user = User::factory()->create();

        // ACT: Konkrét erőforrás lekérése ID alapján JWT tokennel
        $response = $this->actingAsJWT($user)->getJson("/api/resources/{$resource->id}");

        // ASSERT: Ellenőrizzük a konkrét erőforrás adatait
        $response->assertStatus(200)
                 ->assertJson([
                     'name' => 'Meeting Room A',
                     'type' => 'room'
                 ]);
    }

    /**
     * Admin erőforrás létrehozás teszt
     * 
     * Csak admin jogosultsággal lehet erőforrást létrehozni JWT tokennel.
     */
    public function test_admin_create_resource()
    {
        // ARRANGE: Admin felhasználó
        $admin = User::factory()->create(['is_admin' => true]);

        // ACT: Új erőforrás létrehozása JWT tokennel
        $response = $this->actingAsJWT($admin)->postJson('/api/resources', [
            'name' => 'New Resource',
            'type' => 'equipment',
            'description' => 'A test resource',
            'available' => true
        ]);

        // ASSERT: 201 Created státusz és struktúra ellenőrzés
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'id', 'name', 'type', 'description', 'available'
                 ]);

        // Ellenőrizzük az adatbázist
        $this->assertDatabaseHas('resources', [
            'name' => 'New Resource',
            'type' => 'equipment'
        ]);
    }

    /**
     * Nem admin erőforrás létrehozás megtagadás teszt
     * 
     * Normál felhasználó nem hozhat létre erőforrást.
     */
    public function test_non_admin_cannot_create_resource()
    {
        // ARRANGE: Normál felhasználó
        $user = User::factory()->create(['is_admin' => false]);

        // ACT: Erőforrás létrehozási kísérlet JWT tokennel
        $response = $this->actingAsJWT($user)->postJson('/api/resources', [
            'name' => 'Unauthorized Resource',
            'type' => 'equipment'
        ]);

        // ASSERT: 403 Forbidden válasz
        $response->assertStatus(403)
                 ->assertJson([
                     'message' => 'Nincs jogosultságod erőforrás létrehozására.'
                 ]);
    }

    /**
     * Admin erőforrás módosítás teszt
     * 
     * Admin módosíthatja az erőforrások adatait JWT tokennel.
     */
    public function test_admin_update_resource()
    {
        // ARRANGE: Admin és módosítandó erőforrás
        $admin = User::factory()->create(['is_admin' => true]);
        $resource = Resource::factory()->create([
            'name' => 'Old Name',
            'available' => true
        ]);

        // ACT: Erőforrás frissítése JWT tokennel
        $response = $this->actingAsJWT($admin)->putJson(
            "/api/resources/{$resource->id}", 
            [
                'name' => 'Updated Name',
                'available' => false
            ]
        );

        // ASSERT: Ellenőrizzük a frissített adatokat
        $response->assertStatus(200)
                 ->assertJson([
                     'name' => 'Updated Name',
                     'available' => false
                 ]);

        // Adatbázis ellenőrzés
        $this->assertDatabaseHas('resources', [
            'id' => $resource->id,
            'name' => 'Updated Name'
        ]);
    }

    /**
     * Admin erőforrás törlés teszt
     * 
     * Admin törölhet erőforrásokat.
     * Soft delete: csak a deleted_at mező kitöltődik.
     */
    public function test_admin_delete_resource()
    {
        // ARRANGE: Admin és törlendő erőforrás
        $admin = User::factory()->create(['is_admin' => true]);
        $resource = Resource::factory()->create();

        // ACT: Erőforrás törlése JWT tokennel
        $response = $this->actingAsJWT($admin)->deleteJson(
            "/api/resources/{$resource->id}"
        );

        // ASSERT: Sikeres törlés
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Erőforrás törölve.']);

        // assertSoftDeleted: ellenőrzi, hogy a deleted_at mező ki van töltve
        $this->assertSoftDeleted('resources', ['id' => $resource->id]);
    }

    /**
     * Nem admin módosítás megtagadás teszt
     * 
     * Normál felhasználó nem módosíthat erőforrást.
     */
    public function test_non_admin_cannot_update_resource()
    {
        // ARRANGE: Normál felhasználó és erőforrás
        $user = User::factory()->create(['is_admin' => false]);
        $resource = Resource::factory()->create();

        // ACT: Módosítási kísérlet JWT tokennel
        $response = $this->actingAsJWT($user)->putJson(
            "/api/resources/{$resource->id}", 
            ['name' => 'Hacked Name']
        );

        // ASSERT: 403 Forbidden válasz
        $response->assertStatus(403)
                 ->assertJson([
                     'message' => 'Nincs jogosultságod erőforrás módosítására.'
                 ]);
    }

    /**
     * Nem autentifikált erőforrás létrehozás teszt
     * 
     * Token nélkül nem lehet erőforrást létrehozni.
     */
    public function test_unauthenticated_cannot_create_resource()
    {
        // ACT & ASSERT: Token nélkül 401 Unauthorized
        $this->postJson('/api/resources', [
            'name' => 'Test',
            'type' => 'room'
        ])->assertStatus(401);
    }
}
```

**Tesztelési Jellemzők:**
- **assertSoftDeleted()**: Soft delete ellenőrzése (deleted_at mező kitöltve)
- **Route Model Binding Test**: A `show()` metódus automatikus model lekérést tesztel
- **Admin vs Normál User**: Minden művelethez külön teszt a jogosultság ellenőrzésre

---

### 4. ReservationTest - Foglalás Kezelés Tesztek

A `ReservationTest` a foglalások komplex üzleti logikáját teszteli, beleértve a jogosultság-kezelést, időpont validációt és státusz védelmét.

```php
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

    /**
     * Felhasználó saját foglalásai teszt
     * 
     * Normál felhasználó csak saját foglalásait látja JWT tokennel.
     */
    public function test_user_list_own_reservations()
    {
        // ARRANGE: Felhasználó és foglalások
        $user = User::factory()->create();
        $resource = Resource::factory()->create();
        
        // 2 foglalás létrehozása a felhasználóhoz
        Reservation::factory()->count(2)->create([
            'user_id' => $user->id,
            'resource_id' => $resource->id
        ]);

        // ACT: Foglalások lekérése JWT tokennel
        $response = $this->actingAsJWT($user)->getJson('/api/reservations');

        // ASSERT: Csak a 2 saját foglalást látja
        $response->assertStatus(200)
                 ->assertJsonCount(2);
    }

    /**
     * Admin összes foglalás teszt
     * 
     * Admin felhasználó minden foglalást lát.
     */
    public function test_admin_list_all_reservations()
    {
        // ARRANGE: Admin és több felhasználó foglalásai
        $admin = User::factory()->create(['is_admin' => true]);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $resource = Resource::factory()->create();

        // Különböző felhasználók foglalásai
        Reservation::factory()->create([
            'user_id' => $user1->id, 
            'resource_id' => $resource->id
        ]);
        Reservation::factory()->create([
            'user_id' => $user2->id, 
            'resource_id' => $resource->id
        ]);

        // ACT: Foglalások lekérése adminként JWT tokennel
        $response = $this->actingAsJWT($admin)->getJson('/api/reservations');

        // ASSERT: Mind a 2 foglalást látja
        $response->assertStatus(200)
                 ->assertJsonCount(2);
    }

    /**
     * Saját foglalás megtekintés teszt
     * 
     * Felhasználó megtekintheti saját foglalását JWT tokennel.
     */
    public function test_show_own_reservation()
    {
        // ARRANGE: Felhasználó és foglalása
        $user = User::factory()->create();
        $resource = Resource::factory()->create();
        $reservation = Reservation::factory()->create([
            'user_id' => $user->id,
            'resource_id' => $resource->id
        ]);

        // ACT: Saját foglalás lekérése JWT tokennel
        $response = $this->actingAsJWT($user)->getJson(
            "/api/reservations/{$reservation->id}"
        );

        // ASSERT: Sikeres lekérés és struktúra ellenőrzés
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'id', 'user_id', 'resource_id', 
                     'start_time', 'end_time', 'status'
                 ]);
    }

    /**
     * Más foglalása nem látható teszt
     * 
     * Normál felhasználó nem tekinthet meg más foglalását JWT tokennel sem.
     */
    public function test_user_cannot_view_other_user_reservation()
    {
        // ARRANGE: Két felhasználó, egyik foglalása
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $resource = Resource::factory()->create();
        $reservation = Reservation::factory()->create([
            'user_id' => $user2->id,
            'resource_id' => $resource->id
        ]);

        // ACT: user1 próbálja megtekinteni user2 foglalását JWT tokennel
        $response = $this->actingAsJWT($user1)->getJson(
            "/api/reservations/{$reservation->id}"
        );

        // ASSERT: 403 Forbidden válasz
        $response->assertStatus(403)
                 ->assertJson([
                     'message' => 'Nincs jogosultságod megtekinteni ezt a foglalást!'
                 ]);
    }

    /**
     * Foglalás létrehozás teszt
     * 
     * Teszteli az új foglalás létrehozását jövőbeli időpontra.
     * Carbon library: dátum és idő kezelés.
     */
    public function test_create_reservation()
    {
        // ARRANGE: Felhasználó és erőforrás
        $user = User::factory()->create();
        $resource = Resource::factory()->create();
        
        // Jövőbeli időpontok Carbon-nal
        $startTime = Carbon::now()->addHours(2);
        $endTime = $startTime->copy()->addHours(1);

        // ACT: Foglalás létrehozása JWT tokennel
        $response = $this->actingAsJWT($user)->postJson('/api/reservations', [
            'resource_id' => $resource->id,
            'start_time' => $startTime,
            'end_time' => $endTime
        ]);

        // ASSERT: 201 Created és pending státusz
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'id', 'user_id', 'resource_id', 
                     'start_time', 'end_time', 'status'
                 ])
                 ->assertJson(['status' => 'pending']);

        // Adatbázis ellenőrzés
        $this->assertDatabaseHas('reservations', [
            'user_id' => $user->id,
            'resource_id' => $resource->id
        ]);
    }

    /**
     * Múltbeli foglalás tilalmazás teszt
     * 
     * Validációs szabály: nem lehet múltbeli foglalást létrehozni.
     */
    public function test_cannot_create_reservation_in_past()
    {
        // ARRANGE: Felhasználó és erőforrás
        $user = User::factory()->create();
        $resource = Resource::factory()->create();
        
        // Múltbeli időpontok
        $startTime = Carbon::now()->subHours(1); // 1 órával ezelőtt
        $endTime = $startTime->copy()->addHours(1);

        // ACT: Múltbeli foglalás kísérlete JWT tokennel
        $response = $this->actingAsJWT($user)->postJson('/api/reservations', [
            'resource_id' => $resource->id,
            'start_time' => $startTime,
            'end_time' => $endTime
        ]);

        // ASSERT: 422 Unprocessable Entity (validációs hiba)
        $response->assertStatus(422);
    }

    /**
     * Felhasználó foglalás módosítás teszt
     * 
     * Normál felhasználó módosíthatja saját foglalásának időpontjait.
     */
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

        // Új időpontok
        $newStartTime = Carbon::now()->addHours(5);
        $newEndTime = $newStartTime->copy()->addHours(2);

        // ACT: Foglalás frissítése JWT tokennel
        $response = $this->actingAsJWT($user)->putJson(
            "/api/reservations/{$reservation->id}", 
            [
                'start_time' => $newStartTime,
                'end_time' => $newEndTime
            ]
        );

        // ASSERT: Sikeres frissítés
        $response->assertStatus(200);

        // Frissítjük az objektumot az adatbázisból
        $reservation->refresh();
        
        // Ellenőrizzük az időpontokat (ISO formátumban)
        $this->assertEquals(
            $newStartTime->format('Y-m-d H:i'), 
            $reservation->start_time->format('Y-m-d H:i')
        );
        $this->assertEquals(
            $newEndTime->format('Y-m-d H:i'), 
            $reservation->end_time->format('Y-m-d H:i')
        );
    }

    /**
     * Admin státusz módosítás teszt
     * 
     * Admin felhasználó módosíthatja a foglalás státuszát.
     */
    public function test_admin_can_change_reservation_status()
    {
        // ARRANGE: Admin, felhasználó és foglalás
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $resource = Resource::factory()->create();
        $reservation = Reservation::factory()->create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'status' => 'pending'
        ]);

        // ACT: Status módosítása approved-ra JWT tokennel
        $response = $this->actingAsJWT($admin)->putJson(
            "/api/reservations/{$reservation->id}", 
            ['status' => 'approved']
        );

        // ASSERT: Státusz megváltozott
        $response->assertStatus(200)
                 ->assertJson(['status' => 'approved']);
    }

    /**
     * Felhasználó státusz védelem teszt
     * 
     * Normál felhasználó NEM módosíthatja a státuszt.
     */
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

        // ACT: Status módosítási kísérlet JWT tokennel
        $response = $this->actingAsJWT($user)->putJson(
            "/api/reservations/{$reservation->id}", 
            ['status' => 'approved']
        );

        // ASSERT: Státusz NEM változott meg
        $response->assertStatus(200);
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'pending' // továbbra is pending
        ]);
    }

    /**
     * Foglalás törlés teszt
     * 
     * Felhasználó törölheti saját foglalását.
     * Soft delete: csak a deleted_at mező kitöltődik.
     */
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
        $response = $this->actingAsJWT($user)->deleteJson(
            "/api/reservations/{$reservation->id}"
        );

        // ASSERT: Sikeres törlés
        $response->assertStatus(200);

        // assertSoftDeleted: ellenőrzi, hogy a deleted_at mező ki van töltve
        $this->assertSoftDeleted('reservations', [
            'id' => $reservation->id
        ]);
    }

    /**
     * Nem autentifikált foglalás létrehozás teszt
     * 
     * Token nélkül nem lehet foglalást létrehozni.
     */
    public function test_unauthenticated_cannot_create_reservation()
    {
        // ACT & ASSERT: Token nélkül 401 Unauthorized
        $this->postJson('/api/reservations', [
            'resource_id' => 1,
            'start_time' => Carbon::now()->addHours(1),
            'end_time' => Carbon::now()->addHours(2)
        ])->assertStatus(401);
    }
}
```

---

### Teszt Futtatása

```bash
# Összes teszt futtatása
php artisan test

# Konkrét teszt fájl
php artisan test tests/Feature/AuthTest.php

# Konkrét teszt metódus
php artisan test tests/Feature/AuthTest.php --filter test_register_creates_user

# Teszt kimenet részletesebben
php artisan test --verbose
```

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
- `test_admin_delete_user()` - Admin - felhasználó törlés(soft delete)
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

**Összesen: 31 teszt, 100% pass rate ✅**

<img width="347" height="69" alt="image" src="https://github.com/user-attachments/assets/c39b88b8-efb6-468c-b6e2-cfbf2ff4e609" />



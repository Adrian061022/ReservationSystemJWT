# Reservation System - Projekt Dokumentáció

## Projekt Áttekintése

A **Reservation System** egy Laravel-alapú REST API alkalmazás, amely lehetővé teszi a felhasználók számára erőforrások foglalását (például meetingtermek, felszerelések, berendezések). Az alkalmazás felhasználó-autentifikáción alapul (Laravel Sanctum API tokenek) és szerep-alapú hozzáférés-vezérlést (RBAC) alkalmaz az adminisztrációs funkciók megvédésére.

**Base URL:** `http://127.0.0.1:8000/api` (local development)
vagy `http://localhost/reservationSystem/public/api` (XAMPP)

## Technológia Stack

- **Backend Framework**: Laravel 11
- **Autentifikáció**: Laravel Sanctum (API tokenek)
- **Adatbázis**: MySQL 8.0+
- **Testing**: PHPUnit (Feature tesztek)
- **Package Manager**: Composer, npm
- **PHP verzió**: 8.2+

## Adatbázis Terv

```
+---------------------+     +---------------------+       +-----------------+        +-------------+
|personal_access_tokens|    |        users        |       |   reservations  |        |  resources  |
+---------------------+     +---------------------+       +-----------------+        +-------------+
| id (PK)             |   _1| id (PK)             |1__    | id (PK)         |     __1| id (PK)     |
| tokenable_id (FK)   |K_/  | name                |   \__N| user_id (FK)    |    /   | name        |
| tokenable_type      |     | email (unique)      |       | resource_id (FK)|M__/    | type        |
| name                |     | password            |       | start_time      |        | description |
| token (unique)      |     | phone (nullable)    |       | end_time        |        | available   |
| abilities           |     | is_admin (boolean)  |       | status          |        | created_at  |
| last_used_at        |     | created_at          |       | created_at      |        | updated_at  |
| created_at          |     | updated_at          |       | updated_at      |        +-------------+
+---------------------+     +---------------------+       +-----------------+
```

## Projekt Szerkezete

```
reservationSystem/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── AuthController.php          # Regisztráció, bejelentkezés, kijelentkezés
│   │       ├── UserController.php          # Felhasználó profil kezelés (admin funkciók)
│   │       ├── ResourceController.php      # Erőforrás CRUD operációk
│   │       └── ReservationController.php   # Foglalás CRUD operációk
│   ├── Models/
│   │   ├── User.php                        # Felhasználó model (Sanctum)
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
│   ├── auth.php                            # Autentifikáció konfiguráció
│   └── sanctum.php                         # Sanctum (API) konfiguráció
├── .env                                    # Környezeti változók
├── composer.json                           # PHP függőségek
├── phpunit.xml                             # PHPUnit konfiguráció
└── README.md
```

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
    "updated_at": "2025-12-04T10:00:00Z"
}
```

### Resource (Erőforrás)
```php
{
    "id": 1,
    "name": "Meeting Room A",
    "type": "room",
    "description": "Large conference room for meetings",
    "available": true,
    "created_at": "2025-12-04T10:00:00Z",
    "updated_at": "2025-12-04T10:00:00Z"
}
```

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
    "updated_at": "2025-12-04T12:00:00Z"
}
```

## API Végpontok

### Nem védett végpontok:
- **GET** `/hello` - API teszteléshez
- **POST** `/register` - Regisztrációhoz
- **POST** `/login` - Bejelentkezéshez

<img width="538" height="131" alt="image" src="https://github.com/user-attachments/assets/f8dbb8d2-7c56-42d0-807c-4e988b251b73" />


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
<img width="879" height="305" alt="image" src="https://github.com/user-attachments/assets/932be355-2340-43cf-87f9-a2ea603a1bfb" />


**Kérés Törzse:**
```json
{
    "name":"Adrián3",
    "email": "adrian32@gmail.com",
    "phone": "21321312",
    "password": "Jelszo_2025",
    "password_confiramtion": "Jelszo_2025"
}
```

**Válasz (sikeres regisztráció):** `201 Created`
```json
{
    "message": "User registered successfully",
    "user": {
        "name": "Adrián3",
        "email": "adrian32@gmail.com",
        "phone": "21321312",
        "updated_at": "2025-12-07T17:40:51.000000Z",
        "created_at": "2025-12-07T17:40:51.000000Z",
        "id": 66
    }
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
<img width="851" height="330" alt="image" src="https://github.com/user-attachments/assets/4a91af5a-0805-480c-81c4-c423571dca26" />

**Kérés Törzse:**
```json
{
  "email": "john@example.com",
  "password": "SecurePass_2025"
}
```

**Válasz (sikeres bejelentkezés):** `200 OK`
```json
{
    "access_token": "2|7Fbr79b5zn8RxMfOqfdzZ31SnGWvgDidjahbdRfL2a98cfd8",
    "token_type": "Bearer"
}
```

**Válasz (hibás bejelentkezés):** `401 Unauthorized`
```json
{
  "message": "Invalid credentials"
}
```

---

> Innen kezdve minden végpont **autentifikált**, tehát a kérés `Authorization` headerében meg kell adni a tokent:
> 
> `Authorization: Bearer 2|7Fbr79b5zn8RxMfOqfdzZ31SnGWvgDidjahbdRfL2a98cfd8`

---
<img width="703" height="500" alt="image" src="https://github.com/user-attachments/assets/83ce5cdb-df73-4b42-8e4c-f262a24f8948" />

### **POST** `/logout` - Kijelentkezés

A jelenlegi autentikált felhasználó kijelentkeztetése és tokenjének törlése.
<img width="871" height="356" alt="image" src="https://github.com/user-attachments/assets/0ac0360d-2797-4ff9-8246-d625cac6b8a7" />


**Válasz (sikeres kijelentkezés):** `200 OK`
```json
{
  "message": "Logged out successfully"
}
```


---

### **GET** `/users/me` - Aktuális Profil

Saját felhasználói profil adatainak lekérése.
<img width="855" height="160" alt="image" src="https://github.com/user-attachments/assets/b75ff448-4842-4436-8f60-c2fa44e57f3d" />

**Válasz:** `200 OK`
```json
{
    "id": 5,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+36201234567"
}
```

---

### **PUT** `/users/me` - Profil Frissítése

Saját felhasználói adatok módosítása (név, e-mail, jelszó, telefonszám).
<img width="861" height="325" alt="image" src="https://github.com/user-attachments/assets/6fda8113-37c2-4af3-9943-edc5dc4b2bb2" />



**Kérés Törzse:**
```json
{
  "name": "New Name",
  "email": "newemail@example.com",
  "password": "NewPassword_2025",
  "phone": "+36209876543"
}
```

**Válasz (sikeres frissítés):** `200 OK`
```json
{
  "id": 5,
  "name": "New Name",
  "email": "newemail@example.com",
  "phone": "+36209876543"
}
```

**Válasz (validációs hiba):** `422 Unprocessable Entity`

---

### **GET** `/users` - Összes Felhasználó Listázása (Admin Csak)

Az összes felhasználó adatainak lekérése adminisztratív célokra.
<img width="847" height="158" alt="image" src="https://github.com/user-attachments/assets/d76d9356-2169-411c-b927-23307a8dee50" />

**Válasz:** `200 OK`
```json
[
    {
        "id": 1,
        "name": "admin",
        "email": "admin@example.com"
    },
    {
        "id": 5,
        "name": "John Doe",
        "email": "john@example.com"
    }
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
    "id": 5,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+36201234567"
}
```

**Válasz (felhasználó nem található):** `404 Not Found`

**Válasz (nem admin):** `403 Forbidden`

---

### **DELETE** `/users/{id}` - Felhasználó Törlése (Admin Csak)

Felhasználó fizikai törlése az adatbázisból.
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
| GET | `/ping` | Nyilvános | 200 OK | API teszteléshez |
| POST | `/register` | Nyilvános | 201 Created, 422 | Regisztráció |
| POST | `/login` | Nyilvános | 200 OK, 401 | Bejelentkezés |
| POST | `/logout` | Auth | 200 OK, 401 | Kijelentkezés |
| GET | `/users/me` | Auth | 200 OK, 401 | Saját profil |
| PUT | `/users/me` | Auth | 200 OK, 401, 422 | Profil frissítés |
| GET | `/users` | Admin | 200 OK, 403, 401 | Összes felhasználó |
| GET | `/users/{id}` | Admin | 200 OK, 403, 404, 401 | Konkrét felhasználó |
| DELETE | `/users/{id}` | Admin | 200 OK, 403, 404, 401 | Felhasználó törlés |
| GET | `/resources` | Auth | 200 OK, 401 | Erőforrások |
| GET | `/resources/{id}` | Auth | 200 OK, 401, 404 | Konkrét erőforrás |
| POST | `/resources` | Admin | 201 Created, 403, 401 | Erőforrás létrehozás |
| PUT | `/resources/{id}` | Admin | 200 OK, 403, 401 | Erőforrás módosítás |
| DELETE | `/resources/{id}` | Admin | 200 OK, 403, 401 | Erőforrás törlés |
| GET | `/reservations` | Auth | 200 OK, 401 | Foglalások |
| GET | `/reservations/{id}` | Auth | 200 OK, 401, 403, 404 | Konkrét foglalás |
| POST | `/reservations` | Auth | 201 Created, 401, 422 | Foglalás létrehozás |
| PUT | `/reservations/{id}` | Auth | 200 OK, 401, 403, 422 | Foglalás módosítás |
| DELETE | `/reservations/{id}` | Auth | 200 OK, 401, 403, 404 | Foglalás törlés |

## Autentifikáció és Jogosultságok

### Token-alapú Autentifikáció
- Minden autentifikált endpoint `Authorization: Bearer {token}` header-t igényel
- A token bejelentkezéskor jön vissza
- A tokeneket a `personal_access_tokens` táblában tároljuk
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

## Tesztek

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

**Összesen: 31 teszt, 100% pass rate ✅**

<img width="347" height="69" alt="image" src="https://github.com/user-attachments/assets/c39b88b8-efb6-468c-b6e2-cfbf2ff4e609" />

## Telepítés és Futtatás

### Előfeltételek
- PHP 8.2+
- MySQL 8.0+
- Composer
- Node.js (npm)
- XAMPP vagy más PHP webszerver

### Telepítési Lépések

**1. Projekt klónozása / megnyitása**
```bash
cd c:\xampp\htdocs\reservationSystem
```

**2. Composer függőségek telepítése**
```bash
composer install
```

**3. NPM függőségek telepítése**
```bash
npm install
```

**4. .env fájl konfigurálása**
```bash
cp .env.example .env
```

Szerkessze a `.env` fájlt az adatbázis beállításokkal:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=reservation_system
DB_USERNAME=root
DB_PASSWORD=
```

**5. Alkalmazás kulcs generálása**
```bash
php artisan key:generate
```

**6. Adatbázis migráció futtatása**
```bash
php artisan migrate
```

**7. Adatbázis seed-elés (tesztadatok)**
```bash
php artisan db:seed
```

**8. Development szerver futtatása**
```bash
php artisan serve
```

Az API ekkor elérhető lesz: `http://localhost:8000/api`

vagy XAMPP esetén: `http://localhost/reservationSystem/public/api`

---

## Fejlesztési Munkafolyamat

### A model → migration → controller → route → test sorrend követése

1. **Model létrehozása**
   ```bash
   php artisan make:model ModelName -mfsc
   ```
   - `-m`: Migration
   - `-f`: Factory
   - `-s`: Seeder
   - `-c`: Controller

2. **Migration szerkesztése**
   - Nyissa meg: `database/migrations/`
   - Definiálja az adatbázis táblát

3. **Controller metódusok**
   - Nyissa meg: `app/Http/Controllers/`
   - Implementálja a CRUD metódusokat

4. **Route-ok hozzáadása**
   - Szerkessze: `routes/api.php`
   - Adja hozzá az új endpoint-okat

5. **Tesztek írása**
   ```bash
   php artisan make:test ModelNameTest
   ```
   - Helyezze el: `tests/Feature/`

6. **Tesztek futtatása**
   ```bash
   php artisan test
   ```


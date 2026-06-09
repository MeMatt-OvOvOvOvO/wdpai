# TOPR Rescue

System zarządzania akcjami i zasobami ratowniczymi dla Tatrzańskiego Ochotniczego Pogotowia
Ratunkowego (TOPR). Aplikacja webowa pozwala koordynatorom planować i nadzorować akcje
ratunkowe, przypisywać do nich ratowników i sprzęt, a ratownikom — przeglądać przydzielone
zadania i zarządzać własnym profilem.

Projekt zaliczeniowy z przedmiotu **Wstęp do Projektowania Aplikacji Internetowych (WDPAI)**.

---

## Spis treści

1. [Stos technologiczny](#stos-technologiczny)
2. [Architektura aplikacji](#architektura-aplikacji)
3. [Zasady SOLID](#zasady-solid)
4. [Baza danych i diagram ERD](#baza-danych-i-diagram-erd)
5. [Trzecia Postać Normalna (3NF)](#trzecia-postać-normalna-3nf)
6. [Role i uprawnienia użytkowników](#role-i-uprawnienia-użytkowników)
7. [Bezpieczeństwo](#bezpieczeństwo)
8. [Uruchomienie projektu (Docker)](#uruchomienie-projektu-docker)
9. [Endpointy / mapa tras](#endpointy--mapa-tras)
10. [Scenariusz testowy](#scenariusz-testowy)
11. [Testy automatyczne](#testy-automatyczne)
12. [Zrzuty ekranu](#zrzuty-ekranu)
13. [Checklist zrealizowanych wymagań](#checklist-zrealizowanych-wymagań)

---

## Stos technologiczny

| Warstwa       | Technologia                                                       |
|---------------|-------------------------------------------------------------------|
| Backend       | PHP 8.3 (czysty OOP, bez frameworków), wzorzec MVC                |
| Baza danych   | PostgreSQL 16 (relacje, widoki, funkcje, wyzwalacze, transakcje)  |
| Frontend      | HTML5, CSS3 (własny system designu, zmienne CSS, dark theme)      |
| JavaScript    | Vanilla JS (Fetch API / AJAX, walidacja formularzy po stronie klienta) |
| Konteneryzacja| Docker + docker-compose (nginx, php-fpm, PostgreSQL, pgAdmin)     |
| Sesje / auth  | natywne sesje PHP + CSRF, hashowanie haseł bcrypt                  |

---

## Architektura aplikacji

Aplikacja zrealizowana jest w architekturze **MVC** bez użycia frameworków — własny front
controller (`index.php`), router (`Routing.php`) oraz podział na warstwy:

```
index.php                  → bootstrap, autoloader, globalny exception handler
Routing.php                → router (mapowanie ścieżek na kontrolery + metody)
src/
 ├─ Controllers/           → logika sterująca (SecurityController, DashboardController,
 │                            MissionController, EquipmentController, UserController, AppController)
 ├─ Repository/            → warstwa dostępu do danych (PDO + prepared statements)
 ├─ Entity/                → obiekty domenowe (User, Mission, Equipment)
 └─ Services/              → usługi pomocnicze (DatabaseService – singleton PDO,
                              SessionService – sesje, CSRF, role, limit logowań)
public/
 ├─ views/                 → widoki PHP (layout + podstrony per moduł)
 ├─ css/app.css            → system designu (zmienne CSS, dark theme, RWD)
 └─ js/app.js              → JS: sidebar, filtrowanie, walidacja formularzy, AJAX
```

Przepływ żądania: `nginx → index.php → Routing::run() → Controller::method() → Repository
(PDO/PostgreSQL) → render(View)`.

Każdy kontroler dziedziczy po `AppController`, który dostarcza wspólne metody pomocnicze:
`render()`, `redirect()`, `json()`/`jsonError()`, `isPost()`/`isGet()`, `getPost()`/`getQuery()`.

---

## Zasady SOLID

Projekt świadomie stosuje zasady SOLID w warstwie backendu (PHP OOP), z pragmatycznym podejściem właściwym dla projektu MVC bez frameworka.

### S — Single Responsibility Principle

Każda klasa ma dokładnie jeden powód do zmiany:

- `MissionController`, `EquipmentController`, `UserController`, `SecurityController` — każdy kontroler obsługuje wyłącznie jeden moduł domenowy; żadna logika prezentacji ani dostępu do danych nie trafia do kontrolera
- `MissionRepository`, `EquipmentRepository`, `UserRepository` — wyłącznie dostęp do danych (PDO + prepared statements); zero logiki biznesowej
- `User`, `Mission`, `Equipment` (encje) — wyłącznie dane + logika domenowa (np. `getStatusBadgeClass()`, `isActive()`, `getServiceLifePct()`); zero zapytań SQL
- `SessionService` — zarządzanie sesją PHP, tokenami CSRF, rolami i limitem logowań; nic więcej
- `DatabaseService` — wyłącznie singleton PDO; żadnych zapytań domenowych

### O — Open/Closed Principle

Klasy są otwarte na rozszerzanie, zamknięte na modyfikację:

- `AppController` (klasa bazowa) dostarcza `render()`, `redirect()`, `json()`, `requireLogin()` itd. — dodanie nowego modułu wymaga **wyłącznie** napisania nowego kontrolera rozszerzającego `AppController`, bez dotykania istniejącego kodu
- `Routing.php` — nowe trasy dodaje się przez dopisanie wpisu; istniejące mapowania pozostają nienaruszone

### L — Liskov Substitution Principle

Klasy pochodne można używać wszędzie tam, gdzie oczekiwana jest klasa bazowa:

- Wszystkie kontrolery rozszerzają `AppController` i respektują jego kontrakt — `render()`, `redirect()`, `json()` działają identycznie w każdej podklasie, nie zmieniając semantyki
- Encje (`User`, `Mission`, `Equipment`) są niezależnymi, samodzielnymi obiektami bez efektów ubocznych — warstwy kontrolera i widoku mogą na nich polegać bez znajomości konkretnego typu

### I — Interface Segregation Principle

W projekcie nie zastosowano PHP-owych interfejsów (np. `RepositoryInterface`). Jest to świadoma decyzja: przy trzech repozytoriach z rozłącznym API (brak polimorficznego użycia między nimi) formalne interfejsy zwiększyłyby tylko boilerplate bez realnej korzyści projektowej. Każde repozytorium udostępnia dokładnie taki zestaw metod, jakiego wymaga obsługujący je kontroler — bez „grubych" interfejsów zmuszających do implementacji niepotrzebnych metod.

### D — Dependency Inversion Principle

Repozytoria pobierają połączenie z bazą przez `DatabaseService::getInstance()` (statyczny singleton), a `UserRepository` jest singletonem dostępnym przez `UserRepository::getInstance()`. Jest to świadomy kompromis: w projekcie bez kontenera DI pełne wstrzykiwanie zależności przez konstruktor zwiększyłoby złożoność kodu bardziej niż przyniosłoby korzyści. Wyższa warstwa (kontrolery) nie zależy od szczegółów implementacji PDO — dostają gotowy obiekt repozytorium, nie wiedzą nic o sterownikach ani konfiguracji połączenia.

---

## Baza danych i diagram ERD

Baza `topr_rescue` (PostgreSQL) zawiera 8 tabel połączonych relacjami **1:1**, **1:N** i **M:N**,
2 widoki, funkcję, 2 wyzwalacze oraz transakcje w warstwie repozytoriów. Pełny skrypt
inicjalizujący (struktura + dane przykładowe) znajduje się w
[`docker/db/init/init.sql`](docker/db/init/init.sql) i jest automatycznie ładowany przy starcie
kontenera bazy danych.

### Diagram ERD

```mermaid
erDiagram
    ROLES ||--o{ USERS : "określa rolę"
    USERS ||--|| PROFILES : "ma profil (1:1)"
    USERS ||--o{ MISSIONS : "tworzy (created_by)"
    USERS ||--o{ MISSION_RESCUERS : "bierze udział"
    MISSIONS ||--o{ MISSION_RESCUERS : "ma przypisanych"
    MISSIONS ||--o{ EQUIPMENT_LOANS : "wykorzystuje"
    EQUIPMENT ||--o{ EQUIPMENT_LOANS : "jest wypożyczany"
    INCIDENT_TYPES ||--o{ MISSIONS : "kategoryzuje"
    EQUIPMENT_TYPES ||--o{ EQUIPMENT : "kategoryzuje"

    ROLES {
        int id PK
        string name "coordinator / rescuer"
    }
    USERS {
        int id PK
        string username
        string email
        string password "hash bcrypt"
        int role_id FK
        timestamptz created_at
        bool is_active
    }
    PROFILES {
        int user_id PK_FK "1:1 z users"
        string first_name
        string last_name
        string rank
        string phone
        text bio
        text avatar_url
    }
    INCIDENT_TYPES {
        int id PK
        string name
    }
    MISSIONS {
        int id PK
        string title
        string location
        string coordinates
        int incident_type_id FK
        string status "open/active/completed/cancelled"
        timestamptz start_time
        timestamptz end_time
        text description
        int created_by FK
    }
    EQUIPMENT_TYPES {
        int id PK
        string name
        text description
    }
    EQUIPMENT {
        int id PK
        string name
        string serial_number
        int type_id FK
        string status "ready/in_use/maintenance/retired/lost"
        date last_inspection
        int service_life_pct
    }
    MISSION_RESCUERS {
        int mission_id PK_FK
        int user_id PK_FK
        string role "leader/medic/rescuer"
        timestamptz assigned_at
    }
    EQUIPMENT_LOANS {
        int id PK
        int mission_id FK
        int equipment_id FK
        int quantity
        timestamptz loaned_at
        timestamptz returned_at
    }
```

> Diagram w formacie [Mermaid](https://mermaid.js.org/) — renderuje się automatycznie w
> podglądzie Markdown na GitHub/GitLab oraz w edytorach typu VS Code z odpowiednim pluginem.

### Relacje

- **1:1** — `users` ↔ `profiles` (klucz główny `profiles.user_id` jest jednocześnie kluczem obcym do `users.id`)
- **1:N** — `roles` → `users`, `incident_types` → `missions`, `equipment_types` → `equipment`, `users` → `missions` (pole `created_by`)
- **M:N** — `missions` ↔ `users` poprzez tabelę łączącą `mission_rescuers` (z dodatkowymi atrybutami: `role`, `assigned_at`), `missions` ↔ `equipment` poprzez `equipment_loans` (z atrybutami `quantity`, `loaned_at`, `returned_at`)

### Akcje na referencjach (ON DELETE / ON UPDATE)

Każdy klucz obcy ma świadomie dobraną akcję referencyjną, np.:

- `users.role_id … ON DELETE RESTRICT` — nie można usunąć roli, jeśli przypisani są do niej użytkownicy
- `profiles.user_id … ON DELETE CASCADE` — usunięcie użytkownika usuwa też jego profil
- `missions.incident_type_id … ON DELETE SET NULL` — usunięcie typu zdarzenia nie usuwa powiązanych akcji, tylko czyści referencję
- `mission_rescuers` i `equipment_loans … ON DELETE CASCADE` — usunięcie akcji ratunkowej usuwa powiązane przypisania ratowników i wypożyczenia sprzętu

### Widoki (views)

- **`active_missions_view`** — łączy `missions`, `incident_types`, `mission_rescuers` i `users`;
  prezentuje aktywne/otwarte akcje wraz z liczbą i listą przypisanych ratowników oraz czasem
  trwania (agregacje `COUNT`, `STRING_AGG`, `EXTRACT`)
- **`equipment_usage_report`** — łączy `equipment`, `equipment_types`, `equipment_loans`
  i `missions`; raportuje, ile razy i w jakich akcjach użyto danego sprzętu

### Funkcja

- **`calculate_mission_duration(p_mission_id INT)`** — zwraca czas trwania akcji w minutach
  (dla trwających akcji liczy do chwili obecnej, dla zakończonych — do `end_time`)

### Wyzwalacze (triggers)

- **`trg_activate_mission_on_rescuer`** (`AFTER INSERT ON mission_rescuers`) — automatycznie
  zmienia status akcji z `open` na `active` w momencie przypisania pierwszego ratownika
- **`trg_equipment_loan_status`** (`AFTER INSERT OR DELETE ON equipment_loans`) — automatycznie
  ustawia status sprzętu na `in_use` po wypożyczeniu i z powrotem na `ready`, gdy sprzęt nie jest
  już używany w żadnej aktywnej akcji

### Transakcje

Operacje wieloetapowe w warstwie repozytoriów są opakowane w transakcje
(`beginTransaction()` / `commit()` / `rollBack()`), m.in.:

- `UserRepository::createUser()` — utworzenie konta użytkownika i powiązanego profilu (1:1) jako
  jedna spójna operacja
- `MissionRepository::createMission()` — utworzenie akcji ratunkowej wraz z powiązanymi rekordami

### Eksport bazy

Pełna struktura wraz z danymi przykładowymi jest dostępna jako plik SQL:
[`docker/db/init/init.sql`](docker/db/init/init.sql). Plik ten jest automatycznie wykonywany
przy pierwszym uruchomieniu kontenera PostgreSQL (mechanizm `docker-entrypoint-initdb.d`).

---

## Trzecia Postać Normalna (3NF)

Schemat bazy danych spełnia **Trzecią Postać Normalną (3NF)**, co gwarantuje brak redundancji danych i odporność na anomalie wstawiania, aktualizacji i usuwania.

### 1NF — Pierwsza Postać Normalna ✅

- Wszystkie kolumny przechowują wartości **atomowe** — brak list, tablic ani struktur zagnieżdżonych w polach
- Każda tabela ma zdefiniowany **klucz główny** (PK)
- Brak grup powtarzających się (repeating groups)

### 2NF — Druga Postać Normalna ✅

Każdy atrybut niekluczowy zależy od **całego** klucza głównego (dotyczy tabel ze złożonym PK):

- `mission_rescuers (mission_id, user_id)` — atrybuty `role` i `assigned_at` opisują konkretne **przypisanie** ratownika do akcji, zależą od obu kolumn klucza jednocześnie, nie tylko od jednej z nich
- `equipment_loans` — klucz prosty (`id`), więc 2NF spełniona trywialnie
- Wszystkie pozostałe tabele mają klucze proste — 2NF spełniona trywialnie

### 3NF — Trzecia Postać Normalna ✅

Brak **zależności tranzytywnych** — żaden atrybut niekluczowy nie zależy od innego atrybutu niekluczowego:

| Tabela | Potencjalne zagrożenie 3NF | Zastosowane rozwiązanie |
|--------|---------------------------|-------------------------|
| `users` | nazwa roli mogłaby być przechowywana bezpośrednio | `role_id FK → roles(id)` — nazwa roli wyłącznie w tabeli `roles` |
| `missions` | nazwa typu zdarzenia mogłaby być przechowywana bezpośrednio | `incident_type_id FK → incident_types(id)` |
| `equipment` | nazwa kategorii sprzętu mogłaby być przechowywana bezpośrednio | `type_id FK → equipment_types(id)` |
| `users` | atrybuty profilu (imię, nazwisko, telefon, bio) mogłyby zależeć od e-maila | Wydzielona tabela `profiles` w relacji 1:1 — atrybuty profilu zależą wyłącznie od `user_id` PK |

Wszystkie słowniki (`roles`, `incident_types`, `equipment_types`) są osobnymi tabelami z własnym PK. Referencje przez klucze obce eliminują redundancję: zmiana np. nazwy roli wymaga aktualizacji **jednego** wiersza w `roles`, a nie setek wierszy w `users`.

---

## Role i uprawnienia użytkowników

System wspiera dwie role, przechowywane w tabeli `roles` i przypisane do użytkownika przez
`users.role_id`:

| Rola          | Uprawnienia                                                                 |
|---------------|------------------------------------------------------------------------------|
| `coordinator` | pełny dostęp: tworzenie/edycja/usuwanie akcji i sprzętu, przypisywanie ratowników i sprzętu do akcji, zarządzanie kontami użytkowników |
| `rescuer`     | dostęp tylko do odczytu (przegląd akcji, sprzętu), zarządzanie własnym profilem |

Kontrola dostępu jest realizowana **dwuwarstwowo**:

1. **Backend** — każda akcja kontrolera wymaga `SessionService::requireLogin()`, a operacje
   modyfikujące dodatkowo `SessionService::requireCoordinator()`. Próba wejścia na chronioną
   ścieżkę bez odpowiedniej roli kończy się stroną błędu **403 Forbidden** — niezależnie od tego,
   czy użytkownik trafi tam przez UI, czy wpisując adres URL bezpośrednio.
2. **Frontend** — widoki sprawdzają `SessionService::isCoordinator()` i warunkowo pokazują/ukrywają
   przyciski akcji (edycja, usuwanie, zarządzanie), dzięki czemu interfejs ratownika jest czytelny
   i nie sugeruje niedostępnych operacji.

---

## Bezpieczeństwo

Zaimplementowane zabezpieczenia (zgodnie z listą kontrolną *Security Bingo* udostępnioną przez
prowadzącego):

- ✅ Ochrona przed SQL injection — wyłącznie *prepared statements* z parametrami nazwanymi (PDO)
- ✅ Generyczne komunikaty błędów logowania ("Nieprawidłowy email lub hasło") — brak enumeracji kont
- ✅ Walidacja formatu email po stronie serwera (`filter_var`)
- ✅ `UserRepository` zarządzany jako singleton (`getInstance()`)
- ✅ `login`/`register` przyjmują dane tylko metodą POST, GET tylko renderuje formularz
- ✅ Token CSRF w formularzach logowania i rejestracji, weryfikowany `hash_equals()`
- ✅ Limity długości danych wejściowych (np. e-mail, hasło, nazwa użytkownika)
- ✅ Hasła przechowywane jako hash `bcrypt` (`password_hash`/`password_verify`)
- ✅ Hasła nigdy nie trafiają do logów ani do widoków
- ✅ Regeneracja ID sesji po poprawnym logowaniu (`session_regenerate_id`) — ochrona przed session fixation
- ✅ Cookie sesyjne z flagami `HttpOnly`, `SameSite=Lax` oraz dynamicznie ustawianą flagą `Secure` (aktywną na HTTPS)
- ✅ Limit prób logowania — po 5 nieudanych próbach konto/adres jest blokowane na 5 minut (kod HTTP 429)
- ✅ Walidacja złożoności hasła (min. 8 znaków)
- ✅ Sprawdzanie unikalności e-maila przy rejestracji bez ujawniania, czy konto istnieje
- ✅ Escapowanie danych w widokach (`htmlspecialchars`) — ochrona przed XSS
- ✅ Brak surowych błędów / stack trace w produkcji (`display_errors=Off`, własny `set_exception_handler`)
- ✅ Sensowne kody HTTP (400/403/404/429/500) zwracane przy błędach
- ✅ Zapytania SQL pobierają tylko niezbędne kolumny (bez `SELECT *`)
- ✅ Poprawne wylogowanie — pełne zniszczenie sesji (`session_unset`, `session_destroy`, wyczyszczenie cookie)
- ✅ Logowanie nieudanych prób logowania do audytu (e-mail + adres IP, nigdy hasło)
- ⏳ Wymuszenie HTTPS dla logowania/rejestracji — wymaga certyfikatu SSL i konfiguracji nginx;
  zaplanowane jako rozszerzenie produkcyjne (kod jest już przygotowany: `SessionService` ustawia
  flagę `Secure` automatycznie, gdy wykryje połączenie HTTPS)

---

## Uruchomienie projektu (Docker)

### Wymagania

- Docker oraz Docker Compose

### Kroki

1. Sklonuj repozytorium i przejdź do katalogu projektu.
2. (Opcjonalnie) skopiuj plik `.env.example` do `.env` i dostosuj zmienne środowiskowe.
3. Zbuduj i uruchom kontenery:

   ```bash
   docker-compose up --build
   ```

4. Poczekaj, aż PostgreSQL zainicjalizuje bazę danych (skrypt `init.sql` wykona się automatycznie
   przy pierwszym starcie — utworzy tabele, widoki, funkcje, wyzwalacze i wstawi dane przykładowe).
5. Otwórz aplikację w przeglądarce: **http://localhost:8080**
6. Panel pgAdmin (podgląd bazy danych): **http://localhost:5050**
   (login: `admin@example.com`, hasło: `admin`)

### Konta testowe

Dane logowania do przykładowych kont (hasło dla wszystkich: `password123`):

| Rola          | E-mail                  | Hasło         |
|---------------|-------------------------|---------------|
| Koordynator   | koordynator@topr.pl     | password123   |
| Ratownik      | ratownik1@topr.pl       | password123   |
| Ratownik      | ratownik2@topr.pl       | password123   |
| Ratownik      | ratownik3@topr.pl       | password123   |

### Restart "od zera"

Jeśli chcesz, aby skrypt inicjalizujący bazę wykonał się ponownie (np. po zmianie `init.sql`),
usuń wolumeny i zbuduj kontenery od nowa:

```bash
docker-compose down -v
docker-compose up --build
```

---

## Endpointy / mapa tras

| Metoda | Ścieżka                              | Kontroler / akcja                      | Dostęp        |
|--------|--------------------------------------|----------------------------------------|---------------|
| GET/POST | `/login`, `/register`              | `SecurityController`                    | publiczny     |
| GET    | `/logout`                            | `SecurityController::logout`            | zalogowany    |
| GET    | `/dashboard`                         | `DashboardController::index`            | zalogowany    |
| GET    | `/missions`, `/missions/{id}`        | `MissionController::index/show`         | zalogowany    |
| GET/POST | `/missions/new`, `/missions/{id}/edit`, `/missions/{id}/delete` | `MissionController` | koordynator |
| POST   | `/missions/{id}/rescuers[/remove]`   | `MissionController::add/removeRescuer`  | koordynator   |
| GET    | `/equipment`, `/equipment/{id}`      | `EquipmentController::index/show`       | zalogowany    |
| GET/POST | `/equipment/new`, `/equipment/{id}/edit`, `/equipment/{id}/delete` | `EquipmentController` | koordynator |
| GET    | `/users`, `/users/{id}/edit`         | `UserController::index/edit`            | koordynator   |
| GET/POST | `/profile`                         | `UserController::profile/updateProfile` | zalogowany    |
| GET    | `/api/missions`, `/api/equipment`, `/api/users`, `/api/stats` | API (Fetch/AJAX) | zalogowany |
| POST   | `/api/missions/{id}/equipment`       | `EquipmentController::apiLoanEquipment` | koordynator   |

---

## Scenariusz testowy

Poniższy scenariusz pozwala ręcznie zweryfikować kluczowe funkcje aplikacji:

1. **Logowanie** — wejdź na `/login`, zaloguj się jako `koordynator@topr.pl` / `password123`.
   Sprawdź, że po poprawnym logowaniu trafiasz na `/dashboard`.
2. **Walidacja i CSRF** — spróbuj wysłać formularz logowania z niepoprawnym formatem e-maila —
   pole powinno zostać oznaczone na czerwono (walidacja JS), a po wysłaniu serwer zwróci komunikat
   o błędnym formacie.
3. **Limit prób logowania (A4)** — wyloguj się i 5-krotnie wprowadź błędne hasło dla tego samego
   adresu e-mail. Przy szóstej próbie formularz powinien zwrócić komunikat o blokadzie czasowej
   (HTTP 429) zamiast standardowego "Nieprawidłowy email lub hasło".
4. **Role i uprawnienia** — zaloguj się jako `ratownik1@topr.pl` / `password123`. Sprawdź, że:
   - w menu i widokach nie pojawiają się przyciski "Edytuj"/"Usuń"/"Nowa akcja",
   - bezpośrednie wejście na `/missions/new` lub `/users` zwraca stronę **403 Forbidden**.
5. **Tworzenie akcji ratunkowej (koordynator)** — zaloguj się jako koordynator, utwórz nową akcję
   przez `/missions/new`, przypisz do niej ratownika. Sprawdź w widoku szczegółów, że status akcji
   automatycznie zmienił się na `active` (działanie wyzwalacza `trg_activate_mission_on_rescuer`).
6. **Wypożyczenie sprzętu (AJAX)** — w widoku szczegółów akcji dodaj sprzęt przez formularz
   (Fetch API, `/api/missions/{id}/equipment`). Sprawdź w module "Sprzęt", że status pozycji
   zmienił się na `in_use` (działanie wyzwalacza `trg_equipment_loan_status`).
7. **Widoki SQL** — w pgAdmin wykonaj `SELECT * FROM active_missions_view;` oraz
   `SELECT * FROM equipment_usage_report;`, aby zobaczyć zagregowane dane z kilku tabel.
8. **Wylogowanie** — kliknij "Wyloguj". Spróbuj wrócić na `/dashboard` przyciskiem "wstecz" —
   aplikacja powinna przekierować z powrotem na `/login` (sesja została zniszczona).

---

## Testy automatyczne

Projekt zawiera dwa rodzaje testów automatycznych, uzupełniających ręczny scenariusz testowy
opisany powyżej:

### 1. Testy jednostkowe (PHPUnit)

Znajdują się w katalogu [`tests/Unit`](tests/Unit) i sprawdzają logikę, którą można odizolować
od bazy danych i warstwy HTTP:

- **`SessionServiceTest`** — generowanie/walidacja/rotacja tokenów CSRF, obsługa komunikatów
  flash, sprawdzanie ról (`isCoordinator`/`isRescuer`/`isLoggedIn`) oraz mechanizm **A4**
  (limit 5 prób logowania, blokada na 5 minut, niezależność liczników dla różnych adresów e-mail,
  czyszczenie licznika po udanym logowaniu),
- **`UserEntityTest`** — mapowanie danych encji `User`, logika `isCoordinator`/`isRescuer`/`isActive`
  oraz spójność `password_hash`/`password_verify` używana przy logowaniu,
- **`MissionEntityTest`** — logika statusów akcji ratunkowej (`isActive`, `isCompleted`,
  `getStatusBadgeClass`) sterująca wyglądem i filtrowaniem w widokach.

Uruchomienie (wewnątrz kontenera PHP, gdzie dostępny jest interpreter PHP 8.3):

```bash
docker-compose exec php sh -c "composer install && composer run test:unit"
```

albo lokalnie, jeśli masz zainstalowany PHP 8.3+ i Composer:

```bash
composer install
composer run test:unit
# lub bezpośrednio:
vendor/bin/phpunit --configuration phpunit.xml.dist
```

> Testy jednostkowe celowo **nie łączą się z PostgreSQL** — dzięki temu są szybkie,
> deterministyczne i mogą być uruchamiane w pipeline CI bez zależności zewnętrznych.
> Logika wymagająca bazy danych (repozytoria, widoki, wyzwalacze, funkcje) jest
> zweryfikowana przez testy integracyjne opisane niżej oraz ręczny scenariusz testowy.

### 2. Testy integracyjne (curl)

Skrypt [`tests/Integration/run.sh`](tests/Integration/run.sh) odpytuje **działającą aplikację**
(uruchomioną przez `docker-compose up`) i sprawdza jej zachowanie „od zewnątrz” — dokładnie tak,
jak zrobiłaby to przeglądarka: kody odpowiedzi HTTP, przekierowania, ochronę tras przed
niezalogowanymi/nieuprawnionymi użytkownikami, działanie CSRF oraz limitu prób logowania (A4).

Uruchomienie (gdy `docker-compose up` już działa na `localhost:8080`):

```bash
./tests/Integration/run.sh
```

Skrypt weryfikuje m.in.:

- dostępność stron publicznych (`/login`, `/register`) i obecność formularza wraz z tokenem CSRF,
- zwracanie kodu **404** dla nieistniejących tras,
- przekierowanie niezalogowanych użytkowników z tras chronionych (`/dashboard`, `/missions`,
  `/equipment`, `/users`, `/profile`) na `/login`,
- odrzucenie żądania logowania bez poprawnego tokenu CSRF (kod **403**),
- generyczny komunikat błędu logowania bez ujawniania, czy konto istnieje (**B1**),
- walidację formatu adresu e-mail (**C1**),
- zwrócenie kodu **429** po przekroczeniu limitu 5 nieudanych prób logowania (**A4**),
- poprawne logowanie kontem koordynatora, dostęp do tras chronionych oraz pełne
  wylogowanie (zniszczenie sesji).

Adres bazowy oraz dane testowego konta koordynatora można nadpisać zmiennymi środowiskowymi:

```bash
BASE_URL=http://localhost:8080 \
TEST_COORDINATOR_EMAIL=koordynator@topr.pl \
TEST_COORDINATOR_PASSWORD=password123 \
./tests/Integration/run.sh
```

---

## Zrzuty ekranu

> _Miejsce na zrzuty ekranu kluczowych widoków aplikacji — uzupełnij przed oddaniem projektu:_

- Ekran logowania
- Dashboard koordynatora (statystyki, mapa, brief taktyczny)
- Lista akcji ratunkowych i widok szczegółów akcji
- Moduł sprzętu (lista, status, formularz dodawania)
- Zarządzanie użytkownikami (widok koordynatora) i profil użytkownika

---

## Checklist zrealizowanych wymagań

| Kryterium                                              | Status |
|--------------------------------------------------------|:------:|
| Dokumentacja w README.md (opis, ERD, screeny, flow)     | ✅ |
| Docker                                                  | ✅ |
| Architektura aplikacji MVC                              | ✅ |
| Kod napisany obiektowo (backend)                        | ✅ |
| Zasady SOLID (opis + analiza w README)                  | ✅ |
| Diagram ERD                                             | ✅ |
| Trzecia Postać Normalna — 3NF (opis + analiza w README) | ✅ |
| Git (systematyka commitów, merge do main)               | ✅ |
| Realizacja tematu                                       | ✅ |
| HTML                                                    | ✅ |
| PostgreSQL                                              | ✅ |
| Złożoność bazy danych (relacje 1:1, 1:N, M:N)           | ✅ |
| Eksport bazy do pliku .sql                              | ✅ |
| PHP                                                     | ✅ |
| JavaScript                                              | ✅ |
| Fetch API (AJAX)                                        | ✅ |
| Design                                                  | ✅ |
| Responsywność (desktop + mobile)                        | ✅ |
| Logowanie                                               | ✅ |
| Sesja użytkownika                                       | ✅ |
| Uprawnienia użytkowników                                | ✅ |
| Role użytkowników (co najmniej dwie)                    | ✅ |
| Wylogowywanie                                           | ✅ |
| Widoki, wyzwalacze, funkcje, transakcje                 | ✅ |
| Akcje na referencjach                                   | ✅ |
| Bezpieczeństwo                                          | ✅ |
| Brak replikacji kodu                                    | ✅ |
| Czystość i przejrzystość kodu                           | ✅ |

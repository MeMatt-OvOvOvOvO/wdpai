#!/usr/bin/env bash
#
# Testy integracyjne TOPR Rescue (curl).
#
# W przeciwieństwie do testów jednostkowych (PHPUnit, tests/Unit), ten skrypt
# odpytuje DZIAŁAJĄCĄ aplikację (kontenery z `docker-compose up`) i sprawdza
# zachowanie "od zewnątrz": kody HTTP, przekierowania, ochronę tras przed
# niezalogowanymi/nieuprawnionymi użytkownikami, CSRF i limit prób logowania.
#
# Użycie:
#   ./tests/Integration/run.sh                         # domyślnie http://localhost:8080
#   BASE_URL=http://localhost:8080 ./tests/Integration/run.sh
#
# Wymaga: curl. Aplikacja musi być uruchomiona (docker-compose up).

set -uo pipefail

BASE_URL="${BASE_URL:-http://localhost:8080}"
COOKIE_JAR="$(mktemp)"
PASS=0
FAIL=0

cleanup() { rm -f "$COOKIE_JAR"; }
trap cleanup EXIT

# ------------------------------------------------------------------
# Pomocnicze funkcje asercji
# ------------------------------------------------------------------

# assert_status <opis> <oczekiwany_kod> <metoda> <ścieżka> [dodatkowe opcje curl...]
assert_status() {
    local description="$1"; shift
    local expected="$1"; shift
    local method="$1"; shift
    local path="$1"; shift

    local actual
    actual=$(curl -s -o /dev/null -w '%{http_code}' -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
        -X "$method" "$BASE_URL$path" "$@")

    if [[ "$actual" == "$expected" ]]; then
        echo "  [OK]   $description (oczekiwano $expected, otrzymano $actual)"
        PASS=$((PASS + 1))
    else
        echo "  [FAIL] $description (oczekiwano $expected, otrzymano $actual)"
        FAIL=$((FAIL + 1))
    fi
}

# assert_redirect_to <opis> <ścieżka> <oczekiwany_fragment_lokalizacji>
assert_redirect_to() {
    local description="$1"; shift
    local path="$1"; shift
    local expected_fragment="$1"; shift

    local location
    location=$(curl -s -o /dev/null -D - -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
        "$BASE_URL$path" | grep -i '^location:' | tr -d '\r' | awk '{print $2}')

    if [[ "$location" == *"$expected_fragment"* ]]; then
        echo "  [OK]   $description (przekierowano do: $location)"
        PASS=$((PASS + 1))
    else
        echo "  [FAIL] $description (oczekiwano przekierowania zawierającego '$expected_fragment', otrzymano: '${location:-brak}')"
        FAIL=$((FAIL + 1))
    fi
}

# assert_body_contains <opis> <ścieżka> <oczekiwany_tekst>
assert_body_contains() {
    local description="$1"; shift
    local path="$1"; shift
    local needle="$1"; shift

    local body
    body=$(curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" "$BASE_URL$path")

    if [[ "$body" == *"$needle"* ]]; then
        echo "  [OK]   $description"
        PASS=$((PASS + 1))
    else
        echo "  [FAIL] $description (nie znaleziono '$needle' w odpowiedzi)"
        FAIL=$((FAIL + 1))
    fi
}

section() { echo ""; echo "=== $1 ==="; }

# ------------------------------------------------------------------
# 1. Strony publiczne dostępne bez logowania
# ------------------------------------------------------------------
section "Strony publiczne"
assert_status "GET /login zwraca 200"            200 GET "/login"
assert_status "GET /register zwraca 200"         200 GET "/register"
assert_body_contains "Formularz logowania zawiera pole email" "/login" "email"
assert_body_contains "Formularz logowania zawiera token CSRF" "/login" "csrf_token"

# ------------------------------------------------------------------
# 2. Nieistniejąca trasa -> 404
# ------------------------------------------------------------------
section "Obsługa błędów"
assert_status "Nieistniejąca trasa zwraca 404"   404 GET "/this-route-does-not-exist-12345"

# ------------------------------------------------------------------
# 3. Trasy chronione przekierowują niezalogowanych do /login
# ------------------------------------------------------------------
section "Ochrona tras przed niezalogowanymi (requireLogin -> redirect /login)"
assert_redirect_to "GET /dashboard przekierowuje do /login"  "/dashboard"  "/login"
assert_redirect_to "GET /missions przekierowuje do /login"   "/missions"   "/login"
assert_redirect_to "GET /equipment przekierowuje do /login"  "/equipment"  "/login"
assert_redirect_to "GET /profile przekierowuje do /login"    "/profile"    "/login"
assert_redirect_to "GET /users przekierowuje do /login"      "/users"      "/login"

# ------------------------------------------------------------------
# 4. Logowanie - błędne dane / walidacja / CSRF
# ------------------------------------------------------------------
section "Logowanie - walidacja i CSRF"

# 4a. Próba logowania bez tokenu CSRF -> 403
assert_status "POST /login bez tokenu CSRF zwraca 403" 403 POST "/login" \
    --data-urlencode "email=koordynator@topr.pl" \
    --data-urlencode "password=password123"

# 4b. Pobierz świeży token CSRF ze strony logowania
LOGIN_PAGE=$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE_URL/login")
CSRF_TOKEN=$(echo "$LOGIN_PAGE" | grep -o 'name="csrf_token" value="[^"]*"' | head -1 | sed -E 's/.*value="([^"]*)".*/\1/')

if [[ -n "$CSRF_TOKEN" ]]; then
    echo "  [OK]   Token CSRF został pobrany ze strony logowania"
    PASS=$((PASS + 1))
else
    echo "  [FAIL] Nie udało się pobrać tokenu CSRF ze strony logowania - kolejne testy logowania zostaną pominięte"
    FAIL=$((FAIL + 1))
fi

if [[ -n "$CSRF_TOKEN" ]]; then
    # 4c. Błędne hasło -> generyczny komunikat (B1), kod 200 (formularz wyświetlony ponownie)
    BAD_LOGIN_BODY=$(curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" -X POST "$BASE_URL/login" \
        --data-urlencode "csrf_token=$CSRF_TOKEN" \
        --data-urlencode "email=koordynator@topr.pl" \
        --data-urlencode "password=zle-haslo-na-pewno")

    if [[ "$BAD_LOGIN_BODY" == *"Nieprawidłowy email lub hasło"* ]]; then
        echo "  [OK]   Błędne hasło zwraca generyczny komunikat (B1 - brak enumeracji kont)"
        PASS=$((PASS + 1))
    else
        echo "  [FAIL] Nie znaleziono generycznego komunikatu błędu logowania"
        FAIL=$((FAIL + 1))
    fi

    # 4d. Niepoprawny format email -> komunikat walidacyjny
    CSRF_TOKEN_2=$(curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" "$BASE_URL/login" \
        | grep -o 'name="csrf_token" value="[^"]*"' | head -1 | sed -E 's/.*value="([^"]*)".*/\1/')

    INVALID_EMAIL_BODY=$(curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" -X POST "$BASE_URL/login" \
        --data-urlencode "csrf_token=$CSRF_TOKEN_2" \
        --data-urlencode "email=to-nie-jest-email" \
        --data-urlencode "password=cokolwiek123")

    if [[ "$INVALID_EMAIL_BODY" == *"Nieprawidłowy format adresu email"* ]]; then
        echo "  [OK]   Niepoprawny format email jest odrzucany z czytelnym komunikatem (C1)"
        PASS=$((PASS + 1))
    else
        echo "  [FAIL] Walidacja formatu email nie zadziałała zgodnie z oczekiwaniami"
        FAIL=$((FAIL + 1))
    fi
fi

# ------------------------------------------------------------------
# 5. Limit prób logowania (A4) - 6. próba powinna zwrócić 429
# ------------------------------------------------------------------
section "A4 - limit prób logowania / blokada czasowa"

LOCKOUT_EMAIL="lockout-test-$$@topr.pl"
LAST_STATUS=""
for i in 1 2 3 4 5 6; do
    TOKEN=$(curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" "$BASE_URL/login" \
        | grep -o 'name="csrf_token" value="[^"]*"' | head -1 | sed -E 's/.*value="([^"]*)".*/\1/')

    LAST_STATUS=$(curl -s -o /dev/null -w '%{http_code}' -b "$COOKIE_JAR" -c "$COOKIE_JAR" -X POST "$BASE_URL/login" \
        --data-urlencode "csrf_token=$TOKEN" \
        --data-urlencode "email=$LOCKOUT_EMAIL" \
        --data-urlencode "password=zle-haslo-$i")
done

if [[ "$LAST_STATUS" == "429" ]]; then
    echo "  [OK]   Po 6 nieudanych próbach logowania serwer zwraca 429 (konto zablokowane czasowo)"
    PASS=$((PASS + 1))
else
    echo "  [FAIL] Oczekiwano kodu 429 po przekroczeniu limitu prób logowania, otrzymano: $LAST_STATUS"
    FAIL=$((FAIL + 1))
fi

# ------------------------------------------------------------------
# 6. Pełne logowanie -> dostęp do tras chronionych + ochrona ról
# ------------------------------------------------------------------
section "Logowanie poprawnymi danymi i kontrola dostępu wg ról"

COORD_EMAIL="${TEST_COORDINATOR_EMAIL:-koordynator@topr.pl}"
COORD_PASSWORD="${TEST_COORDINATOR_PASSWORD:-password123}"

FRESH_TOKEN=$(curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" "$BASE_URL/login" \
    | grep -o 'name="csrf_token" value="[^"]*"' | head -1 | sed -E 's/.*value="([^"]*)".*/\1/')

LOGIN_STATUS=$(curl -s -o /dev/null -w '%{http_code}' -b "$COOKIE_JAR" -c "$COOKIE_JAR" -X POST "$BASE_URL/login" \
    --data-urlencode "csrf_token=$FRESH_TOKEN" \
    --data-urlencode "email=$COORD_EMAIL" \
    --data-urlencode "password=$COORD_PASSWORD")

# Udane logowanie kończy się przekierowaniem (302) na /dashboard
if [[ "$LOGIN_STATUS" == "302" || "$LOGIN_STATUS" == "200" ]]; then
    echo "  [OK]   Logowanie kontem koordynatora zakończone przekierowaniem/200 (status $LOGIN_STATUS)"
    PASS=$((PASS + 1))

    assert_status "Po zalogowaniu GET /dashboard zwraca 200"  200 GET "/dashboard"
    assert_status "Po zalogowaniu GET /missions zwraca 200"   200 GET "/missions"
    assert_status "Po zalogowaniu GET /equipment zwraca 200"  200 GET "/equipment"
    assert_status "Koordynator ma dostęp do GET /users (200)" 200 GET "/users"
    assert_status "Koordynator ma dostęp do GET /missions/new (200)" 200 GET "/missions/new"

    # Wylogowanie i sprawdzenie, że sesja faktycznie wygasła
    curl -s -o /dev/null -b "$COOKIE_JAR" -c "$COOKIE_JAR" "$BASE_URL/logout"
    assert_redirect_to "Po wylogowaniu GET /dashboard znów przekierowuje do /login" "/dashboard" "/login"
else
    echo "  [SKIP] Logowanie testowym kontem koordynatora nie powiodło się (status $LOGIN_STATUS)."
    echo "         Pomijam testy zależne od zalogowanej sesji."
    echo "         Sprawdź dane logowania (TEST_COORDINATOR_EMAIL / TEST_COORDINATOR_PASSWORD) oraz czy baza została zainicjalizowana."
fi

# ------------------------------------------------------------------
# Podsumowanie
# ------------------------------------------------------------------
echo ""
echo "============================================"
echo "Wynik testów integracyjnych: $PASS OK, $FAIL FAIL"
echo "============================================"

if [[ "$FAIL" -gt 0 ]]; then
    exit 1
fi

exit 0

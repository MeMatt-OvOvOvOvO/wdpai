<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Before;

/**
 * Testy jednostkowe SessionService.
 *
 * Skupiają się na logice, która nie zależy od bazy danych ani warstwy HTTP:
 * tokeny CSRF, flash messages, sprawdzanie ról oraz mechanizm A4
 * (limit prób logowania / blokada czasowa).
 */
final class SessionServiceTest extends TestCase
{
    #[Before]
    protected function resetSession(): void
    {
        // Każdy test startuje z czystą tablicą sesji, żeby testy się nie przenikały.
        $_SESSION = [];
    }

    // -------------------------------------------------------
    // CSRF
    // -------------------------------------------------------

    public function testGenerateCsrfTokenReturnsNonEmptyString(): void
    {
        $token = SessionService::generateCsrfToken();

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        // bin2hex(random_bytes(32)) => 64 znaki hex
        $this->assertSame(64, strlen($token));
    }

    public function testGenerateCsrfTokenIsStableAcrossCalls(): void
    {
        $first  = SessionService::generateCsrfToken();
        $second = SessionService::generateCsrfToken();

        $this->assertSame($first, $second, 'Token CSRF nie powinien się zmieniać przy kolejnych odczytach w tej samej sesji.');
    }

    public function testValidateCsrfTokenAcceptsMatchingToken(): void
    {
        $token = SessionService::generateCsrfToken();

        $this->assertTrue(SessionService::validateCsrfToken($token));
    }

    public function testValidateCsrfTokenRejectsWrongToken(): void
    {
        SessionService::generateCsrfToken();

        $this->assertFalse(SessionService::validateCsrfToken('to-na-pewno-jest-zly-token'));
    }

    public function testValidateCsrfTokenRejectsWhenNoTokenStored(): void
    {
        $this->assertFalse(SessionService::validateCsrfToken('cokolwiek'));
    }

    public function testRotateCsrfTokenChangesStoredToken(): void
    {
        $original = SessionService::generateCsrfToken();
        SessionService::rotateCsrfToken();
        $rotated = SessionService::get('csrf_token');

        $this->assertNotSame($original, $rotated);
        $this->assertFalse(SessionService::validateCsrfToken($original), 'Stary token nie powinien być już ważny po rotacji.');
        $this->assertTrue(SessionService::validateCsrfToken($rotated));
    }

    // -------------------------------------------------------
    // Flash messages
    // -------------------------------------------------------

    public function testFlashMessageIsAvailableOnceAndThenCleared(): void
    {
        SessionService::flash('success', 'Operacja zakończona sukcesem.');

        $this->assertSame('Operacja zakończona sukcesem.', SessionService::getFlash('success'));
        // Drugie odczytanie powinno zwrócić null - flash jest jednorazowy
        $this->assertNull(SessionService::getFlash('success'));
    }

    public function testGetFlashReturnsNullWhenNothingWasSet(): void
    {
        $this->assertNull(SessionService::getFlash('error'));
    }

    // -------------------------------------------------------
    // Role i stan zalogowania
    // -------------------------------------------------------

    public function testIsLoggedInIsFalseByDefault(): void
    {
        $this->assertFalse(SessionService::isLoggedIn());
    }

    public function testIsLoggedInIsTrueAfterSettingSessionData(): void
    {
        SessionService::set('user_id', 1);
        SessionService::set('is_logged_in', true);

        $this->assertTrue(SessionService::isLoggedIn());
    }

    public function testIsCoordinatorRequiresLoginAndCorrectRole(): void
    {
        SessionService::set('user_id', 1);
        SessionService::set('is_logged_in', true);
        SessionService::set('user_role', 'coordinator');

        $this->assertTrue(SessionService::isCoordinator());
        $this->assertFalse(SessionService::isRescuer());
    }

    public function testIsRescuerRequiresLoginAndCorrectRole(): void
    {
        SessionService::set('user_id', 2);
        SessionService::set('is_logged_in', true);
        SessionService::set('user_role', 'rescuer');

        $this->assertTrue(SessionService::isRescuer());
        $this->assertFalse(SessionService::isCoordinator());
    }

    public function testRoleChecksAreFalseWhenNotLoggedIn(): void
    {
        SessionService::set('user_role', 'coordinator');
        // brak user_id / is_logged_in => isLoggedIn() == false

        $this->assertFalse(SessionService::isCoordinator());
        $this->assertFalse(SessionService::isRescuer());
    }

    // -------------------------------------------------------
    // A4: limit prób logowania / blokada czasowa
    // -------------------------------------------------------

    public function testNoLockoutForFreshEmail(): void
    {
        $this->assertSame(0, SessionService::getLoginLockoutRemaining('swiezy@topr.pl'));
    }

    public function testAccountIsNotLockedBeforeReachingMaxAttempts(): void
    {
        $email = 'rescuer@topr.pl';

        for ($i = 0; $i < 4; $i++) {
            SessionService::registerFailedLogin($email);
        }

        $this->assertSame(0, SessionService::getLoginLockoutRemaining($email), 'Po 4 nieudanych próbach (próg=5) konto nie powinno być jeszcze zablokowane.');
    }

    public function testAccountIsLockedAfterReachingMaxAttempts(): void
    {
        $email = 'brute-force@topr.pl';

        for ($i = 0; $i < 5; $i++) {
            SessionService::registerFailedLogin($email);
        }

        $remaining = SessionService::getLoginLockoutRemaining($email);

        $this->assertGreaterThan(0, $remaining, 'Po 5 nieudanych próbach konto powinno zostać zablokowane.');
        $this->assertLessThanOrEqual(300, $remaining, 'Czas blokady nie powinien przekraczać skonfigurowanych 5 minut (300s).');
    }

    public function testLockoutIsCaseInsensitiveOnEmail(): void
    {
        $email = 'Coordinator@TOPR.pl';

        for ($i = 0; $i < 5; $i++) {
            SessionService::registerFailedLogin($email);
        }

        // Ten sam adres, inna wielkość liter — powinien trafić w ten sam licznik (sha1(strtolower(...)))
        $remaining = SessionService::getLoginLockoutRemaining('coordinator@topr.pl');

        $this->assertGreaterThan(0, $remaining);
    }

    public function testClearLoginAttemptsRemovesLockout(): void
    {
        $email = 'reset-me@topr.pl';

        for ($i = 0; $i < 5; $i++) {
            SessionService::registerFailedLogin($email);
        }
        $this->assertGreaterThan(0, SessionService::getLoginLockoutRemaining($email));

        SessionService::clearLoginAttempts($email);

        $this->assertSame(0, SessionService::getLoginLockoutRemaining($email), 'Po udanym logowaniu licznik prób powinien zostać wyczyszczony.');
    }

    public function testFailedAttemptsForDifferentEmailsAreIndependent(): void
    {
        for ($i = 0; $i < 5; $i++) {
            SessionService::registerFailedLogin('user-a@topr.pl');
        }

        // user-b nie powinien być zablokowany przez próby user-a
        $this->assertSame(0, SessionService::getLoginLockoutRemaining('user-b@topr.pl'));
    }
}

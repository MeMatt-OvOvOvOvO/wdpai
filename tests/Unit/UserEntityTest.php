<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Testy jednostkowe encji User — sprawdzają mapowanie danych z konstruktora
 * na gettery oraz logikę pomocniczą wykorzystywaną przez kontrolę dostępu
 * (isCoordinator/isRescuer), która decyduje co użytkownik widzi w interfejsie.
 */
final class UserEntityTest extends TestCase
{
    private function makeUser(string $roleName, bool $isActive = true): User
    {
        return new User(
            id: 1,
            username: 'jan.kowalski',
            email: 'jan.kowalski@topr.pl',
            password: '$2y$10$abcdefghijklmnopqrstuv', // przykładowy hash bcrypt - nie prawdziwe haslo
            roleId: 2,
            roleName: $roleName,
            isActive: $isActive,
            createdAt: '2026-01-01 12:00:00'
        );
    }

    public function testGettersReturnConstructorValues(): void
    {
        $user = $this->makeUser('coordinator');

        $this->assertSame(1, $user->getId());
        $this->assertSame('jan.kowalski', $user->getUsername());
        $this->assertSame('jan.kowalski@topr.pl', $user->getEmail());
        $this->assertSame(2, $user->getRoleId());
        $this->assertSame('coordinator', $user->getRoleName());
        $this->assertTrue($user->isActive());
        $this->assertSame('2026-01-01 12:00:00', $user->getCreatedAt());
    }

    public function testIsCoordinatorIsTrueOnlyForCoordinatorRole(): void
    {
        $coordinator = $this->makeUser('coordinator');
        $rescuer     = $this->makeUser('rescuer');

        $this->assertTrue($coordinator->isCoordinator());
        $this->assertFalse($coordinator->isRescuer());

        $this->assertTrue($rescuer->isRescuer());
        $this->assertFalse($rescuer->isCoordinator());
    }

    public function testIsActiveReflectsConstructorFlag(): void
    {
        $active   = $this->makeUser('rescuer', true);
        $inactive = $this->makeUser('rescuer', false);

        $this->assertTrue($active->isActive());
        $this->assertFalse($inactive->isActive());
    }

    public function testPasswordHashCanBeVerifiedWithPasswordVerify(): void
    {
        // Sprawdzamy spójność z mechanizmem logowania: hash wygenerowany przez
        // password_hash() musi dać się zweryfikować przez password_verify()
        // dokładnie tak, jak robi to SecurityController::login().
        $plainPassword = 'BardzoTajneHaslo123';
        $hash = password_hash($plainPassword, PASSWORD_BCRYPT);

        $user = new User(
            id: 5,
            username: 'test.user',
            email: 'test.user@topr.pl',
            password: $hash,
            roleId: 2,
            roleName: 'rescuer'
        );

        $this->assertTrue(password_verify($plainPassword, $user->getPassword()));
        $this->assertFalse(password_verify('zleHaslo', $user->getPassword()));
    }
}

<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Testy jednostkowe encji Mission — w szczególności logiki statusów,
 * która steruje wyglądem (kolor odznaki) oraz tym, czy akcja liczy się
 * jako "trwająca" w widokach takich jak active_missions_view.
 */
final class MissionEntityTest extends TestCase
{
    private function makeMission(string $status): Mission
    {
        return new Mission(
            id: 10,
            title: 'Poszukiwanie turysty - Dolina Pięciu Stawów',
            location: 'Dolina Pięciu Stawów Polskich',
            coordinates: '49.1963,20.0788',
            incidentTypeId: 1,
            incidentTypeName: 'Zaginięcie',
            status: $status,
            startTime: '2026-06-01 10:00:00'
        );
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function activeStatusProvider(): array
    {
        return [
            'open is active'      => ['open', true],
            'active is active'    => ['active', true],
            'completed not active'=> ['completed', false],
            'cancelled not active'=> ['cancelled', false],
        ];
    }

    /**
     * @dataProvider activeStatusProvider
     */
    public function testIsActiveReflectsStatus(string $status, bool $expectedActive): void
    {
        $mission = $this->makeMission($status);

        $this->assertSame($expectedActive, $mission->isActive());
    }

    public function testIsCompletedIsTrueOnlyForCompletedStatus(): void
    {
        $this->assertTrue($this->makeMission('completed')->isCompleted());
        $this->assertFalse($this->makeMission('active')->isCompleted());
        $this->assertFalse($this->makeMission('open')->isCompleted());
        $this->assertFalse($this->makeMission('cancelled')->isCompleted());
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function badgeClassProvider(): array
    {
        return [
            'active'    => ['active', 'badge--active'],
            'open'      => ['open', 'badge--open'],
            'completed' => ['completed', 'badge--completed'],
            'cancelled' => ['cancelled', 'badge--cancelled'],
            'unknown'   => ['some-unexpected-status', 'badge--default'],
        ];
    }

    /**
     * @dataProvider badgeClassProvider
     */
    public function testGetStatusBadgeClassMapsStatusToCssClass(string $status, string $expectedClass): void
    {
        $mission = $this->makeMission($status);

        $this->assertSame($expectedClass, $mission->getStatusBadgeClass());
    }

    public function testGettersReturnConstructorValues(): void
    {
        $mission = $this->makeMission('active');

        $this->assertSame(10, $mission->getId());
        $this->assertSame('Poszukiwanie turysty - Dolina Pięciu Stawów', $mission->getTitle());
        $this->assertSame('Dolina Pięciu Stawów Polskich', $mission->getLocation());
        $this->assertSame('49.1963,20.0788', $mission->getCoordinates());
        $this->assertSame(1, $mission->getIncidentTypeId());
        $this->assertSame('Zaginięcie', $mission->getIncidentTypeName());
        $this->assertSame('active', $mission->getStatus());
    }
}

<?php

class Equipment
{
    private int     $id;
    private string  $name;
    private string  $serialNumber;
    private ?int    $typeId;
    private ?string $typeName;
    private string  $status;
    private ?string $lastInspection;
    private ?int    $serviceLifePct;
    private ?string $notes;
    private string  $createdAt;

    public function __construct(
        int     $id,
        string  $name,
        string  $serialNumber,
        ?int    $typeId         = null,
        ?string $typeName       = null,
        string  $status         = 'ready',
        ?string $lastInspection = null,
        ?int    $serviceLifePct = null,
        ?string $notes          = null,
        string  $createdAt      = ''
    ) {
        $this->id             = $id;
        $this->name           = $name;
        $this->serialNumber   = $serialNumber;
        $this->typeId         = $typeId;
        $this->typeName       = $typeName;
        $this->status         = $status;
        $this->lastInspection = $lastInspection;
        $this->serviceLifePct = $serviceLifePct;
        $this->notes          = $notes;
        $this->createdAt      = $createdAt;
    }

    public function getId(): int              { return $this->id; }
    public function getName(): string         { return $this->name; }
    public function getSerialNumber(): string { return $this->serialNumber; }
    public function getTypeId(): ?int         { return $this->typeId; }
    public function getTypeName(): ?string    { return $this->typeName; }
    public function getStatus(): string       { return $this->status; }
    public function getLastInspection(): ?string { return $this->lastInspection; }
    public function getServiceLifePct(): ?int { return $this->serviceLifePct; }
    public function getNotes(): ?string       { return $this->notes; }
    public function getCreatedAt(): string    { return $this->createdAt; }

    public function isReady(): bool       { return $this->status === 'ready'; }
    public function isInUse(): bool       { return $this->status === 'in_use'; }
    public function isMaintenance(): bool { return $this->status === 'maintenance'; }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'ready'       => 'Gotowy',
            'in_use'      => 'W użyciu',
            'maintenance' => 'Serwis',
            'retired'     => 'Wycofany',
            'lost'        => 'Zaginiony',
            default       => $this->status,
        };
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'ready'       => 'badge--ready',
            'in_use'      => 'badge--in-use',
            'maintenance' => 'badge--maintenance',
            'retired'     => 'badge--retired',
            'lost'        => 'badge--lost',
            default       => 'badge--default',
        };
    }
}

<?php

class Mission
{
    private int     $id;
    private string  $title;
    private string  $location;
    private ?string $coordinates;
    private ?int    $incidentTypeId;
    private ?string $incidentTypeName;
    private string  $status;
    private string  $startTime;
    private ?string $endTime;
    private ?string $description;
    private ?int    $createdBy;
    private string  $createdAt;

    public function __construct(
        int     $id,
        string  $title,
        string  $location,
        ?string $coordinates      = null,
        ?int    $incidentTypeId   = null,
        ?string $incidentTypeName = null,
        string  $status           = 'open',
        string  $startTime        = '',
        ?string $endTime          = null,
        ?string $description      = null,
        ?int    $createdBy        = null,
        string  $createdAt        = ''
    ) {
        $this->id               = $id;
        $this->title            = $title;
        $this->location         = $location;
        $this->coordinates      = $coordinates;
        $this->incidentTypeId   = $incidentTypeId;
        $this->incidentTypeName = $incidentTypeName;
        $this->status           = $status;
        $this->startTime        = $startTime;
        $this->endTime          = $endTime;
        $this->description      = $description;
        $this->createdBy        = $createdBy;
        $this->createdAt        = $createdAt;
    }

    public function getId(): int              { return $this->id; }
    public function getTitle(): string        { return $this->title; }
    public function getLocation(): string     { return $this->location; }
    public function getCoordinates(): ?string { return $this->coordinates; }
    public function getIncidentTypeId(): ?int    { return $this->incidentTypeId; }
    public function getIncidentTypeName(): ?string { return $this->incidentTypeName; }
    public function getStatus(): string       { return $this->status; }
    public function getStartTime(): string    { return $this->startTime; }
    public function getEndTime(): ?string     { return $this->endTime; }
    public function getDescription(): ?string { return $this->description; }
    public function getCreatedBy(): ?int      { return $this->createdBy; }
    public function getCreatedAt(): string    { return $this->createdAt; }

    public function isActive(): bool    { return in_array($this->status, ['open', 'active']); }
    public function isCompleted(): bool { return $this->status === 'completed'; }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'active'    => 'Aktywna',
            'open'      => 'Otwarta',
            'completed' => 'Zakończona',
            'cancelled' => 'Anulowana',
            default     => $this->status,
        };
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'active'    => 'badge--active',
            'open'      => 'badge--open',
            'completed' => 'badge--completed',
            'cancelled' => 'badge--cancelled',
            default     => 'badge--default',
        };
    }
}

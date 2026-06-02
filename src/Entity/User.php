<?php

class User
{
    private int    $id;
    private string $username;
    private string $email;
    private string $password;
    private int    $roleId;
    private string $roleName;
    private bool   $isActive;
    private string $createdAt;

    public function __construct(
        int    $id,
        string $username,
        string $email,
        string $password,
        int    $roleId,
        string $roleName  = '',
        bool   $isActive  = true,
        string $createdAt = ''
    ) {
        $this->id        = $id;
        $this->username  = $username;
        $this->email     = $email;
        $this->password  = $password;
        $this->roleId    = $roleId;
        $this->roleName  = $roleName;
        $this->isActive  = $isActive;
        $this->createdAt = $createdAt;
    }

    public function getId(): int       { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function getEmail(): string    { return $this->email; }
    public function getPassword(): string { return $this->password; }
    public function getRoleId(): int      { return $this->roleId; }
    public function getRoleName(): string { return $this->roleName; }
    public function isActive(): bool      { return $this->isActive; }
    public function getCreatedAt(): string { return $this->createdAt; }

    public function isCoordinator(): bool { return $this->roleName === 'coordinator'; }
    public function isRescuer(): bool     { return $this->roleName === 'rescuer'; }
}

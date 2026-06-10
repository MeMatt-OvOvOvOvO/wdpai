<?php

class UserRepository
{
    // D1: UserRepository zarządzany jako singleton – jedna spójna instancja
    private static ?UserRepository $instance = null;

    private PDO $db;

    private function __construct()
    {
        $this->db = DatabaseService::getInstance()->connect();
    }

    public static function getInstance(): UserRepository
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Zapobiega klonowaniu singletona
    private function __clone() {}

    public function getUserByEmail(string $email): ?User
    {
        // C5: pobieramy tylko kolumny potrzebne do uwierzytelnienia, nie SELECT *
        $stmt = $this->db->prepare('
            SELECT u.id, u.username, u.email, u.password, u.role_id, u.is_active, u.created_at,
                   r.name AS role_name
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.email = :email AND u.is_active = TRUE
        ');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapToEntity($row) : null;
    }

    public function getUserByUsername(string $username): ?User
    {
        // C5: pobieramy tylko potrzebne kolumny zamiast SELECT *
        $stmt = $this->db->prepare('
            SELECT u.id, u.username, u.email, u.password, u.role_id, u.is_active, u.created_at,
                   r.name AS role_name
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.username = :username AND u.is_active = TRUE
        ');
        $stmt->execute([':username' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapToEntity($row) : null;
    }

    public function getUserById(int $id): ?User
    {
        // C5: pobieramy tylko potrzebne kolumny zamiast SELECT *
        // LEFT JOIN roles – chroni przed null gdy role_id nie ma odpowiednika
        $stmt = $this->db->prepare('
            SELECT u.id, u.username, u.email, u.password, u.role_id, u.is_active, u.created_at,
                   r.name AS role_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.id = :id
        ');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapToEntity($row) : null;
    }

    /** @return User[] */
    public function getAllUsers(): array
    {
        $stmt = $this->db->query('
            SELECT u.*, r.name AS role_name,
                   p.first_name, p.last_name, p.rank
            FROM users u
            JOIN roles r ON u.role_id = r.id
            LEFT JOIN profiles p ON u.id = p.user_id
            ORDER BY u.created_at DESC
        ');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'mapToEntity'], $rows);
    }

    public function createUser(string $email, string $hashedPassword, string $username, int $roleId = 2): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('
                INSERT INTO users (username, email, password, role_id)
                VALUES (:username, :email, :password, :role_id)
                RETURNING id
            ');
            $stmt->execute([
                ':username' => $username,
                ':email'    => $email,
                ':password' => $hashedPassword,
                ':role_id'  => $roleId,
            ]);
            $userId = (int)$stmt->fetchColumn();

            // Utwórz pusty profil
            $stmt2 = $this->db->prepare('
                INSERT INTO profiles (user_id, first_name, last_name)
                VALUES (:user_id, :first_name, :last_name)
            ');
            $stmt2->execute([
                ':user_id'    => $userId,
                ':first_name' => $username,
                ':last_name'  => '',
            ]);

            $this->db->commit();
            return $userId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateUser(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        foreach (['username', 'email', 'role_id', 'is_active'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($fields)) return false;

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function updateProfile(int $userId, array $data): bool
    {
        // UPSERT: jeśli profil nie istnieje (np. user dodany ręcznie przez SQL),
        // tworzy go automatycznie; w przeciwnym razie aktualizuje.
        $firstName = $data['first_name'] ?? '';
        $lastName  = $data['last_name']  ?? '';
        $rank      = $data['rank']       ?? null;
        $phone     = $data['phone']      ?? null;
        $bio       = $data['bio']        ?? null;

        $stmt = $this->db->prepare('
            INSERT INTO profiles (user_id, first_name, last_name, rank, phone, bio)
            VALUES (:user_id, :first_name, :last_name, :rank, :phone, :bio)
            ON CONFLICT (user_id) DO UPDATE SET
                first_name = EXCLUDED.first_name,
                last_name  = EXCLUDED.last_name,
                rank       = EXCLUDED.rank,
                phone      = EXCLUDED.phone,
                bio        = EXCLUDED.bio
        ');

        return $stmt->execute([
            ':user_id'    => $userId,
            ':first_name' => $firstName,
            ':last_name'  => $lastName,
            ':rank'       => $rank,
            ':phone'      => $phone,
            ':bio'        => $bio,
        ]);
    }

    public function deleteUser(int $id): bool
    {
        // Soft delete
        $stmt = $this->db->prepare('UPDATE users SET is_active = FALSE WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function getProfileByUserId(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM profiles WHERE user_id = :user_id');
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function getRoles(): array
    {
        $stmt = $this->db->query('SELECT * FROM roles ORDER BY id');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function mapToEntity(array $row): User
    {
        return new User(
            (int)$row['id'],
            $row['username'],
            $row['email'],
            $row['password'],
            (int)$row['role_id'],
            $row['role_name']  ?? '',
            (bool)$row['is_active'],
            $row['created_at'] ?? ''
        );
    }
}

<?php

class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->connect();
    }

    public function getUserByEmail(string $email): ?User
    {
        $stmt = $this->db->prepare('
            SELECT u.*, r.name AS role_name
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
        $stmt = $this->db->prepare('
            SELECT u.*, r.name AS role_name
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
        $stmt = $this->db->prepare('
            SELECT u.*, r.name AS role_name
            FROM users u
            JOIN roles r ON u.role_id = r.id
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
        $fields = [];
        $params = [':user_id' => $userId];

        foreach (['first_name', 'last_name', 'rank', 'phone', 'bio'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($fields)) return false;

        $sql = 'UPDATE profiles SET ' . implode(', ', $fields) . ' WHERE user_id = :user_id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
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

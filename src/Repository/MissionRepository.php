<?php

class MissionRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->connect();
    }

    /** @return Mission[] */
    public function getAllMissions(): array
    {
        $stmt = $this->db->query('
            SELECT m.*, it.name AS incident_type_name
            FROM missions m
            LEFT JOIN incident_types it ON m.incident_type_id = it.id
            ORDER BY m.start_time DESC
        ');
        return array_map([$this, 'mapToEntity'], $stmt->fetchAll());
    }

    /** @return Mission[] — tylko akcje, do których przypisany jest dany ratownik */
    public function getMissionsForRescuer(int $userId): array
    {
        $stmt = $this->db->prepare('
            SELECT m.*, it.name AS incident_type_name
            FROM missions m
            LEFT JOIN incident_types it ON m.incident_type_id = it.id
            INNER JOIN mission_rescuers mr ON mr.mission_id = m.id AND mr.user_id = :user_id
            ORDER BY m.start_time DESC
        ');
        $stmt->execute([':user_id' => $userId]);
        return array_map([$this, 'mapToEntity'], $stmt->fetchAll());
    }

    /** @return Mission[] */
    public function getActiveMissions(): array
    {
        $stmt = $this->db->query('
            SELECT mission_id AS id, title, location, coordinates,
                   incident_type, status, start_time,
                   rescuer_count, rescuer_names, duration_hours,
                   NULL AS incident_type_id,
                   NULL AS description, NULL AS created_by, NULL AS created_at,
                   incident_type AS incident_type_name
            FROM active_missions_view
            ORDER BY start_time DESC
        ');
        return array_map([$this, 'mapToEntity'], $stmt->fetchAll());
    }

    public function getMissionById(int $id): ?Mission
    {
        $stmt = $this->db->prepare('
            SELECT m.*, it.name AS incident_type_name
            FROM missions m
            LEFT JOIN incident_types it ON m.incident_type_id = it.id
            WHERE m.id = :id
        ');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->mapToEntity($row) : null;
    }

    public function createMission(array $data): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('
                INSERT INTO missions (title, location, coordinates, incident_type_id,
                                      status, start_time, description, created_by)
                VALUES (:title, :location, :coordinates, :incident_type_id,
                        :status, :start_time, :description, :created_by)
                RETURNING id
            ');
            $stmt->execute([
                ':title'            => $data['title'],
                ':location'         => $data['location'],
                ':coordinates'      => $data['coordinates']      ?? null,
                ':incident_type_id' => $data['incident_type_id'] ?? null,
                ':status'           => $data['status']           ?? 'open',
                ':start_time'       => $data['start_time']       ?? date('Y-m-d H:i:sP'),
                ':description'      => $data['description']      ?? null,
                ':created_by'       => $data['created_by']       ?? null,
            ]);
            $id = (int)$stmt->fetchColumn();
            $this->db->commit();
            return $id;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateMission(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        $allowed = ['title', 'location', 'coordinates', 'incident_type_id',
                    'status', 'start_time', 'end_time', 'description'];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($fields)) return false;

        $sql  = 'UPDATE missions SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function deleteMission(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM missions WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    // ---- Ratownicy w akcji ----

    public function getMissionRescuers(int $missionId): array
    {
        $stmt = $this->db->prepare('
            SELECT u.id AS user_id, u.username, u.email, p.first_name, p.last_name,
                   p.rank, mr.role AS mission_role, mr.assigned_at
            FROM mission_rescuers mr
            JOIN users    u ON mr.user_id  = u.id
            LEFT JOIN profiles p ON u.id = p.user_id
            WHERE mr.mission_id = :mission_id
            ORDER BY mr.assigned_at
        ');
        $stmt->execute([':mission_id' => $missionId]);
        return $stmt->fetchAll();
    }

    public function addRescuerToMission(int $missionId, int $userId, string $role = 'rescuer'): bool
    {
        $stmt = $this->db->prepare('
            INSERT INTO mission_rescuers (mission_id, user_id, role)
            VALUES (:mission_id, :user_id, :role)
            ON CONFLICT (mission_id, user_id) DO NOTHING
        ');
        return $stmt->execute([
            ':mission_id' => $missionId,
            ':user_id'    => $userId,
            ':role'       => $role,
        ]);
    }

    public function removeRescuerFromMission(int $missionId, int $userId): bool
    {
        $stmt = $this->db->prepare('
            DELETE FROM mission_rescuers
            WHERE mission_id = :mission_id AND user_id = :user_id
        ');
        return $stmt->execute([':mission_id' => $missionId, ':user_id' => $userId]);
    }

    // ---- Typy zdarzeń ----

    public function getIncidentTypes(): array
    {
        return $this->db->query('SELECT * FROM incident_types ORDER BY name')->fetchAll();
    }

    // ---- Statystyki (dla dashboardu) ----

    public function getStats(): array
    {
        $row = $this->db->query('
            SELECT
                COUNT(*) FILTER (WHERE status IN (\'open\',\'active\')) AS open_missions,
                COUNT(*) FILTER (WHERE status = \'completed\')          AS completed_missions,
                COUNT(*)                                                AS total_missions
            FROM missions
        ')->fetch();

        return $row ?: ['open_missions' => 0, 'completed_missions' => 0, 'total_missions' => 0];
    }

    public function getMissionDuration(int $missionId): ?float
    {
        $stmt = $this->db->prepare('SELECT calculate_mission_duration(:id)');
        $stmt->execute([':id' => $missionId]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (float)$val : null;
    }

    private function mapToEntity(array $row): Mission
    {
        return new Mission(
            (int)$row['id'],
            $row['title'],
            $row['location'],
            $row['coordinates']       ?? null,
            isset($row['incident_type_id']) ? (int)$row['incident_type_id'] : null,
            $row['incident_type_name'] ?? null,
            $row['status']            ?? 'open',
            $row['start_time']        ?? '',
            $row['end_time']          ?? null,
            $row['description']       ?? null,
            isset($row['created_by']) ? (int)$row['created_by'] : null,
            $row['created_at']        ?? ''
        );
    }
}

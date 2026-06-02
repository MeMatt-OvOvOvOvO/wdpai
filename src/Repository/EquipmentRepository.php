<?php

class EquipmentRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->connect();
    }

    /** @return Equipment[] */
    public function getAllEquipment(): array
    {
        $stmt = $this->db->query('
            SELECT e.*, et.name AS type_name
            FROM equipment e
            LEFT JOIN equipment_types et ON e.type_id = et.id
            ORDER BY e.name
        ');
        return array_map([$this, 'mapToEntity'], $stmt->fetchAll());
    }

    public function getEquipmentById(int $id): ?Equipment
    {
        $stmt = $this->db->prepare('
            SELECT e.*, et.name AS type_name
            FROM equipment e
            LEFT JOIN equipment_types et ON e.type_id = et.id
            WHERE e.id = :id
        ');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->mapToEntity($row) : null;
    }

    /** @return Equipment[] */
    public function getEquipmentByStatus(string $status): array
    {
        $stmt = $this->db->prepare('
            SELECT e.*, et.name AS type_name
            FROM equipment e
            LEFT JOIN equipment_types et ON e.type_id = et.id
            WHERE e.status = :status
            ORDER BY e.name
        ');
        $stmt->execute([':status' => $status]);
        return array_map([$this, 'mapToEntity'], $stmt->fetchAll());
    }

    public function createEquipment(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO equipment (name, serial_number, type_id, status,
                                   last_inspection, service_life_pct, notes)
            VALUES (:name, :serial_number, :type_id, :status,
                    :last_inspection, :service_life_pct, :notes)
            RETURNING id
        ');
        $stmt->execute([
            ':name'             => $data['name'],
            ':serial_number'    => $data['serial_number'],
            ':type_id'          => $data['type_id']          ?? null,
            ':status'           => $data['status']           ?? 'ready',
            ':last_inspection'  => $data['last_inspection']  ?? null,
            ':service_life_pct' => $data['service_life_pct'] ?? null,
            ':notes'            => $data['notes']            ?? null,
        ]);
        return (int)$stmt->fetchColumn();
    }

    public function updateEquipment(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        $allowed = ['name', 'serial_number', 'type_id', 'status',
                    'last_inspection', 'service_life_pct', 'notes'];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($fields)) return false;

        $sql  = 'UPDATE equipment SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function deleteEquipment(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM equipment WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    // ---- Wypożyczenia ----

    public function loanEquipmentToMission(int $missionId, int $equipmentId, int $quantity = 1): bool
    {
        $stmt = $this->db->prepare('
            INSERT INTO equipment_loans (mission_id, equipment_id, quantity)
            VALUES (:mission_id, :equipment_id, :quantity)
            ON CONFLICT (mission_id, equipment_id) DO NOTHING
        ');
        return $stmt->execute([
            ':mission_id'   => $missionId,
            ':equipment_id' => $equipmentId,
            ':quantity'     => $quantity,
        ]);
    }

    public function returnEquipment(int $missionId, int $equipmentId): bool
    {
        $stmt = $this->db->prepare('
            UPDATE equipment_loans
            SET returned_at = CURRENT_TIMESTAMP
            WHERE mission_id = :mission_id AND equipment_id = :equipment_id
        ');
        return $stmt->execute([
            ':mission_id'   => $missionId,
            ':equipment_id' => $equipmentId,
        ]);
    }

    public function getEquipmentForMission(int $missionId): array
    {
        $stmt = $this->db->prepare('
            SELECT e.id, e.name, e.serial_number, et.name AS type_name,
                   e.status, el.quantity, el.loaned_at, el.returned_at
            FROM equipment_loans el
            JOIN equipment      e  ON el.equipment_id = e.id
            LEFT JOIN equipment_types et ON e.type_id = et.id
            WHERE el.mission_id = :mission_id
        ');
        $stmt->execute([':mission_id' => $missionId]);
        return $stmt->fetchAll();
    }

    // ---- Typy sprzętu ----

    public function getEquipmentTypes(): array
    {
        return $this->db->query('SELECT * FROM equipment_types ORDER BY name')->fetchAll();
    }

    // ---- Raport i statystyki ----

    public function getUsageReport(): array
    {
        return $this->db->query('SELECT * FROM equipment_usage_report ORDER BY total_missions DESC')
                        ->fetchAll();
    }

    public function getStats(): array
    {
        $row = $this->db->query('
            SELECT
                COUNT(*)                                             AS total,
                COUNT(*) FILTER (WHERE status = \'ready\')          AS ready,
                COUNT(*) FILTER (WHERE status = \'in_use\')         AS in_use,
                COUNT(*) FILTER (WHERE status = \'maintenance\')    AS maintenance
            FROM equipment
        ')->fetch();

        return $row ?: ['total' => 0, 'ready' => 0, 'in_use' => 0, 'maintenance' => 0];
    }

    private function mapToEntity(array $row): Equipment
    {
        return new Equipment(
            (int)$row['id'],
            $row['name'],
            $row['serial_number'],
            isset($row['type_id']) ? (int)$row['type_id'] : null,
            $row['type_name']        ?? null,
            $row['status']           ?? 'ready',
            $row['last_inspection']  ?? null,
            isset($row['service_life_pct']) ? (int)$row['service_life_pct'] : null,
            $row['notes']            ?? null,
            $row['created_at']       ?? ''
        );
    }
}

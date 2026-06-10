-- Sprzęt w serwisie – seed
INSERT INTO equipment (name, serial_number, status, last_inspection, service_life_pct, notes, type_id)
VALUES
(
    'Defibrylator AED Zoll AED 3',
    'MED-ZOLL3-2021-14',
    'maintenance',
    '2026-04-10',
    52,
    'Wymiana elektrod i baterii. Przegląd kwartalny. Szacowany powrót: 16.06.2026.',
    (SELECT id FROM equipment_types WHERE name ILIKE 'Medyczny' LIMIT 1)
),
(
    'Radiotelefon Motorola DP4800e',
    'COM-DP4800-2022-03',
    'maintenance',
    '2026-05-28',
    71,
    'Uszkodzona antena i klips do paska. Oczekuje na części zamienne.',
    (SELECT id FROM equipment_types WHERE name ILIKE 'Łączność' LIMIT 1)
);

-- ============================================================
-- TOPR RESCUE - System Zarządzania Akcjami i Zasobami Ratowniczymi
-- Baza danych PostgreSQL
-- ============================================================

-- ============================================================
-- TABELE SŁOWNIKOWE (lookup tables)
-- ============================================================

CREATE TABLE roles (
    id   SERIAL PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL  -- 'rescuer', 'coordinator'
);

CREATE TABLE incident_types (
    id   SERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE equipment_types (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(100) UNIQUE NOT NULL,
    description TEXT
);

-- ============================================================
-- UŻYTKOWNICY I PROFILE (relacja 1:1)
-- ============================================================

CREATE TABLE users (
    id         SERIAL PRIMARY KEY,
    username   VARCHAR(50)  UNIQUE NOT NULL,
    email      VARCHAR(255) UNIQUE NOT NULL,
    password   TEXT         NOT NULL,
    role_id    INT          NOT NULL REFERENCES roles(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    created_at TIMESTAMPTZ  DEFAULT CURRENT_TIMESTAMP,
    is_active  BOOLEAN      DEFAULT TRUE
);

-- Profil 1:1 z users (user_id jest jednocześnie PK i FK)
CREATE TABLE profiles (
    user_id    INT          PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    first_name VARCHAR(100) NOT NULL,
    last_name  VARCHAR(100) NOT NULL,
    rank       VARCHAR(100),
    phone      VARCHAR(20),
    bio        TEXT,
    avatar_url TEXT
);

-- ============================================================
-- AKCJE RATUNKOWE (missions)
-- ============================================================

CREATE TABLE missions (
    id               SERIAL PRIMARY KEY,
    title            VARCHAR(200) NOT NULL,
    location         VARCHAR(255) NOT NULL,
    coordinates      VARCHAR(100),                    -- np. "49°11'20" N, 19°59'12" E"
    incident_type_id INT REFERENCES incident_types(id) ON DELETE SET NULL ON UPDATE CASCADE,
    status           VARCHAR(50) NOT NULL DEFAULT 'open'
                     CHECK (status IN ('open','active','completed','cancelled')),
    start_time       TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    end_time         TIMESTAMPTZ,
    description      TEXT,
    created_by       INT REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    created_at       TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- SPRZĘT
-- ============================================================

CREATE TABLE equipment (
    id                  SERIAL PRIMARY KEY,
    name                VARCHAR(200) NOT NULL,
    serial_number       VARCHAR(100) UNIQUE NOT NULL,
    type_id             INT REFERENCES equipment_types(id) ON DELETE SET NULL ON UPDATE CASCADE,
    status              VARCHAR(50)  NOT NULL DEFAULT 'ready'
                        CHECK (status IN ('ready','in_use','maintenance','retired','lost')),
    last_inspection     DATE,
    service_life_pct    INT CHECK (service_life_pct BETWEEN 0 AND 100),
    notes               TEXT,
    created_at          TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- RELACJE M:N
-- ============================================================

-- Ratownicy przypisani do akcji (M:N: missions <-> users)
CREATE TABLE mission_rescuers (
    mission_id  INT NOT NULL REFERENCES missions(id)  ON DELETE CASCADE ON UPDATE CASCADE,
    user_id     INT NOT NULL REFERENCES users(id)     ON DELETE CASCADE ON UPDATE CASCADE,
    role        VARCHAR(100) DEFAULT 'rescuer',        -- np. 'leader', 'medic', 'rescuer'
    assigned_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (mission_id, user_id)
);

-- Sprzęt użyty w akcji (M:N: missions <-> equipment)
CREATE TABLE equipment_loans (
    id           SERIAL PRIMARY KEY,
    mission_id   INT NOT NULL REFERENCES missions(id)   ON DELETE CASCADE ON UPDATE CASCADE,
    equipment_id INT NOT NULL REFERENCES equipment(id)  ON DELETE CASCADE ON UPDATE CASCADE,
    quantity     INT NOT NULL DEFAULT 1 CHECK (quantity > 0),
    loaned_at    TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    returned_at  TIMESTAMPTZ,
    UNIQUE (mission_id, equipment_id)
);

-- ============================================================
-- WIDOKI (min. 2 z JOIN na kilku tabelach)
-- ============================================================

-- Widok 1: Aktywne akcje z detalami ratowników i typem zdarzenia
CREATE VIEW active_missions_view AS
SELECT
    m.id                                        AS mission_id,
    m.title,
    m.location,
    m.coordinates,
    m.status,
    m.start_time,
    it.name                                     AS incident_type,
    COUNT(DISTINCT mr.user_id)                  AS rescuer_count,
    STRING_AGG(
        DISTINCT u.username, ', '
        ORDER BY u.username
    )                                           AS rescuer_names,
    EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - m.start_time))/3600 AS duration_hours
FROM missions m
LEFT JOIN incident_types  it ON m.incident_type_id = it.id
LEFT JOIN mission_rescuers mr ON m.id = mr.mission_id
LEFT JOIN users            u  ON mr.user_id = u.id
WHERE m.status IN ('open', 'active')
GROUP BY m.id, m.title, m.location, m.coordinates, m.status, m.start_time, it.name;

-- Widok 2: Raport użycia sprzętu (złączenie equipment + equipment_loans + missions)
CREATE VIEW equipment_usage_report AS
SELECT
    e.id                                  AS equipment_id,
    e.name                                AS equipment_name,
    e.serial_number,
    et.name                               AS equipment_type,
    e.status,
    COUNT(DISTINCT el.mission_id)         AS total_missions,
    SUM(el.quantity)                      AS total_quantity_loaned,
    MAX(el.loaned_at)                     AS last_loaned_at,
    STRING_AGG(
        DISTINCT m.title, '; '
        ORDER BY m.title
    )                                     AS missions_used_in
FROM equipment e
LEFT JOIN equipment_types et ON e.type_id = et.id
LEFT JOIN equipment_loans el ON e.id = el.equipment_id
LEFT JOIN missions         m  ON el.mission_id = m.id
GROUP BY e.id, e.name, e.serial_number, et.name, e.status;

-- ============================================================
-- FUNKCJA: oblicz czas trwania akcji w minutach
-- ============================================================

CREATE OR REPLACE FUNCTION calculate_mission_duration(p_mission_id INT)
RETURNS NUMERIC AS $$
DECLARE
    v_start TIMESTAMPTZ;
    v_end   TIMESTAMPTZ;
BEGIN
    SELECT start_time, end_time
    INTO v_start, v_end
    FROM missions
    WHERE id = p_mission_id;

    IF v_start IS NULL THEN
        RETURN NULL;
    END IF;

    IF v_end IS NULL THEN
        v_end := CURRENT_TIMESTAMP;
    END IF;

    RETURN ROUND(EXTRACT(EPOCH FROM (v_end - v_start)) / 60, 2);
END;
$$ LANGUAGE plpgsql;

-- ============================================================
-- TRIGGER: po przypisaniu ratownika do akcji → zmień status na 'active'
-- ============================================================

CREATE OR REPLACE FUNCTION trigger_activate_mission()
RETURNS TRIGGER AS $$
BEGIN
    -- Jeśli misja jest w statusie 'open', zmień na 'active'
    UPDATE missions
    SET status = 'active'
    WHERE id = NEW.mission_id AND status = 'open';

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_activate_mission_on_rescuer
AFTER INSERT ON mission_rescuers
FOR EACH ROW
EXECUTE FUNCTION trigger_activate_mission();

-- ============================================================
-- TRIGGER: zwróć sprzęt gdy akcja jest zakończona/anulowana
-- ============================================================

CREATE OR REPLACE FUNCTION trigger_return_equipment_on_mission_close()
RETURNS TRIGGER AS $$
BEGIN
    -- Gdy akcja zmienia status na completed lub cancelled:
    -- 1. Ustaw returned_at na wszystkich aktywnych wypożyczeniach tej akcji
    --    (trg_equipment_loan_status ustawi sprzęt na 'ready' przez łańcuch triggerów).
    -- 2. Nadpisz status na 'maintenance' – sprzęt wymaga przeglądu przed kolejnym użyciem.
    --    Pomijamy sprzęt retired/lost – ich statusu nie zmieniamy.
    IF NEW.status IN ('completed', 'cancelled')
       AND OLD.status NOT IN ('completed', 'cancelled') THEN

        UPDATE equipment_loans
        SET returned_at = COALESCE(NEW.end_time, CURRENT_TIMESTAMP)
        WHERE mission_id = NEW.id
          AND returned_at IS NULL;

        UPDATE equipment
        SET status = 'maintenance'
        WHERE id IN (
            SELECT equipment_id FROM equipment_loans WHERE mission_id = NEW.id
        )
        AND status NOT IN ('retired', 'lost');

    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_return_equipment_on_mission_close
AFTER UPDATE OF status ON missions
FOR EACH ROW
EXECUTE FUNCTION trigger_return_equipment_on_mission_close();

-- ============================================================
-- TRIGGER: zmień status sprzętu na 'in_use' po wypożyczeniu
-- ============================================================

CREATE OR REPLACE FUNCTION trigger_equipment_loan_status()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        -- Sprzęt wypożyczony → ustaw in_use
        UPDATE equipment SET status = 'in_use' WHERE id = NEW.equipment_id;

    ELSIF TG_OP = 'UPDATE' THEN
        -- Sprzęt zwrócony (returned_at ustawione przez aplikację)
        IF OLD.returned_at IS NULL AND NEW.returned_at IS NOT NULL THEN
            IF NOT EXISTS (
                SELECT 1 FROM equipment_loans
                WHERE equipment_id = NEW.equipment_id
                  AND returned_at IS NULL
                  AND id != NEW.id
            ) THEN
                UPDATE equipment SET status = 'ready' WHERE id = NEW.equipment_id;
            END IF;
        END IF;

    ELSIF TG_OP = 'DELETE' THEN
        -- Wiersz usunięty fizycznie → sprawdź czy sprzęt nadal wypożyczony gdzie indziej
        IF NOT EXISTS (
            SELECT 1 FROM equipment_loans
            WHERE equipment_id = OLD.equipment_id
              AND returned_at IS NULL
              AND id != OLD.id
        ) THEN
            UPDATE equipment SET status = 'ready' WHERE id = OLD.equipment_id;
        END IF;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_equipment_loan_status
AFTER INSERT OR UPDATE OR DELETE ON equipment_loans
FOR EACH ROW
EXECUTE FUNCTION trigger_equipment_loan_status();

-- ============================================================
-- PRZYKŁADOWE DANE (sample data)
-- ============================================================

-- Role
INSERT INTO roles (name) VALUES ('coordinator'), ('rescuer');

-- Typy zdarzeń
INSERT INTO incident_types (name) VALUES
    ('Wypadek turystyczny'),
    ('Zaginięcie'),
    ('Lawina'),
    ('Upadek ze ściany'),
    ('Ewakuacja medyczna'),
    ('Burza / warunki atmosferyczne'),
    ('Wypadek narciarki');

-- Typy sprzętu
INSERT INTO equipment_types (name, description) VALUES
    ('Liny i sprzęt wspinaczkowy', 'Liny statyczne, dynamiczne, przyrządy asekuracyjne'),
    ('Nosze i transport rannych',  'Nosze, tobogany, systemy transportu'),
    ('Medyczny',                   'Zestawy medyczne, defibrylatory, tlen'),
    ('Łączność',                   'Radiotelefony, telefony satelitarne'),
    ('Odzież i ochrona osobista',  'Kaski, raki, kombinezony');

-- Użytkownicy (hasła: "password" zaszyfrowane bcrypt)
INSERT INTO users (username, email, password, role_id) VALUES
    ('koordynator',  'koordynator@topr.pl',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
    ('ratownik1',    'ratownik1@topr.pl',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2),
    ('ratownik2',    'ratownik2@topr.pl',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2),
    ('ratownik3',    'ratownik3@topr.pl',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2);

-- Profile
INSERT INTO profiles (user_id, first_name, last_name, rank, phone) VALUES
    (1, 'Jan',    'Kowalski',   'Naczelnik',          '+48 600 100 200'),
    (2, 'Adam',   'Nowak',      'Starszy Ratownik',   '+48 600 100 201'),
    (3, 'Maria',  'Wiśniewska', 'Ratownik Medyczny',  '+48 600 100 202'),
    (4, 'Piotr',  'Zając',      'Ratownik',           '+48 600 100 203');

-- Sprzęt
INSERT INTO equipment (name, serial_number, type_id, status, last_inspection, service_life_pct) VALUES
    ('Lina dynamiczna 60m',        'SRT-22940-A', 1, 'ready',       '2024-10-12', 82),
    ('Lina statyczna 100m',        'SRT-22941-B', 1, 'ready',       '2024-09-20', 91),
    ('Nosze alpejskie Akja',        'STR-8910-X',  2, 'ready',       '2024-11-01', 95),
    ('Zestaw medyczny trauma V3',   'MED-7704-B',  3, 'ready',       '2024-08-15', 70),
    ('Radiotelefon VHF taktyczny',  'COM-0082-R',  4, 'maintenance', '2024-10-01', 60),
    ('Kask wspinaczkowy Sentinel',  'PPE-4001-Z',  5, 'ready',       '2024-11-15', 98),
    ('Defibrylator AED',            'MED-0021-D',  3, 'ready',       '2024-10-30', 100),
    ('Tobogan ratunkowy',           'STR-1102-Q',  2, 'ready',       '2024-09-10', 88),
    ('Zestaw Graminger',            'SRT-3301-G',  1, 'ready',       '2024-10-05', 76),
    ('Nosiłka francuska',           'STR-2201-F',  2, 'ready',       '2024-11-02', 90);

-- Akcje ratunkowe
INSERT INTO missions (title, location, coordinates, incident_type_id, status, start_time, end_time, description, created_by) VALUES
    ('Upadek ze ściany - Rysy',
     'Rysy, szlak północny',
     '49.1889,20.0778',
     4, 'completed',
     '2024-10-27 14:22:00+02', '2024-10-27 18:45:00+02',
     'Turysta upadł z wysokości ok. 15m. Złamanie złożone nogi. Ewakuacja śmigłowcem.',
     1),
    ('Zaginięcie w Dolinie Pięciu Stawów',
     'Dolina Pięciu Stawów Polskich',
     '49.1750,20.0333',
     2, 'active',
     NOW() - INTERVAL '2 hours', NULL,
     'Grupa 3 turystów odłączyła się od szlaku. Sygnał GPS słaby.',
     1),
    ('Lawina - Zawrat',
     'Przełęcz Zawrat',
     '49.2194,19.9778',
     3, 'open',
     NOW() - INTERVAL '30 minutes', NULL,
     'Wysokie ryzyko lawiny na stoku północnym. Wstrzymano operacje szkoleniowe.',
     1);

-- Przypisanie ratowników do akcji (trigger uruchomi zmianę statusu)
INSERT INTO mission_rescuers (mission_id, user_id, role) VALUES
    (1, 2, 'leader'),
    (1, 3, 'medic'),
    (2, 2, 'rescuer'),
    (2, 4, 'rescuer');

-- Wypożyczenie sprzętu dla zakończonej misji 1 (trigger zmieni status na 'in_use')
INSERT INTO equipment_loans (mission_id, equipment_id, quantity) VALUES
    (1, 1, 1),
    (1, 4, 1),
    (1, 6, 2);

-- Zwróć sprzęt z misji 1 – trigger trg_equipment_loan_status ustawi status 'ready'
UPDATE equipment_loans SET returned_at = '2024-10-27 18:45:00+02'
WHERE mission_id = 1;

-- ============================================================
-- TOPR Rescue – seed: 10 dodatkowych akcji ratunkowych
-- Uruchom: docker exec -i <container_db> psql -U docker -d db < docker/db/seed_missions.sql
-- lub przez pgAdmin (port 5050)
-- ============================================================

INSERT INTO missions (title, location, coordinates, incident_type_id, status, start_time, end_time, description, created_by)
VALUES

    -- 1. Zakończona – wypadek narciarski Kasprowy
    ('Wypadek na stoku – Kasprowy Wierch',
     'Kasprowy Wierch, trasa czerwona',
     '49.2319,19.9817',
     7, 'completed',
     NOW() - INTERVAL '5 days 9 hours',
     NOW() - INTERVAL '5 days 7 hours',
     'Narciarz zderzył się z ratrakiem na dolnym odcinku trasy czerwonej. Uraz głowy i złamanie obojczyka. Przetransportowany do szpitala w Zakopanem.',
     1),

    -- 2. Zakończona – ewakuacja medyczna Hala Gąsienicowa
    ('Ewakuacja medyczna – Hala Gąsienicowa',
     'Hala Gąsienicowa, schronisko',
     '49.2355,19.9933',
     5, 'completed',
     NOW() - INTERVAL '4 days 15 hours',
     NOW() - INTERVAL '4 days 13 hours 30 minutes',
     'Turysta z objawami zawału serca w schronisku. Stabilizacja na miejscu, ewakuacja śmigłowcem Lotniczego Pogotowia Ratunkowego.',
     1),

    -- 3. Zakończona – lawina Kościelec
    ('Zejście lawiny – Kościelec',
     'Kościelec, ściana wschodnia',
     '49.2280,19.9780',
     3, 'completed',
     NOW() - INTERVAL '3 days 6 hours',
     NOW() - INTERVAL '3 days 3 hours',
     'Dwóch wspinaczy zasypanych przez lawinę płytową. Odkopani po 40 minutach akcji, jeden z poszkodowanych z hipotermią II stopnia.',
     1),

    -- 4. Zakończona – zaginięcie Orla Perć
    ('Zaginięcie na Orlej Perci',
     'Orla Perć, odcinek Kozia Przełęcz–Granaty',
     '49.2210,20.0310',
     2, 'completed',
     NOW() - INTERVAL '2 days 18 hours',
     NOW() - INTERVAL '2 days 14 hours',
     'Samotna turystka nie wróciła do schroniska na noc. Odnaleziona na biwaku pod Granatami – zdrowa, bez ekwipunku biwakowego.',
     1),

    -- 5. Zakończona – upadek Dolina Jaworzynka
    ('Upadek ze szlaku – Dolina Jaworzynka',
     'Dolina Jaworzynka, szlak żółty',
     '49.2650,19.9580',
     1, 'completed',
     NOW() - INTERVAL '2 days 10 hours',
     NOW() - INTERVAL '2 days 8 hours 45 minutes',
     'Turysta poślizgnął się na oblodzonym szlaku, upadł ze skarpy ok. 4m. Złamanie kostki i stłuczenia. Transport noszami do drogi.',
     1),

    -- 6. Aktywna – burza Świnica
    ('Burza nad Świnicą – ewakuacja grupy',
     'Świnica, rejon szczytu',
     '49.2140,19.9710',
     6, 'active',
     NOW() - INTERVAL '3 hours',
     NULL,
     'Gwałtowna burza zaskoczyła grupę 8 turystów w rejonie szczytu Świnicy. Trwa ewakuacja na szlak zejściowy. Warunki: wichura 80 km/h, grad.',
     1),

    -- 7. Aktywna – zaginięcie dzieci Dolina Kościeliska
    ('Zaginięcie dzieci – Dolina Kościeliska',
     'Dolina Kościeliska, rejon Wąwozu Kraków',
     '49.2730,19.8970',
     2, 'active',
     NOW() - INTERVAL '1 hour 30 minutes',
     NULL,
     'Dwoje dzieci (10 i 12 lat) odłączyło się od wycieczki szkolnej. Ostatni kontakt przy tablicy informacyjnej Wąwozu Kraków. Trwa przeszukanie terenu.',
     1),

    -- 8. Otwarta – ryzyko lawiny Żleb Kulczyńskiego
    ('Ryzyko lawiny – Żleb Kulczyńskiego',
     'Żleb Kulczyńskiego, Tatry Zachodnie',
     '49.2050,19.8820',
     3, 'open',
     NOW() - INTERVAL '20 minutes',
     NULL,
     'Komunikat lawinowy stopień 4/5. Patrol prewencyjny wstrzymał ruch na szlaku. Oczekiwanie na ocenę ryzyka przez specjalistę lawinowego.',
     1),

    -- 9. Otwarta – wypadek wspinaczkowy Mięguszowieckie Szczyty
    ('Wypadek wspinaczkowy – Mięguszowieckie Szczyty',
     'Mięguszowiecki Szczyt Wielki, filar północny',
     '49.1880,20.0650',
     4, 'open',
     NOW() - INTERVAL '45 minutes',
     NULL,
     'Dwuosobowa lina wspinaczkowa zgłosiła uraz – jeden ze wspinaczy nie może samodzielnie zejść po kontuzji kolana na wysokości ok. 2300 m n.p.m.',
     1),

    -- 10. Otwarta – wypadek turystyczny Wielka Racza
    ('Hipotermia – Dolina Chochołowska',
     'Dolina Chochołowska, szlak niebieski',
     '49.3010,19.8280',
     5, 'open',
     NOW() - INTERVAL '10 minutes',
     NULL,
     'Starszy turysta (ok. 70 lat) z objawami hipotermii I/II stopnia. Znaleziony przez innych turystów 3 km od schroniska. Patrol TOPR w drodze.',
     1);

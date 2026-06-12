<?php
$pageTitle  = htmlspecialchars($mission->getTitle());
$activePage = 'missions';
$needsMap   = true;
ob_start();
?>

<main class="page-content">
  <div class="page-header">
    <div>
      <div class="page-subtitle">Akcje Ratunkowe</div>
      <h1 class="page-title"><?= htmlspecialchars($mission->getTitle()) ?></h1>
    </div>
    <div class="page-header__actions" style="display:flex;gap:0.75rem">
      <a href="/missions" class="btn btn--ghost">
        <span class="material-symbols-outlined">arrow_back</span>
        <span class="btn-text">Powrót</span>
      </a>
      <?php if (SessionService::isCoordinator()): ?>
      <a href="/missions/<?= $mission->getId() ?>/edit" class="btn btn--secondary">
        <span class="material-symbols-outlined">edit</span>
        <span class="btn-text">Edytuj</span>
      </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="mission-detail-grid" style="display:grid;grid-template-columns:2fr 1fr;gap:2rem">

    <!-- ===== LEWA KOLUMNA: Szczegóły ===== -->
    <div>
      <div class="card">
        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.5rem">
          <span class="material-symbols-outlined" style="color:var(--color-primary)">emergency_share</span>
          <h2 style="font-family:var(--font-headline);font-size:1rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em">
            Szczegóły Zdarzenia
          </h2>
        </div>

        <div class="name-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
          <div>
            <div class="form-label">Typ zdarzenia</div>
            <div style="font-weight:500"><?= htmlspecialchars($mission->getIncidentTypeName() ?? '—') ?></div>
          </div>
          <div>
            <div class="form-label">Status</div>
            <span class="badge <?= htmlspecialchars($mission->getStatusBadgeClass()) ?>"><?= htmlspecialchars($mission->getStatusLabel()) ?></span>
          </div>
          <div>
            <div class="form-label">Lokalizacja</div>
            <div style="font-weight:500;display:flex;align-items:center;gap:0.4rem">
              <span class="material-symbols-outlined" style="font-size:0.875rem;color:var(--color-text-dim)">location_on</span>
              <?= htmlspecialchars($mission->getLocation()) ?>
            </div>
          </div>
          <?php if ($mission->getCoordinates()): ?>
          <div>
            <div class="form-label">Koordynaty GPS</div>
            <div style="font-family:monospace;font-size:0.8rem;color:var(--color-text-muted)"><?= htmlspecialchars($mission->getCoordinates()) ?></div>
          </div>
          <?php endif; ?>
          <div>
            <div class="form-label">Czas rozpoczęcia</div>
            <div style="font-weight:500">
              <?= $mission->getStartTime() ? date('d.m.Y H:i', strtotime($mission->getStartTime())) : '—' ?>
            </div>
          </div>
          <div>
            <div class="form-label">Czas trwania</div>
            <div style="font-weight:500">
              <?php if ($duration !== null): ?>
                <?= round($duration) ?> min
              <?php else: ?>
                <span class="badge badge--active">W trakcie</span>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <?php if ($mission->getDescription()): ?>
        <div style="margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid var(--color-border-light)">
          <div class="form-label">Opis zdarzenia</div>
          <p style="font-size:0.875rem;line-height:1.7;color:var(--color-text-muted);white-space:pre-wrap"><?= htmlspecialchars($mission->getDescription()) ?></p>
        </div>
        <?php endif; ?>
      </div>

      <?php if ($mission->getCoordinates()): ?>
      <!-- Mapa lokalizacji -->
      <div class="card" style="margin-top:1.5rem">
        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1rem">
          <span class="material-symbols-outlined" style="color:var(--color-primary)">map</span>
          <h2 style="font-family:var(--font-headline);font-size:1rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em">
            Lokalizacja akcji
          </h2>
        </div>
        <div id="missionMap" style="height:280px;border-radius:var(--radius-sm);overflow:hidden;background:var(--color-surface-mid)"></div>
      </div>
      <?php endif; ?>
    </div>

    <!-- ===== PRAWA KOLUMNA ===== -->
    <div style="display:flex;flex-direction:column;gap:1.5rem">

      <!-- Deployment Team -->
      <div class="card">
        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.25rem">
          <span class="material-symbols-outlined" style="color:var(--color-primary)">groups</span>
          <h3 style="font-family:var(--font-headline);font-size:0.875rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em">
            Zespół Ratowniczy
          </h3>
        </div>

        <?php if (empty($rescuers)): ?>
        <p style="font-size:0.8rem;color:var(--color-text-dim);text-align:center;padding:1rem 0">
          Brak przypisanych ratowników
        </p>
        <?php else: ?>
        <ul style="display:flex;flex-direction:column;gap:0.5rem;margin-bottom:1rem">
          <?php foreach ($rescuers as $r): ?>
          <li style="display:flex;align-items:center;justify-content:space-between;padding:0.5rem 0;border-bottom:1px solid var(--color-border-light)">
            <div>
              <div style="font-weight:600;font-size:0.8rem"><?= htmlspecialchars($r['username']) ?></div>
              <div style="font-size:0.7rem;color:var(--color-text-dim)">
                <?= htmlspecialchars($r['rank'] ?? '') ?>
                <?php if (!empty($r['mission_role'])): ?>
                  · <span style="color:var(--color-primary)"><?= htmlspecialchars($r['mission_role']) ?></span>
                <?php endif; ?>
              </div>
            </div>
            <?php if (SessionService::isCoordinator() && !in_array($mission->getStatus(), ['completed', 'cancelled'])): ?>
            <form method="POST" action="/missions/<?= $mission->getId() ?>/rescuers/remove" style="margin:0">
              <input type="hidden" name="user_id" value="<?= htmlspecialchars($r['user_id']) ?>">
              <button type="submit" class="btn btn--danger btn--sm" title="Usuń z akcji"
                      onclick="return confirm('Usunąć ratownika z akcji?')">
                <span class="material-symbols-outlined" style="font-size:0.875rem">person_remove</span>
              </button>
            </form>
            <?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <?php if (SessionService::isCoordinator() && !in_array($mission->getStatus(), ['completed', 'cancelled'])): ?>
        <form method="POST" action="/missions/<?= $mission->getId() ?>/rescuers" style="display:flex;flex-direction:column;gap:0.75rem">
          <div class="form-group" style="margin-bottom:0">
            <label class="form-label" for="rescuer_user_id">Dodaj ratownika</label>
            <select class="form-select" id="rescuer_user_id" name="user_id">
              <option value="">— Wybierz ratownika —</option>
              <?php foreach ($allUsers as $u): ?>
              <option value="<?= $u->getId() ?>"><?= htmlspecialchars($u->getUsername()) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="margin-bottom:0">
            <label class="form-label" for="mission_role">Rola</label>
            <select class="form-select" id="mission_role" name="mission_role">
              <option value="rescuer">Ratownik</option>
              <option value="medic">Medyk</option>
              <option value="leader">Dowódca</option>
            </select>
          </div>
          <button type="submit" class="btn btn--primary btn--sm">
            <span class="material-symbols-outlined">person_add</span>
            Dodaj do akcji
          </button>
        </form>
        <?php endif; ?>
      </div>

      <!-- Equipment Log -->
      <div class="card">
        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.25rem">
          <span class="material-symbols-outlined" style="color:var(--color-primary)">inventory</span>
          <h3 style="font-family:var(--font-headline);font-size:0.875rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em">
            Dziennik Sprzętu
          </h3>
        </div>

        <?php if (empty($equipment)): ?>
        <p style="font-size:0.8rem;color:var(--color-text-dim);text-align:center;padding:1rem 0">
          Brak przypisanego sprzętu
        </p>
        <?php else: ?>
        <ul style="display:flex;flex-direction:column;gap:0.5rem;margin-bottom:1rem">
          <?php foreach ($equipment as $eq): ?>
          <li style="display:flex;align-items:center;justify-content:space-between;padding:0.5rem 0;border-bottom:1px solid var(--color-border-light)">
            <div>
              <div style="font-weight:600;font-size:0.8rem"><?= htmlspecialchars($eq['name']) ?></div>
              <div style="font-size:0.7rem;color:var(--color-text-dim)">
                <?= htmlspecialchars($eq['serial_number'] ?? '') ?>
              </div>
            </div>
            <div style="font-family:var(--font-headline);font-size:0.75rem;font-weight:700;color:var(--color-text-muted)">
              x<?= htmlspecialchars($eq['quantity'] ?? 1) ?>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <!-- Dodaj sprzęt via Fetch API – tylko dla aktywnych/otwartych akcji -->
        <?php if (!in_array($mission->getStatus(), ['completed', 'cancelled'])): ?>
        <div id="addEquipResult" class="alert" style="display:none"></div>
        <form id="addEquipForm" data-mission-id="<?= $mission->getId() ?>"
              style="display:flex;flex-direction:column;gap:0.75rem">
          <div class="form-group" style="margin-bottom:0">
            <label class="form-label" for="equipment_id">Sprzęt</label>
            <select class="form-select" id="equipment_id" name="equipment_id">
              <option value="">— Wybierz sprzęt —</option>
              <?php foreach ($allEquip as $e): ?>
              <option value="<?= $e->getId() ?>"><?= htmlspecialchars($e->getName()) ?> (<?= htmlspecialchars($e->getSerialNumber()) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="margin-bottom:0">
            <label class="form-label" for="equip_quantity">Ilość</label>
            <input class="form-input" type="number" id="equip_quantity" name="quantity" min="1" value="1">
          </div>
          <button type="submit" class="btn btn--secondary btn--sm">
            <span class="material-symbols-outlined">add</span>
            Dodaj
          </button>
        </form>
        <?php else: ?>
        <p style="font-size:0.75rem;color:var(--color-text-dim);text-align:center;padding:0.5rem 0">
          Akcja zakończona — sprzęt jest tylko do odczytu.
        </p>
        <?php endif; ?>
      </div>

    </div>
  </div>
</main>

<?php if ($mission->getCoordinates()): ?>
<script>
(function () {
  var raw = <?= json_encode($mission->getCoordinates()) ?>;

  // Parser koordynatów – obsługuje dwa formaty:
  // 1. Dziesiętny:  "49.2730,19.8970"
  // 2. DMS:         "49°11'20\" N, 20°04'40\" E"
  function parseCoords(str) {
    if (!str) return null;

    // Format dziesiętny: liczba,liczba
    var dec = str.match(/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/);
    if (dec) return [parseFloat(dec[1]), parseFloat(dec[2])];

    // Format DMS
    str = str.replace(/['']/g, "'").replace(/[""]/g, '"');
    var re = /(\d+)[°](\d+)['](\d+(?:\.\d+)?)["]?\s*([NnSs])[,\s]+(\d+)[°](\d+)['](\d+(?:\.\d+)?)["]?\s*([EeWw])/;
    var m = str.match(re);
    if (!m) return null;
    var lat = +m[1] + +m[2]/60 + +m[3]/3600;
    if (m[4].toUpperCase() === 'S') lat = -lat;
    var lon = +m[5] + +m[6]/60 + +m[7]/3600;
    if (m[8].toUpperCase() === 'W') lon = -lon;
    return [lat, lon];
  }

  var coords = parseCoords(raw);
  if (!coords) return;

  var map = L.map('missionMap', { zoomControl: true, scrollWheelZoom: false }).setView(coords, 13);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 18
  }).addTo(map);

  // Czerwony marker taktyczny
  var icon = L.divIcon({
    className: '',
    html: '<div style="width:20px;height:20px;background:var(--color-primary,#e53e3e);border:3px solid #fff;border-radius:50%;box-shadow:0 0 0 3px rgba(229,62,62,0.35),0 2px 8px rgba(0,0,0,0.5)"></div>',
    iconSize: [20, 20],
    iconAnchor: [10, 10]
  });

  var marker = L.marker(coords, { icon: icon }).addTo(map);
  marker.bindPopup(
    '<strong><?= addslashes(htmlspecialchars($mission->getLocation())) ?></strong><br>'
    + '<span style="font-size:0.75rem;color:#888">' + raw + '</span>'
  ).openPopup();
})();
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>

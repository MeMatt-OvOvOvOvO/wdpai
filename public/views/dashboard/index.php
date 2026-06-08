<?php
$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
ob_start();
?>

<main class="page-content">
  <!-- Stat Cards -->
  <div class="grid-3">
    <div class="stat-card stat-card--critical">
      <div class="stat-card__label">
        Open Incidents
        <span class="material-symbols-outlined icon-filled" style="color:var(--color-primary)">emergency_share</span>
      </div>
      <div>
        <div class="stat-card__value"><?= str_pad((int)($missionStats['open_missions'] ?? 0), 2, '0', STR_PAD_LEFT) ?></div>
        <div class="stat-card__sub stat-card__sub--red animate-pulse">Critical Priority</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-card__label">
        Active Rescuers
        <span class="material-symbols-outlined">groups</span>
      </div>
      <div>
        <div class="stat-card__value"><?= str_pad($activeRescuersCount ?? 0, 2, '0', STR_PAD_LEFT) ?></div>
        <div class="stat-card__sub stat-card__sub--green">On-Site</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-card__label">
        Available Gear
        <span class="material-symbols-outlined">inventory</span>
      </div>
      <div>
        <div class="stat-card__value"><?= $equipmentStats['ready'] ?? 0 ?></div>
        <div class="stat-card__sub stat-card__sub--muted">Units Ready</div>
      </div>
    </div>
  </div>

  <!-- Ops Grid: Map + Briefing -->
  <div class="ops-grid">
    <!-- Left: Map + Commands -->
    <div>
      <div class="section-header">
        <div class="section-title">
          <div class="section-title__dot"></div>
          Live Operations Map
        </div>
        <span class="text-xxs text-dim font-mono">49.2319° N, 19.9817° E</span>
      </div>

      <div class="ops-map">
        <!-- Mapa operacyjna (Leaflet + OpenStreetMap). Inicjalizowana w app.js (initOpsMap),
             markery akcji ratunkowych są pobierane asynchronicznie przez Fetch API z /api/missions. -->
        <div id="opsMap" class="ops-map__leaflet" role="img" aria-label="Mapa operacyjna sektora Tatr z lokalizacjami aktywnych akcji ratunkowych"></div>
        <div class="ops-map__overlay"></div>
        <div class="ops-map__hud">
          <div>
            <div class="hud-panel">
              <div class="hud-panel__label">Sector: Kasprowy</div>
              <div class="hud-panel__value">WIND: 45KM/H NW | TEMP: -12°C</div>
            </div>
          </div>
          <div class="flex items-center justify-between">
            <div class="sar-indicator">
              <div class="sar-indicator__dot"></div>
              <div class="sar-indicator__label">Active SAR: North Slope</div>
            </div>
            <a href="/missions" class="btn btn--ghost btn--sm">
              <span class="material-symbols-outlined">open_in_full</span>
            </a>
          </div>
        </div>
      </div>

      <!-- Tactical Commands -->
      <div class="mt-6">
        <div class="section-header">
          <div class="section-title">Tactical Commands</div>
        </div>
        <div class="command-grid">
          <a href="/missions/new" class="command-btn command-btn--primary">
            <span class="material-symbols-outlined">add_alert</span>
            <span class="command-btn__label">New Incident<br>Dispatch</span>
          </a>
          <a href="/equipment" class="command-btn">
            <span class="material-symbols-outlined">inventory</span>
            <span class="command-btn__label">Equip.<br>Check</span>
          </a>
          <a href="/missions" class="command-btn">
            <span class="material-symbols-outlined">description</span>
            <span class="command-btn__label">Mission<br>Log</span>
          </a>
          <?php if (SessionService::isCoordinator()): ?>
          <a href="/users" class="command-btn">
            <span class="material-symbols-outlined">group_add</span>
            <span class="command-btn__label">Mobilize<br>Rescuers</span>
          </a>
          <?php endif; ?>
          <a href="/profile" class="command-btn">
            <span class="material-symbols-outlined">account_circle</span>
            <span class="command-btn__label">My<br>Profile</span>
          </a>
          <button class="command-btn" onclick="alert('Broadcast wysłany do wszystkich jednostek.')">
            <span class="material-symbols-outlined">radio</span>
            <span class="command-btn__label">Broadcast<br>Alert</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Right: Tactical Briefing -->
    <div>
      <div class="section-header">
        <div class="section-title">Tactical Briefing</div>
      </div>
      <div class="briefing-list" style="max-height:600px;overflow-y:auto">
        <?php if (empty($activeMissions)): ?>
        <div class="briefing-item" style="text-align:center;padding:2rem">
          <span class="material-symbols-outlined" style="font-size:2rem;opacity:0.3">check_circle</span>
          <p class="text-dim text-sm mt-2">Brak aktywnych akcji</p>
        </div>
        <?php else: ?>
        <?php foreach ($activeMissions as $m): ?>
        <div class="briefing-item <?= $m->getStatus() === 'active' ? 'briefing-item--active' : '' ?>">
          <div class="briefing-item__meta">
            <span class="briefing-item__type badge <?= $m->getStatusBadgeClass() ?>">
              <?= htmlspecialchars($m->getIncidentTypeName() ?? $m->getStatus()) ?>
            </span>
            <span class="briefing-item__time">
              <?= date('H:i', strtotime($m->getStartTime())) ?>
            </span>
          </div>
          <div class="briefing-item__title"><?= htmlspecialchars($m->getTitle()) ?></div>
          <div class="briefing-item__desc"><?= htmlspecialchars($m->getLocation()) ?></div>
          <div class="briefing-item__actions">
            <a href="/missions/<?= $m->getId() ?>" class="btn btn--primary btn--sm">Szczegóły</a>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="mt-4">
        <a href="/missions" class="btn btn--ghost w-full" style="justify-content:center">
          <span class="material-symbols-outlined">list</span>
          Wszystkie akcje
        </a>
      </div>
    </div>
  </div>
</main>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>

<?php
// Elementy dostępne w layoucie:
// $pageTitle   - tytuł strony
// $activePage  - aktywna pozycja menu (dashboard/missions/equipment/users)

$username  = SessionService::get('user_username') ?? 'Użytkownik';
$userRole  = SessionService::get('user_role')     ?? 'rescuer';
$roleLabel = $userRole === 'coordinator' ? 'Koordynator' : 'Ratownik';
?>
<!DOCTYPE html>
<html lang="pl" class="dark">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($pageTitle ?? 'TOPR Rescue') ?> – TOPR Rescue</title>
  <link rel="stylesheet" href="/public/css/app.css?v=<?= filemtime(__DIR__ . '/../../css/app.css') ?>"/>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"/>
  <?php if (($activePage ?? '') === 'dashboard' || ($needsMap ?? false)): ?>
  <!-- Leaflet.js – darmowa biblioteka mapowa (BSD-2-Clause), kafelki z OpenStreetMap (bez klucza API) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css"/>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
  <?php endif; ?>
</head>
<body>

<div class="layout">
  <!-- Sidebar Overlay (mobile) -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- ========== SIDEBAR ========== -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar__brand">
      <div class="sidebar__brand-title">Centrum Dowodzenia</div>
      <div class="sidebar__brand-sub">Sektor 7 – Tatry</div>
    </div>

    <nav class="sidebar__nav">
      <a href="/dashboard" class="nav-item <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
        <span class="material-symbols-outlined <?= ($activePage ?? '') === 'dashboard' ? 'icon-filled' : '' ?>">grid_view</span>
        Dashboard
      </a>
      <a href="/missions" class="nav-item <?= ($activePage ?? '') === 'missions' ? 'active' : '' ?>">
        <span class="material-symbols-outlined">description</span>
        Raporty / Akcje
      </a>
      <a href="/equipment" class="nav-item <?= ($activePage ?? '') === 'equipment' ? 'active' : '' ?>">
        <span class="material-symbols-outlined">handheld_controller</span>
        Sprzęt
      </a>
      <?php if ($userRole === 'coordinator'): ?>
      <a href="/users" class="nav-item <?= ($activePage ?? '') === 'users' ? 'active' : '' ?>">
        <span class="material-symbols-outlined">manage_accounts</span>
        Użytkownicy
      </a>
      <?php endif; ?>
    </nav>

    <div class="sidebar__footer">
      <button class="sos-btn" onclick="alert('SOS wysłany! Powiadomiono centrum dowodzenia.')">
        <span class="material-symbols-outlined icon-filled">emergency</span>
        Sygnał SOS
      </button>
      <a href="/profile" class="nav-item <?= ($activePage ?? '') === 'profile' ? 'active' : '' ?>">
        <span class="material-symbols-outlined">help</span>
        Profil
      </a>
      <a href="/logout" class="nav-item">
        <span class="material-symbols-outlined">logout</span>
        Wyloguj
      </a>
    </div>
  </aside>

  <!-- ========== MAIN ========== -->
  <div class="main-wrapper">
    <!-- Topbar -->
    <header class="topbar">
      <div class="flex items-center gap-4">
        <button class="hamburger-btn" id="hamburgerBtn">
          <span class="material-symbols-outlined">menu</span>
        </button>
        <div class="topbar__logo">TOPR Rescue</div>
        <div class="topbar__search">
          <span class="material-symbols-outlined topbar__search-icon">search</span>
          <input type="text" placeholder="Szukaj akcji lub ratownika..." id="globalSearch"/>
        </div>
      </div>
      <div class="topbar__actions">
        <button class="icon-btn" id="notifBtn" title="Powiadomienia">
          <span class="material-symbols-outlined">notifications_active</span>
          <span class="notification-dot" id="notifDot"></span>
        </button>
        <a href="/profile" class="topbar__user">
          <div class="topbar__user-info">
            <div class="topbar__user-name"><?= htmlspecialchars(strtoupper($username)) ?></div>
            <div class="topbar__user-role"><?= htmlspecialchars($roleLabel) ?></div>
          </div>
          <span class="material-symbols-outlined text-dim icon-filled">account_circle</span>
        </a>
      </div>
    </header>

    <!-- Page Content (injected by each view) -->
    <?= $content ?? '' ?>

    <!-- Footer -->
    <footer style="padding: 1rem 2rem; border-top: 1px solid var(--color-border-light); display:flex; justify-content:space-between; align-items:center;">
      <div class="flex items-center gap-4 text-xxs text-dim uppercase tracking-wide">
        <span class="flex items-center gap-1">
          <span class="material-symbols-outlined" style="font-size:0.8rem">security</span>
          Zaszyfrowane Łącze Taktyczne v4.2.0
        </span>
        <span>Serwer: TATRA-MAIN-01</span>
      </div>
      <div class="text-xxs text-dim uppercase tracking-wide">TOPR Command © 2026</div>
    </footer>
  </div>
</div>

<!-- Ongoing Mission Indicator -->
<div class="mission-indicator" id="missionIndicator" style="display:none">
  <div class="mission-indicator__dot"></div>
  <div class="mission-indicator__text" id="missionIndicatorText">Trwająca akcja</div>
</div>

<script src="/public/js/app.js"></script>

<!-- Notification dropdown – dołączony do body (ucieka ze stacking contextu topbara) -->
<div id="notifPanel" style="
  display:none;
  position:fixed;
  width:300px;
  background:var(--color-surface-low);
  border:1px solid var(--color-border);
  border-radius:var(--radius-md);
  box-shadow:0 8px 32px rgba(0,0,0,0.6);
  z-index:9999;
  overflow:hidden;
  font-family:var(--font-body);
">
  <div style="padding:0.65rem 1rem;font-family:var(--font-headline);font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:var(--color-text-dim);border-bottom:1px solid var(--color-border-light)">
    Aktywne akcje
  </div>
  <div id="notifList" style="max-height:320px;overflow-y:auto"></div>
</div>

<script>
(function () {
  var btn   = document.getElementById('notifBtn');
  var panel = document.getElementById('notifPanel');
  var list  = document.getElementById('notifList');
  var dot   = document.getElementById('notifDot');
  if (!btn || !panel) return;

  var loaded = false;
  var open   = false;

  var STATUS_ICON  = { active:'emergency', open:'pending', completed:'check_circle', cancelled:'cancel' };
  var STATUS_COLOR = { active:'var(--color-primary)', open:'var(--color-warning)', completed:'var(--color-success)', cancelled:'var(--color-text-dim)' };

  function loadNotifs() {
    list.innerHTML = '<div style="padding:1rem;font-size:0.8rem;color:var(--color-text-dim);text-align:center">Ładowanie…</div>';
    fetch('/api/missions')
      .then(function(r){ return r.json(); })
      .then(function(missions) {
        var active = (missions || []).filter(function(m){ return m.status === 'active' || m.status === 'open'; });
        if (active.length === 0) {
          list.innerHTML = '<div style="padding:1.25rem 1rem;font-size:0.8rem;color:var(--color-text-dim);text-align:center">Brak aktywnych akcji</div>';
          if (dot) dot.style.display = 'none';
          return;
        }
        list.innerHTML = active.slice(0, 6).map(function(m) {
          var icon  = STATUS_ICON[m.status]  || 'info';
          var color = STATUS_COLOR[m.status] || 'var(--color-text-dim)';
          return '<a href="/missions/' + encodeURIComponent(m.id) + '" style="display:flex;align-items:flex-start;gap:0.75rem;padding:0.75rem 1rem;border-bottom:1px solid var(--color-border-light);text-decoration:none;color:var(--color-text);transition:background 0.12s" onmouseover="this.style.background=\'var(--color-surface-mid)\'" onmouseout="this.style.background=\'\'">'
            + '<span class="material-symbols-outlined" style="font-size:1.1rem;color:' + color + ';flex-shrink:0;margin-top:0.1rem">' + icon + '</span>'
            + '<div style="min-width:0">'
            + '<div style="font-size:0.8rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + m.title.replace(/</g,'&lt;') + '</div>'
            + '<div style="font-size:0.7rem;color:var(--color-text-dim);margin-top:0.15rem">' + m.location.replace(/</g,'&lt;') + ' &middot; ' + ({active:'Aktywna',open:'Otwarta',completed:'Zakończona',cancelled:'Anulowana'}[m.status]||m.status) + '</div>'
            + '</div></a>';
        }).join('');
        if (active.length > 6) {
          list.innerHTML += '<a href="/missions" style="display:block;padding:0.75rem 1rem;font-size:0.75rem;color:var(--color-primary);text-align:center;text-decoration:none;border-top:1px solid var(--color-border-light)">Zobacz wszystkie (' + active.length + ')</a>';
        }
      })
      .catch(function() {
        list.innerHTML = '<div style="padding:1rem;font-size:0.8rem;color:var(--color-danger);text-align:center">Błąd ładowania</div>';
      });
  }

  function showPanel() {
    var rect = btn.getBoundingClientRect();
    panel.style.top  = (rect.bottom + 6) + 'px';
    panel.style.left = Math.max(8, rect.right - 300) + 'px';
    panel.style.display = 'block';
    open = true;
    if (!loaded) { loaded = true; loadNotifs(); }
  }

  function hidePanel() {
    panel.style.display = 'none';
    open = false;
  }

  btn.addEventListener('click', function(e) {
    e.stopPropagation();
    open ? hidePanel() : showPanel();
  });

  document.addEventListener('click', function() {
    if (open) hidePanel();
  });

  panel.addEventListener('click', function(e) { e.stopPropagation(); });

  window.addEventListener('resize', hidePanel);
  window.addEventListener('scroll', function(e) {
    if (!panel.contains(e.target)) hidePanel();
  }, true);
})();
</script>
</body>
</html>

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
  <link rel="stylesheet" href="/public/css/app.css"/>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"/>
</head>
<body>

<div class="layout">
  <!-- Sidebar Overlay (mobile) -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- ========== SIDEBAR ========== -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar__brand">
      <div class="sidebar__brand-title">Base Command</div>
      <div class="sidebar__brand-sub">Sector 7 – Tatra</div>
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
        SOS Signal
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
        <button class="icon-btn" title="Powiadomienia">
          <span class="material-symbols-outlined">notifications_active</span>
          <span class="notification-dot"></span>
        </button>
        <button class="icon-btn" title="Ustawienia">
          <span class="material-symbols-outlined">settings</span>
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
          Encrypted Tactical Link v4.2.0
        </span>
        <span>Server: TATRA-MAIN-01</span>
      </div>
      <div class="text-xxs text-dim uppercase tracking-wide">TOPR Command © 2024</div>
    </footer>
  </div>
</div>

<!-- Ongoing Mission Indicator -->
<div class="mission-indicator" id="missionIndicator" style="display:none">
  <div class="mission-indicator__dot"></div>
  <div class="mission-indicator__text" id="missionIndicatorText">Ongoing Mission</div>
</div>

<script src="/public/js/app.js"></script>
</body>
</html>

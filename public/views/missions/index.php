<?php
$pageTitle  = 'Akcje Ratunkowe';
$activePage = 'missions';
ob_start();
?>

<main class="page-content">
  <?php if (!empty($success)): ?>
  <div class="alert alert--success">
    <span class="material-symbols-outlined">check_circle</span>
    <?= htmlspecialchars($success) ?>
  </div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
  <div class="alert alert--error">
    <span class="material-symbols-outlined">error</span>
    <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <div class="page-header">
    <div>
      <div class="page-subtitle">TOPR Rescue</div>
      <h1 class="page-title">Akcje Ratunkowe</h1>
    </div>
    <div class="page-header__actions" style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap">
      <select id="statusFilter" class="form-select" style="padding:0.5rem 1rem">
        <option value="all">Wszystkie statusy</option>
        <option value="open">Otwarte</option>
        <option value="active">Aktywne</option>
        <option value="completed">Zakończone</option>
        <option value="cancelled">Anulowane</option>
      </select>
      <?php if (SessionService::isCoordinator()): ?>
      <a href="/missions/new" class="btn btn--primary">
        <span class="material-symbols-outlined">add_alert</span>
        Nowa Akcja
      </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="table-wrap">
      <table class="table table--missions">
        <thead>
          <tr>
            <th>Tytuł</th>
            <th>Lokalizacja</th>
            <th>Typ zdarzenia</th>
            <th>Status</th>
            <th>Czas rozpoczęcia</th>
            <th>Akcje</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($missions)): ?>
          <tr>
            <td colspan="6" style="text-align:center;padding:2rem;color:var(--color-text-dim)">
              <span class="material-symbols-outlined" style="font-size:2rem;opacity:0.3;display:block;margin-bottom:0.5rem">description</span>
              Brak akcji do wyświetlenia
            </td>
          </tr>
          <?php else: ?>
          <?php foreach ($missions as $mission): ?>
          <tr data-status="<?= htmlspecialchars($mission->getStatus()) ?>"
              data-search="<?= htmlspecialchars($mission->getTitle() . ' ' . $mission->getLocation() . ' ' . $mission->getIncidentTypeName()) ?>">
            <td>
              <div style="font-weight:600;color:var(--color-text)"><?= htmlspecialchars($mission->getTitle()) ?></div>
            </td>
            <td>
              <div style="display:flex;align-items:center;gap:0.4rem;color:var(--color-text-muted)">
                <span class="material-symbols-outlined" style="font-size:0.875rem">location_on</span>
                <?= htmlspecialchars($mission->getLocation()) ?>
              </div>
            </td>
            <td><?= htmlspecialchars($mission->getIncidentTypeName() ?? '—') ?></td>
            <td><span class="badge <?= htmlspecialchars($mission->getStatusBadgeClass()) ?>"><?= htmlspecialchars($mission->getStatusLabel()) ?></span></td>
            <td style="font-size:0.75rem;color:var(--color-text-muted)">
              <?= $mission->getStartTime() ? date('d.m.Y H:i', strtotime($mission->getStartTime())) : '—' ?>
            </td>
            <td>
              <div style="display:flex;gap:0.5rem">
                <a href="/missions/<?= $mission->getId() ?>" class="btn btn--ghost btn--sm">
                  <span class="material-symbols-outlined">visibility</span>
                  <span class="btn-text">Szczegóły</span>
                </a>
                <?php if (SessionService::isCoordinator()): ?>
                <a href="/missions/<?= $mission->getId() ?>/edit" class="btn btn--secondary btn--sm">
                  <span class="material-symbols-outlined">edit</span>
                  <span class="btn-text">Edytuj</span>
                </a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>

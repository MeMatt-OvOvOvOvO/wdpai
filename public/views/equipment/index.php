<?php
$pageTitle  = 'Sprzęt';
$activePage = 'equipment';
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
      <div class="page-subtitle">Zarządzanie</div>
      <h1 class="page-title">Sprzęt Ratunkowy</h1>
    </div>
  </div>

  <!-- Stat Cards -->
  <div class="grid-3 equip-stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:2rem">
    <div class="stat-card">
      <div class="stat-card__label">
        Total
        <span class="material-symbols-outlined">inventory_2</span>
      </div>
      <div>
        <div class="stat-card__value"><?= str_pad((int)($stats['total'] ?? 0), 2, '0', STR_PAD_LEFT) ?></div>
        <div class="stat-card__sub stat-card__sub--muted">Łącznie</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-card__label">
        Ready
        <span class="material-symbols-outlined" style="color:var(--color-success)">check_circle</span>
      </div>
      <div>
        <div class="stat-card__value" style="color:var(--color-success)"><?= str_pad((int)($stats['ready'] ?? 0), 2, '0', STR_PAD_LEFT) ?></div>
        <div class="stat-card__sub stat-card__sub--green">Gotowy</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-card__label">
        In Use
        <span class="material-symbols-outlined" style="color:var(--color-info)">sync</span>
      </div>
      <div>
        <div class="stat-card__value" style="color:var(--color-info)"><?= str_pad((int)($stats['in_use'] ?? 0), 2, '0', STR_PAD_LEFT) ?></div>
        <div class="stat-card__sub" style="color:var(--color-info)">W użyciu</div>
      </div>
    </div>
    <div class="stat-card stat-card--critical">
      <div class="stat-card__label">
        Maintenance
        <span class="material-symbols-outlined" style="color:var(--color-warning)">build</span>
      </div>
      <div>
        <div class="stat-card__value" style="color:var(--color-warning)"><?= str_pad((int)($stats['maintenance'] ?? 0), 2, '0', STR_PAD_LEFT) ?></div>
        <div class="stat-card__sub" style="color:var(--color-warning)">Serwis</div>
      </div>
    </div>
  </div>

  <!-- Filters -->
  <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.5rem;flex-wrap:wrap">
    <button class="btn btn--primary btn--sm" data-filter-btn="all">Wszystko</button>
    <?php foreach ($equipmentTypes as $type): ?>
    <button class="btn btn--ghost btn--sm" data-filter-btn="<?= htmlspecialchars($type['name'] ?? $type['type_name'] ?? '') ?>">
      <?= htmlspecialchars($type['name'] ?? $type['type_name'] ?? '') ?>
    </button>
    <?php endforeach; ?>
  </div>

  <!-- Equipment Grid -->
  <?php if (empty($equipment)): ?>
  <div class="card" style="text-align:center;padding:3rem">
    <span class="material-symbols-outlined" style="font-size:3rem;opacity:0.3;display:block;margin-bottom:1rem">inventory</span>
    <p style="color:var(--color-text-dim)">Brak zarejestrowanego sprzętu</p>
  </div>
  <?php else: ?>
  <div class="grid-auto">
    <?php foreach ($equipment as $eq): ?>
    <?php
      $pct = (int)($eq->getServiceLifePct() ?? 100);
      $barColor = $pct >= 70 ? 'var(--color-success)' : ($pct >= 30 ? 'var(--color-warning)' : 'var(--color-danger)');
      $typeName = $eq->getTypeName() ?? '';
    ?>
    <div class="equip-card card"
         data-type="<?= htmlspecialchars($typeName) ?>"
         data-search="<?= htmlspecialchars($eq->getName() . ' ' . $eq->getSerialNumber() . ' ' . $typeName) ?>"
         style="position:relative;display:flex;flex-direction:column;gap:0.75rem">

      <!-- Status badge in corner -->
      <div style="display:flex;align-items:flex-start;justify-content:space-between">
        <div style="width:3rem;height:3rem;background:var(--color-surface-highest);border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center">
          <span class="material-symbols-outlined" style="color:var(--color-text-dim)">handheld_controller</span>
        </div>
        <span class="badge <?= htmlspecialchars($eq->getStatusBadgeClass()) ?>"><?= htmlspecialchars($eq->getStatus()) ?></span>
      </div>

      <!-- Name & Serial -->
      <div>
        <div class="equip-card__name" style="font-family:var(--font-headline);font-weight:700;font-size:0.9rem;color:var(--color-text)">
          <?= htmlspecialchars($eq->getName()) ?>
        </div>
        <div class="equip-card__serial" style="font-size:0.7rem;font-family:monospace;color:var(--color-text-dim);margin-top:0.2rem">
          <?= htmlspecialchars($eq->getSerialNumber()) ?>
        </div>
      </div>

      <!-- Meta -->
      <div style="display:flex;flex-direction:column;gap:0.3rem;font-size:0.75rem;color:var(--color-text-muted)">
        <div style="display:flex;align-items:center;gap:0.4rem">
          <span class="material-symbols-outlined" style="font-size:0.875rem">category</span>
          <?= htmlspecialchars($typeName ?: '—') ?>
        </div>
        <?php if ($eq->getLastInspection()): ?>
        <div style="display:flex;align-items:center;gap:0.4rem">
          <span class="material-symbols-outlined" style="font-size:0.875rem">event</span>
          Ostatnia inspekcja: <?= date('d.m.Y', strtotime($eq->getLastInspection())) ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Service Life Progress Bar -->
      <div>
        <div style="display:flex;justify-content:space-between;margin-bottom:0.3rem;font-size:0.65rem;color:var(--color-text-dim)">
          <span>Żywotność</span>
          <span style="color:<?= $barColor ?>"><?= $pct ?>%</span>
        </div>
        <div style="height:4px;background:var(--color-surface-highest);border-radius:2px;overflow:hidden">
          <div class="progress-bar__fill" style="height:100%;width:<?= $pct ?>%;background:<?= $barColor ?>;transition:width 0.3s"></div>
        </div>
      </div>

      <!-- Actions -->
      <div style="display:flex;gap:0.5rem;margin-top:auto;padding-top:0.5rem">
        <a href="/equipment/<?= $eq->getId() ?>" class="btn btn--ghost btn--sm" style="flex:1;justify-content:center">
          <span class="material-symbols-outlined">visibility</span>
          Szczegóły
        </a>
        <?php if (SessionService::isCoordinator()): ?>
        <a href="/equipment/<?= $eq->getId() ?>/edit" class="btn btn--secondary btn--sm" style="flex:1;justify-content:center">
          <span class="material-symbols-outlined">edit</span>
          Edytuj
        </a>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (SessionService::isCoordinator()): ?>
  <div style="margin-top:2rem;display:flex;justify-content:center">
    <a href="/equipment/new" class="btn btn--primary btn--lg">
      <span class="material-symbols-outlined">add</span>
      Register New Gear
    </a>
  </div>
  <?php endif; ?>
</main>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>

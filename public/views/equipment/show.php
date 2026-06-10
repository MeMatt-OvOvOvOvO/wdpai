<?php
$pageTitle  = htmlspecialchars($equipment->getName());
$activePage = 'equipment';
ob_start();

$pct      = (int)($equipment->getServiceLifePct() ?? 100);
$barColor = $pct >= 70 ? 'var(--color-success)' : ($pct >= 30 ? 'var(--color-warning)' : 'var(--color-danger)');
?>

<main class="page-content">
  <div class="page-header">
    <div>
      <div class="page-subtitle">Sprzęt Ratunkowy</div>
      <h1 class="page-title"><?= htmlspecialchars($equipment->getName()) ?></h1>
    </div>
    <div class="page-header__actions" style="display:flex;gap:0.75rem">
      <a href="/equipment" class="btn btn--ghost">
        <span class="material-symbols-outlined">arrow_back</span>
        <span class="btn-text">Powrót</span>
      </a>
      <?php if (SessionService::isCoordinator()): ?>
      <a href="/equipment/<?= $equipment->getId() ?>/edit" class="btn btn--secondary">
        <span class="material-symbols-outlined">edit</span>
        <span class="btn-text">Edytuj</span>
      </a>
      <?php endif; ?>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:2fr 1fr;gap:2rem">

    <!-- ===== LEWA KOLUMNA ===== -->
    <div class="card">
      <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.5rem">
        <span class="material-symbols-outlined" style="color:var(--color-primary)">handheld_controller</span>
        <h2 style="font-family:var(--font-headline);font-size:1rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em">
          Szczegóły Sprzętu
        </h2>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div>
          <div class="form-label">Numer seryjny</div>
          <div style="font-family:monospace;font-weight:500"><?= htmlspecialchars($equipment->getSerialNumber()) ?></div>
        </div>
        <div>
          <div class="form-label">Status</div>
          <span class="badge <?= htmlspecialchars($equipment->getStatusBadgeClass()) ?>"><?= htmlspecialchars($equipment->getStatusLabel()) ?></span>
        </div>
        <div>
          <div class="form-label">Kategoria</div>
          <div style="font-weight:500"><?= htmlspecialchars($equipment->getTypeName() ?? '—') ?></div>
        </div>
        <div>
          <div class="form-label">Ostatnia inspekcja</div>
          <div style="font-weight:500">
            <?= $equipment->getLastInspection() ? date('d.m.Y', strtotime($equipment->getLastInspection())) : '—' ?>
          </div>
        </div>
      </div>

      <?php if ($equipment->getNotes()): ?>
      <div style="margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid var(--color-border-light)">
        <div class="form-label">Uwagi</div>
        <p style="font-size:0.875rem;line-height:1.7;color:var(--color-text-muted);white-space:pre-wrap"><?= htmlspecialchars($equipment->getNotes()) ?></p>
      </div>
      <?php endif; ?>
    </div>

    <!-- ===== PRAWA KOLUMNA ===== -->
    <div class="card">
      <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.25rem">
        <span class="material-symbols-outlined" style="color:var(--color-primary)">monitor_heart</span>
        <h3 style="font-family:var(--font-headline);font-size:0.875rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em">
          Stan techniczny
        </h3>
      </div>

      <div style="margin-bottom:1rem">
        <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;font-size:0.8rem">
          <span style="color:var(--color-text-dim)">Żywotność sprzętu</span>
          <span style="font-weight:700;color:<?= $barColor ?>"><?= $pct ?>%</span>
        </div>
        <div style="height:8px;background:var(--color-surface-highest);border-radius:4px;overflow:hidden">
          <div style="height:100%;width:<?= $pct ?>%;background:<?= $barColor ?>;border-radius:4px;transition:width 0.3s"></div>
        </div>
        <div style="font-size:0.65rem;color:var(--color-text-dim);margin-top:0.4rem">
          <?php if ($pct >= 70): ?>
            Sprzęt w dobrym stanie
          <?php elseif ($pct >= 30): ?>
            Wymaga przeglądu
          <?php else: ?>
            Wymaga natychmiastowego serwisu
          <?php endif; ?>
        </div>
      </div>

      <?php if (SessionService::isCoordinator()): ?>
      <div style="padding-top:1rem;border-top:1px solid var(--color-border-light)">
        <div class="form-label" style="color:var(--color-danger);margin-bottom:0.75rem">Strefa Niebezpieczna</div>
        <form method="POST" action="/equipment/<?= $equipment->getId() ?>/delete"
              onsubmit="return confirmDelete('Czy na pewno chcesz usunąć ten sprzęt?')">
          <button type="submit" class="btn btn--danger btn--sm w-full" style="justify-content:center">
            <span class="material-symbols-outlined">delete</span>
            Usuń sprzęt
          </button>
        </form>
      </div>
      <?php endif; ?>
    </div>

  </div>
</main>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>

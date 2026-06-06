<?php
$pageTitle  = 'Edytuj: ' . htmlspecialchars($equipment->getName());
$activePage = 'equipment';
ob_start();
?>

<main class="page-content">
  <div class="page-header">
    <div>
      <div class="page-subtitle">Sprzęt</div>
      <h1 class="page-title">Edytuj Sprzęt</h1>
    </div>
    <div style="display:flex;gap:0.75rem">
      <a href="/equipment/<?= $equipment->getId() ?>" class="btn btn--ghost">
        <span class="material-symbols-outlined">arrow_back</span>
        Powrót
      </a>
    </div>
  </div>

  <?php if (!empty($error)): ?>
  <div class="alert alert--error">
    <span class="material-symbols-outlined">error</span>
    <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <div class="card" style="max-width:640px">
    <form method="POST" action="/equipment/<?= $equipment->getId() ?>/edit">
      <div class="form-group">
        <label class="form-label" for="name">Nazwa sprzętu *</label>
        <input class="form-input" type="text" id="name" name="name" required
               value="<?= htmlspecialchars($equipment->getName()) ?>">
      </div>

      <div class="form-group">
        <label class="form-label" for="serial_number">Numer seryjny *</label>
        <input class="form-input" type="text" id="serial_number" name="serial_number" required
               value="<?= htmlspecialchars($equipment->getSerialNumber()) ?>">
      </div>

      <div class="form-group">
        <label class="form-label" for="type_id">Typ sprzętu</label>
        <select class="form-select" id="type_id" name="type_id">
          <option value="">— Wybierz typ —</option>
          <?php foreach ($equipmentTypes as $type): ?>
          <?php $typeId = $type['id'] ?? $type['equipment_type_id'] ?? ''; ?>
          <option value="<?= htmlspecialchars($typeId) ?>"
            <?= ($equipment->getTypeName() === ($type['name'] ?? $type['type_name'] ?? '')) ? 'selected' : '' ?>>
            <?= htmlspecialchars($type['name'] ?? $type['type_name'] ?? '') ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="status">Status</label>
        <select class="form-select" id="status" name="status">
          <option value="ready"       <?= $equipment->getStatus() === 'ready'       ? 'selected' : '' ?>>Gotowy (ready)</option>
          <option value="in_use"      <?= $equipment->getStatus() === 'in_use'      ? 'selected' : '' ?>>W użyciu (in_use)</option>
          <option value="maintenance" <?= $equipment->getStatus() === 'maintenance' ? 'selected' : '' ?>>Serwis (maintenance)</option>
          <option value="retired"     <?= $equipment->getStatus() === 'retired'     ? 'selected' : '' ?>>Wycofany (retired)</option>
          <option value="lost"        <?= $equipment->getStatus() === 'lost'        ? 'selected' : '' ?>>Zaginiony (lost)</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="last_inspection">Ostatnia inspekcja</label>
        <input class="form-input" type="date" id="last_inspection" name="last_inspection"
               value="<?= $equipment->getLastInspection() ? date('Y-m-d', strtotime($equipment->getLastInspection())) : '' ?>">
      </div>

      <div class="form-group">
        <label class="form-label" for="service_life_pct">Żywotność (%)</label>
        <input class="form-input" type="number" id="service_life_pct" name="service_life_pct"
               min="0" max="100"
               value="<?= htmlspecialchars($equipment->getServiceLifePct() ?? 100) ?>">
      </div>

      <div class="form-group">
        <label class="form-label" for="notes">Uwagi</label>
        <textarea class="form-textarea" id="notes" name="notes" rows="4"><?= htmlspecialchars($equipment->getNotes() ?? '') ?></textarea>
      </div>

      <div style="display:flex;gap:0.75rem;justify-content:flex-end;margin-top:1.5rem">
        <a href="/equipment/<?= $equipment->getId() ?>" class="btn btn--ghost">Anuluj</a>
        <button type="submit" class="btn btn--primary">
          <span class="material-symbols-outlined">save</span>
          Zapisz zmiany
        </button>
      </div>
    </form>
  </div>

  <!-- Danger Zone -->
  <div class="card" style="max-width:640px;margin-top:2rem;border:1px solid rgba(239,68,68,0.3)">
    <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1rem">
      <span class="material-symbols-outlined" style="color:var(--color-danger)">warning</span>
      <h3 style="font-family:var(--font-headline);font-size:0.875rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--color-danger)">
        Danger Zone
      </h3>
    </div>
    <p style="font-size:0.8rem;color:var(--color-text-muted);margin-bottom:1rem">
      Usunięcie sprzętu jest nieodwracalne. Rekord zostanie trwale skasowany z bazy danych.
    </p>
    <form method="POST" action="/equipment/<?= $equipment->getId() ?>/delete"
          onsubmit="return confirmDelete('Czy na pewno chcesz usunąć ten sprzęt? Operacja jest nieodwracalna.')">
      <button type="submit" class="btn btn--danger">
        <span class="material-symbols-outlined">delete_forever</span>
        Usuń sprzęt
      </button>
    </form>
  </div>
</main>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>

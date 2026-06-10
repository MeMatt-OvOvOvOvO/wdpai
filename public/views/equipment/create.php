<?php
$pageTitle  = 'Rejestracja Sprzętu';
$activePage = 'equipment';
ob_start();
?>

<main class="page-content">
  <div class="page-header">
    <div>
      <div class="page-subtitle">Sprzęt</div>
      <h1 class="page-title">Rejestracja Sprzętu</h1>
    </div>
    <a href="/equipment" class="btn btn--ghost">
      <span class="material-symbols-outlined">arrow_back</span>
      Powrót
    </a>
  </div>

  <?php if (!empty($error)): ?>
  <div class="alert alert--error">
    <span class="material-symbols-outlined">error</span>
    <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <div class="card" style="max-width:640px">
    <form method="POST" action="/equipment/new">
      <div class="form-group">
        <label class="form-label" for="name">Nazwa sprzętu *</label>
        <input class="form-input" type="text" id="name" name="name" required
               placeholder="Np. Lina dynamiczna 60m"
               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label class="form-label" for="serial_number">Numer seryjny *</label>
        <input class="form-input" type="text" id="serial_number" name="serial_number" required
               placeholder="Np. SN-2024-001"
               value="<?= htmlspecialchars($_POST['serial_number'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label class="form-label" for="type_id">Typ sprzętu</label>
        <select class="form-select" id="type_id" name="type_id">
          <option value="">— Wybierz typ —</option>
          <?php foreach ($equipmentTypes as $type): ?>
          <?php $typeId = $type['id'] ?? $type['equipment_type_id'] ?? ''; ?>
          <option value="<?= htmlspecialchars($typeId) ?>"
            <?= (($_POST['type_id'] ?? '') == $typeId) ? 'selected' : '' ?>>
            <?= htmlspecialchars($type['name'] ?? $type['type_name'] ?? '') ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="status">Status</label>
        <select class="form-select" id="status" name="status">
          <option value="ready"       <?= (($_POST['status'] ?? 'ready') === 'ready')       ? 'selected' : '' ?>>Gotowy</option>
          <option value="in_use"      <?= (($_POST['status'] ?? '') === 'in_use')            ? 'selected' : '' ?>>W użyciu</option>
          <option value="maintenance" <?= (($_POST['status'] ?? '') === 'maintenance')       ? 'selected' : '' ?>>Serwis</option>
          <option value="retired"     <?= (($_POST['status'] ?? '') === 'retired')           ? 'selected' : '' ?>>Wycofany</option>
          <option value="lost"        <?= (($_POST['status'] ?? '') === 'lost')              ? 'selected' : '' ?>>Zaginiony</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="last_inspection">Ostatnia inspekcja</label>
        <input class="form-input" type="date" id="last_inspection" name="last_inspection"
               value="<?= htmlspecialchars($_POST['last_inspection'] ?? date('Y-m-d')) ?>">
      </div>

      <div class="form-group">
        <label class="form-label" for="service_life_pct">Żywotność (%) </label>
        <input class="form-input" type="number" id="service_life_pct" name="service_life_pct"
               min="0" max="100" placeholder="100"
               value="<?= htmlspecialchars($_POST['service_life_pct'] ?? '100') ?>">
      </div>

      <div class="form-group">
        <label class="form-label" for="notes">Uwagi</label>
        <textarea class="form-textarea" id="notes" name="notes" rows="4"
                  placeholder="Dodatkowe informacje o sprzęcie..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
      </div>

      <div style="display:flex;gap:0.75rem;justify-content:flex-end;margin-top:1.5rem">
        <a href="/equipment" class="btn btn--ghost">Anuluj</a>
        <button type="submit" class="btn btn--primary">
          <span class="material-symbols-outlined">add</span>
          Zarejestruj sprzęt
        </button>
      </div>
    </form>
  </div>
</main>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>

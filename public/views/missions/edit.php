<?php
$pageTitle  = 'Edytuj: ' . htmlspecialchars($mission->getTitle());
$activePage = 'missions';
ob_start();
?>

<main class="page-content">
  <div class="page-header">
    <div>
      <div class="page-subtitle">Akcje Ratunkowe</div>
      <h1 class="page-title">Edytuj Akcję</h1>
    </div>
    <div class="page-header__actions" style="display:flex;gap:0.75rem">
      <a href="/missions/<?= $mission->getId() ?>" class="btn btn--ghost">
        <span class="material-symbols-outlined">arrow_back</span>
        <span class="btn-text">Powrót</span>
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
    <form method="POST" action="/missions/<?= $mission->getId() ?>/edit">
      <div class="form-group">
        <label class="form-label" for="title">Tytuł akcji *</label>
        <input class="form-input" type="text" id="title" name="title" required
               value="<?= htmlspecialchars($mission->getTitle()) ?>">
      </div>

      <div class="form-group">
        <label class="form-label" for="location">Lokalizacja *</label>
        <input class="form-input" type="text" id="location" name="location" required
               value="<?= htmlspecialchars($mission->getLocation()) ?>">
      </div>

      <div class="form-group">
        <label class="form-label" for="incident_type_id">Typ zdarzenia</label>
        <select class="form-select" id="incident_type_id" name="incident_type_id">
          <option value="">— Wybierz typ —</option>
          <?php foreach ($incidentTypes as $type): ?>
          <?php $typeId = $type['id'] ?? $type['incident_type_id'] ?? ''; ?>
          <option value="<?= htmlspecialchars($typeId) ?>"
            <?= ($typeId == ($mission->getIncidentTypeName() ? '' : '')) ? '' : '' ?>>
            <?= htmlspecialchars($type['name'] ?? $type['type_name'] ?? '') ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="coordinates">Koordynaty GPS</label>
        <input class="form-input" type="text" id="coordinates" name="coordinates"
               placeholder='49°11&apos;20" N, 20°04&apos;40" E'
               value="<?= htmlspecialchars($mission->getCoordinates() ?? '') ?>">
      </div>

      <div class="form-group">
        <label class="form-label" for="status">Status</label>
        <select class="form-select" id="status" name="status">
          <option value="open"      <?= $mission->getStatus() === 'open'      ? 'selected' : '' ?>>Otwarte</option>
          <option value="active"    <?= $mission->getStatus() === 'active'    ? 'selected' : '' ?>>Aktywne</option>
          <option value="completed" <?= $mission->getStatus() === 'completed' ? 'selected' : '' ?>>Zakończone</option>
          <option value="cancelled" <?= $mission->getStatus() === 'cancelled' ? 'selected' : '' ?>>Anulowane</option>
        </select>
      </div>

      <div class="name-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div class="form-group">
          <label class="form-label" for="start_time">Czas rozpoczęcia</label>
          <input class="form-input" type="datetime-local" id="start_time" name="start_time"
                 value="<?= $mission->getStartTime() ? date('Y-m-d\TH:i', strtotime($mission->getStartTime())) : '' ?>">
        </div>
        <div class="form-group">
          <label class="form-label" for="end_time">Czas zakończenia</label>
          <input class="form-input" type="datetime-local" id="end_time" name="end_time"
                 value="<?= $mission->getEndTime() ? date('Y-m-d\TH:i', strtotime($mission->getEndTime())) : '' ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="description">Opis zdarzenia</label>
        <textarea class="form-textarea" id="description" name="description" rows="5"><?= htmlspecialchars($mission->getDescription() ?? '') ?></textarea>
      </div>

      <div style="display:flex;gap:0.75rem;justify-content:flex-end;margin-top:1.5rem">
        <a href="/missions/<?= $mission->getId() ?>" class="btn btn--ghost">Anuluj</a>
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
      Usunięcie akcji jest nieodwracalne. Wszystkie powiązane dane (ratownicy, sprzęt) zostaną odłączone.
    </p>
    <form method="POST" action="/missions/<?= $mission->getId() ?>/delete"
          onsubmit="return confirmDelete('Czy na pewno chcesz usunąć tę akcję? Operacja jest nieodwracalna.')">
      <button type="submit" class="btn btn--danger">
        <span class="material-symbols-outlined">delete_forever</span>
        Usuń akcję
      </button>
    </form>
  </div>
</main>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>

<?php
$pageTitle  = 'Nowa Akcja';
$activePage = 'missions';
ob_start();
?>

<main class="page-content">
  <div class="page-header">
    <div>
      <div class="page-subtitle">Akcje Ratunkowe</div>
      <h1 class="page-title">Nowa Akcja</h1>
    </div>
    <a href="/missions" class="btn btn--ghost">
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
    <form method="POST" action="/missions/new">
      <div class="form-group">
        <label class="form-label" for="title">Tytuł akcji *</label>
        <input class="form-input" type="text" id="title" name="title" required
               placeholder="Np. Poszukiwanie turysty na Kasprowym"
               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label class="form-label" for="location">Lokalizacja *</label>
        <input class="form-input" type="text" id="location" name="location" required
               placeholder="Np. Kasprowy Wierch, szlak czerwony"
               value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label class="form-label" for="incident_type_id">Typ zdarzenia</label>
        <select class="form-select" id="incident_type_id" name="incident_type_id">
          <option value="">— Wybierz typ —</option>
          <?php foreach ($incidentTypes as $type): ?>
          <option value="<?= htmlspecialchars($type['id'] ?? $type['incident_type_id'] ?? '') ?>"
            <?= (($_POST['incident_type_id'] ?? '') == ($type['id'] ?? $type['incident_type_id'] ?? '')) ? 'selected' : '' ?>>
            <?= htmlspecialchars($type['name'] ?? $type['type_name'] ?? '') ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="coordinates">Koordynaty GPS (opcjonalne)</label>
        <input class="form-input" type="text" id="coordinates" name="coordinates"
               placeholder='49°11&apos;20" N, 20°04&apos;40" E'
               value="<?= htmlspecialchars($_POST['coordinates'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label class="form-label" for="start_time">Czas rozpoczęcia</label>
        <input class="form-input" type="datetime-local" id="start_time" name="start_time"
               value="<?= htmlspecialchars($_POST['start_time'] ?? date('Y-m-d\TH:i')) ?>">
      </div>

      <div class="form-group">
        <label class="form-label" for="description">Opis zdarzenia</label>
        <textarea class="form-textarea" id="description" name="description" rows="5"
                  placeholder="Szczegółowy opis sytuacji, warunków, znanych informacji..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
      </div>

      <div style="display:flex;gap:0.75rem;justify-content:flex-end;margin-top:1.5rem">
        <a href="/missions" class="btn btn--ghost">Anuluj</a>
        <button type="submit" class="btn btn--primary">
          <span class="material-symbols-outlined">add_alert</span>
          Utwórz Akcję
        </button>
      </div>
    </form>
  </div>
</main>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>

<?php
$pageTitle  = 'Mój Profil';
$activePage = 'profile';
ob_start();
?>

<main class="page-content">
  <div class="page-header">
    <div>
      <div class="page-subtitle">Konto</div>
      <h1 class="page-title">Mój Profil</h1>
    </div>
  </div>

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

  <div style="display:grid;grid-template-columns:1fr 2fr;gap:2rem;max-width:900px">

    <!-- Info o koncie (tylko do odczytu) -->
    <div class="card">
      <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.5rem">
        <span class="material-symbols-outlined icon-filled" style="font-size:3rem;color:var(--color-text-dim)">account_circle</span>
        <div>
          <div style="font-family:var(--font-headline);font-weight:700;font-size:1rem"><?= htmlspecialchars($user->getUsername()) ?></div>
          <?php if ($user->getRoleName() === 'coordinator'): ?>
          <span class="badge badge--active">Koordynator</span>
          <?php else: ?>
          <span class="badge badge--in-use">Ratownik</span>
          <?php endif; ?>
        </div>
      </div>

      <div style="display:flex;flex-direction:column;gap:1rem">
        <div>
          <div class="form-label">Email</div>
          <div style="font-size:0.875rem;color:var(--color-text-muted)"><?= htmlspecialchars($user->getEmail()) ?></div>
        </div>
        <div>
          <div class="form-label">Rola</div>
          <div style="font-size:0.875rem"><?= htmlspecialchars(ucfirst($user->getRoleName() ?? '')) ?></div>
        </div>
        <?php if (!empty($profile['rank'])): ?>
        <div>
          <div class="form-label">Stopień</div>
          <div style="font-size:0.875rem"><?= htmlspecialchars($profile['rank']) ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($profile['phone'])): ?>
        <div>
          <div class="form-label">Telefon</div>
          <div style="font-size:0.875rem"><?= htmlspecialchars($profile['phone']) ?></div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Formularz edycji profilu -->
    <div class="card">
      <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.5rem">
        <span class="material-symbols-outlined" style="color:var(--color-primary)">edit</span>
        <h3 style="font-family:var(--font-headline);font-size:0.875rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em">
          Edytuj Profil
        </h3>
      </div>

      <form method="POST" action="/profile">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
          <div class="form-group">
            <label class="form-label" for="first_name">Imię</label>
            <input class="form-input" type="text" id="first_name" name="first_name"
                   value="<?= htmlspecialchars($profile['first_name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label" for="last_name">Nazwisko</label>
            <input class="form-input" type="text" id="last_name" name="last_name"
                   value="<?= htmlspecialchars($profile['last_name'] ?? '') ?>">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="rank">Stopień / Funkcja</label>
          <input class="form-input" type="text" id="rank" name="rank"
                 placeholder="Np. Starszy Ratownik TOPR"
                 value="<?= htmlspecialchars($profile['rank'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label class="form-label" for="phone">Telefon kontaktowy</label>
          <input class="form-input" type="tel" id="phone" name="phone"
                 placeholder="+48 000 000 000"
                 value="<?= htmlspecialchars($profile['phone'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label class="form-label" for="bio">Bio / Opis</label>
          <textarea class="form-textarea" id="bio" name="bio" rows="5"
                    placeholder="Krótki opis, specjalizacje, doświadczenie..."><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
        </div>

        <div style="display:flex;justify-content:flex-end;margin-top:1rem">
          <button type="submit" class="btn btn--primary">
            <span class="material-symbols-outlined">save</span>
            Zapisz profil
          </button>
        </div>
      </form>
    </div>
  </div>
</main>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>

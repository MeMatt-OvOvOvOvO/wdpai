<?php
$pageTitle  = 'Edytuj użytkownika';
$activePage = 'users';
ob_start();

if (!SessionService::isCoordinator()):
?>
<main class="page-content" style="display:flex;align-items:center;justify-content:center;min-height:60vh">
  <div style="text-align:center">
    <span class="material-symbols-outlined" style="font-size:4rem;color:var(--color-primary);display:block;margin-bottom:1rem">lock</span>
    <h2 style="font-family:var(--font-headline);font-size:1.5rem;text-transform:uppercase;margin-bottom:0.5rem">Brak uprawnień</h2>
    <p style="color:var(--color-text-muted);margin-bottom:1.5rem">Ta sekcja jest dostępna tylko dla koordynatorów.</p>
    <a href="/dashboard" class="btn btn--primary">← Powrót do Dashboard</a>
  </div>
</main>
<?php else: ?>

<main class="page-content">
  <div class="page-header">
    <div>
      <div class="page-subtitle">Użytkownicy</div>
      <h1 class="page-title">Edytuj Użytkownika</h1>
    </div>
    <a href="/users" class="btn btn--ghost">
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
  <?php if (!empty($success)): ?>
  <div class="alert alert--success">
    <span class="material-symbols-outlined">check_circle</span>
    <?= htmlspecialchars($success) ?>
  </div>
  <?php endif; ?>

  <div class="edit-form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;max-width:900px">

    <!-- Konto -->
    <div class="card">
      <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.5rem">
        <span class="material-symbols-outlined" style="color:var(--color-primary)">manage_accounts</span>
        <h3 style="font-family:var(--font-headline);font-size:0.875rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em">
          Konto
        </h3>
      </div>

      <div style="margin-bottom:1rem;padding:0.75rem;background:var(--color-surface-mid);border-radius:var(--radius-sm)">
        <div class="form-label">Nazwa użytkownika</div>
        <div style="font-weight:600"><?= htmlspecialchars($user->getUsername()) ?></div>
        <div class="form-label" style="margin-top:0.75rem">Email</div>
        <div style="color:var(--color-text-muted)"><?= htmlspecialchars($user->getEmail()) ?></div>
      </div>

      <form method="POST" action="/users/<?= $user->getId() ?>/edit">
        <input type="hidden" name="section" value="account">

        <div class="form-group">
          <label class="form-label" for="role_id">Rola</label>
          <select class="form-select" id="role_id" name="role_id">
            <?php foreach ($roles as $role): ?>
            <?php $roleId = $role['id'] ?? $role['role_id'] ?? ''; ?>
            <option value="<?= htmlspecialchars($roleId) ?>"
              <?= ($user->getRoleName() === ($role['name'] ?? $role['role_name'] ?? '')) ? 'selected' : '' ?>>
              <?= htmlspecialchars($role['name'] ?? $role['role_name'] ?? '') ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label style="display:flex;align-items:center;gap:0.75rem;cursor:pointer">
            <input type="checkbox" name="is_active" value="1"
                   <?= $user->isActive() ? 'checked' : '' ?>
                   style="width:1rem;height:1rem;accent-color:var(--color-primary)">
            <span class="form-label" style="margin:0">Konto aktywne</span>
          </label>
        </div>

        <button type="submit" class="btn btn--primary btn--sm">
          <span class="material-symbols-outlined">save</span>
          Zapisz konto
        </button>
      </form>
    </div>

    <!-- Profil -->
    <div class="card">
      <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.5rem">
        <span class="material-symbols-outlined" style="color:var(--color-primary)">person</span>
        <h3 style="font-family:var(--font-headline);font-size:0.875rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em">
          Profil
        </h3>
      </div>

      <form method="POST" action="/users/<?= $user->getId() ?>/edit">
        <input type="hidden" name="section" value="profile">

        <div class="name-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
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
          <label class="form-label" for="phone">Telefon</label>
          <input class="form-input" type="tel" id="phone" name="phone"
                 placeholder="+48 000 000 000"
                 value="<?= htmlspecialchars($profile['phone'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label class="form-label" for="bio">Bio</label>
          <textarea class="form-textarea" id="bio" name="bio" rows="4"
                    placeholder="Krótki opis..."><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn--secondary btn--sm">
          <span class="material-symbols-outlined">save</span>
          Zapisz profil
        </button>
      </form>
    </div>
  </div>
</main>

<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>

<?php
$pageTitle  = 'Użytkownicy';
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
      <h1 class="page-title">Użytkownicy</h1>
    </div>
  </div>

  <div class="card">
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Użytkownik</th>
            <th>Email</th>
            <th>Rola</th>
            <th>Status</th>
            <th>Akcje</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($users)): ?>
          <tr>
            <td colspan="5" style="text-align:center;padding:2rem;color:var(--color-text-dim)">
              Brak użytkowników
            </td>
          </tr>
          <?php else: ?>
          <?php foreach ($users as $u): ?>
          <tr data-search="<?= htmlspecialchars($u->getUsername() . ' ' . $u->getEmail() . ' ' . $u->getRoleName()) ?>">
            <td>
              <div style="display:flex;align-items:center;gap:0.75rem">
                <span class="material-symbols-outlined icon-filled" style="color:var(--color-text-dim)">account_circle</span>
                <div>
                  <div style="font-weight:600"><?= htmlspecialchars($u->getUsername()) ?></div>
                </div>
              </div>
            </td>
            <td style="color:var(--color-text-muted)"><?= htmlspecialchars($u->getEmail()) ?></td>
            <td>
              <?php if ($u->getRoleName() === 'coordinator'): ?>
              <span class="badge badge--active">Koordynator</span>
              <?php else: ?>
              <span class="badge badge--in-use">Ratownik</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($u->isActive()): ?>
              <span class="badge badge--ready">Aktywny</span>
              <?php else: ?>
              <span class="badge badge--cancelled">Nieaktywny</span>
              <?php endif; ?>
            </td>
            <td>
              <div style="display:flex;gap:0.5rem">
                <a href="/users/<?= $u->getId() ?>/edit" class="btn btn--secondary btn--sm">
                  <span class="material-symbols-outlined">edit</span>
                  Edytuj
                </a>
                <form method="POST" action="/users/<?= $u->getId() ?>/delete" style="margin:0"
                      onsubmit="return confirmDelete('Czy na pewno chcesz usunąć użytkownika <?= htmlspecialchars(addslashes($u->getUsername())) ?>?')">
                  <button type="submit" class="btn btn--danger btn--sm">
                    <span class="material-symbols-outlined">delete</span>
                  </button>
                </form>
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

<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>

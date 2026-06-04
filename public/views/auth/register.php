<!DOCTYPE html>
<html lang="pl" class="dark">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>TOPR Rescue – Rejestracja</title>
  <link rel="stylesheet" href="/public/css/app.css"/>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"/>
</head>
<body>
<div class="login-corner login-corner--tl"></div>
<div class="login-corner login-corner--tr"></div>
<div class="login-corner login-corner--bl"></div>
<div class="login-corner login-corner--br"></div>

<div class="login-page">
  <div class="login-page__bg">
    <div style="width:100%;height:100%;background:linear-gradient(135deg,#0c0e10 0%,#1a0a0a 100%);"></div>
    <div class="login-page__bg-overlay"></div>
  </div>

  <div class="login-page__content animate-fadein">
    <div class="login-brand">
      <div class="login-brand__icon">
        <span class="material-symbols-outlined icon-filled" style="font-size:1.75rem">emergency</span>
      </div>
      <div class="login-brand__title">TOPR Rescue</div>
      <div class="login-brand__divider"></div>
      <div class="login-brand__subtitle">Nowe konto personelu</div>
    </div>

    <div class="login-terminal">
      <div class="login-terminal__header">
        <div class="login-terminal__status">
          <div class="status-dot"></div>
          Rejestracja operatora
        </div>
      </div>
      <div class="login-terminal__body">
        <div class="login-terminal__title">Utwórz konto</div>
        <div class="login-terminal__sub">Nowy personel ratowniczy</div>

        <?php if (!empty($error)): ?>
        <div class="alert alert--error">
          <span class="material-symbols-outlined">error</span>
          <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="/register" id="registerForm">
          <!-- C2: CSRF token -->
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>"/>
          <div class="form-group">
            <label class="form-label">Nazwa użytkownika</label>
            <div class="form-input-icon">
              <span class="material-symbols-outlined">person</span>
              <input class="form-input" type="text" name="username" placeholder="Login / Rescue ID" required/>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Email</label>
            <div class="form-input-icon">
              <span class="material-symbols-outlined">mail</span>
              <input class="form-input" type="email" name="email" placeholder="adres@topr.pl" required/>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Hasło (min. 8 znaków)</label>
            <div class="form-input-icon">
              <span class="material-symbols-outlined">lock</span>
              <input class="form-input" type="password" name="password" placeholder="••••••••" required minlength="8"/>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Potwierdź hasło</label>
            <div class="form-input-icon">
              <span class="material-symbols-outlined">lock_reset</span>
              <input class="form-input" type="password" name="password2" placeholder="Powtórz hasło" required minlength="8"/>
            </div>
          </div>

          <button type="submit" class="btn btn--primary btn--lg w-full mt-4" style="justify-content:center">
            <span class="material-symbols-outlined">person_add</span>
            Zarejestruj się
          </button>
        </form>

        <div class="login-links">
          <a href="/login">← Powrót do logowania</a>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>

<!DOCTYPE html>
<html lang="pl" class="dark">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>TOPR Rescue – Logowanie</title>
  <link rel="stylesheet" href="/public/css/app.css"/>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"/>
</head>
<body>

<!-- Tactical corner decorations -->
<div class="login-corner login-corner--tl"></div>
<div class="login-corner login-corner--tr"></div>
<div class="login-corner login-corner--bl"></div>
<div class="login-corner login-corner--br"></div>

<div class="login-page">
  <!-- Background -->
  <div class="login-page__bg">
    <div class="login-page__bg-img" style="background: linear-gradient(135deg, #0c0e10 0%, #1a0a0a 100%); width:100%; height:100%;"></div>
    <div class="login-page__bg-overlay"></div>
  </div>

  <div class="login-page__content animate-fadein">
    <!-- Brand -->
    <div class="login-brand">
      <div class="login-brand__icon">
        <span class="material-symbols-outlined icon-filled" style="font-size:1.75rem">emergency</span>
      </div>
      <div class="login-brand__title">TOPR Rescue</div>
      <div class="login-brand__divider"></div>
      <div class="login-brand__subtitle">Bezpieczny Terminal Dostępu</div>
    </div>

    <!-- Terminal box -->
    <div class="login-terminal">
      <div class="login-terminal__header">
        <div class="login-terminal__status">
          <div class="status-dot"></div>
          System Online // Zaszyfrowano
        </div>
        <div class="login-terminal__id">Terminal ID: 097-RC</div>
      </div>
      <div class="login-terminal__body">
        <div class="login-terminal__title">Bezpieczne Logowanie</div>
        <div class="login-terminal__sub">Tylko Personel Dowodzenia</div>

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

        <form method="POST" action="/login" id="loginForm">
          <!-- B2: CSRF token -->
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>"/>
          <div class="form-group">
            <label class="form-label">ID Służbowe / Email</label>
            <div class="form-input-icon">
              <span class="material-symbols-outlined">badge</span>
              <input class="form-input"
                     type="email"
                     name="email"
                     placeholder="Rescue ID lub email"
                     required
                     autocomplete="email"/>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Hasło</label>
            <div style="position:relative">
              <div class="form-input-icon">
                <span class="material-symbols-outlined">lock</span>
                <input class="form-input"
                       type="password"
                       name="password"
                       id="passwordField"
                       placeholder="Wprowadź hasło"
                       required
                       autocomplete="current-password"
                       style="padding-right:2.75rem"/>
              </div>
              <button type="button"
                      onclick="togglePassword()"
                      style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);color:var(--color-text-dim);background:none;border:none;cursor:pointer;display:flex;align-items:center;z-index:2;">
                <span class="material-symbols-outlined" id="eyeIcon">visibility_off</span>
              </button>
            </div>
          </div>

          <div class="flex items-center justify-between mt-2 mb-4" style="padding:0 2px">
            <label class="flex items-center gap-2" style="cursor:pointer;font-size:0.7rem;color:var(--color-text-muted)">
              <input type="checkbox" name="remember" style="accent-color:var(--color-primary)"/>
              Zapamiętaj sesję
            </label>
          </div>

          <button type="submit" class="btn btn--primary btn--lg w-full" style="justify-content:center">
            <span class="material-symbols-outlined">login</span>
            Zaloguj się
          </button>
        </form>

        <div class="login-links">
          <a href="#">Nie pamiętasz hasła?</a>
          <a href="/register">Zarejestruj konto</a>
        </div>

        <div class="login-telemetry">
          <span>LAT: 49.2319° N</span>
          <span>LOC: KASPROWY</span>
          <span>LON: 19.9817° E</span>
        </div>
      </div>
    </div>

    <div class="login-footer">
      <span class="material-symbols-outlined" style="font-size:0.8rem">security</span>
      Zaszyfrowane Łącze Taktyczne v4.2.0
    </div>

    <p style="margin-top:1rem;font-size:0.55rem;color:rgba(255,255,255,0.3);text-transform:uppercase;letter-spacing:0.1em;text-align:center;max-width:300px">
      Nieautoryzowany dostęp do terminala jest surowo zabroniony zgodnie z Regulaminem Bezpieczeństwa Alpejskiego 10-C.
    </p>
  </div>
</div>

<script src="/public/js/app.js"></script>
<script>
function togglePassword() {
  const field = document.getElementById('passwordField');
  const icon  = document.getElementById('eyeIcon');
  if (field.type === 'password') {
    field.type = 'text';
    icon.textContent = 'visibility';
  } else {
    field.type = 'password';
    icon.textContent = 'visibility_off';
  }
}
</script>
</body>
</html>

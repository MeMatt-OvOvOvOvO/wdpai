// ============================================================
// TOPR Rescue – app.js (vanilla JS, no frameworks)
// ============================================================

document.addEventListener('DOMContentLoaded', () => {

  // 1. Hamburger / sidebar toggle
  const hamburger = document.getElementById('hamburgerBtn');
  const sidebar   = document.getElementById('sidebar');
  const overlay   = document.getElementById('sidebarOverlay');
  if (hamburger) {
    hamburger.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      overlay.classList.toggle('active');
    });
    overlay.addEventListener('click', () => {
      sidebar.classList.remove('open');
      overlay.classList.remove('active');
    });
  }

  // 2. Global search – filters table rows and equipment cards
  const searchInput = document.getElementById('globalSearch');
  if (searchInput) {
    searchInput.addEventListener('input', e => {
      const q = e.target.value.toLowerCase();
      document.querySelectorAll('tr[data-search], .equip-card[data-search]').forEach(el => {
        el.style.display = el.dataset.search.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }

  // 3. Status filter buttons (equipment categories)
  document.querySelectorAll('[data-filter-btn]').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('[data-filter-btn]').forEach(b => b.classList.remove('btn--primary'));
      btn.classList.add('btn--primary');
      const filter = btn.dataset.filterBtn;
      document.querySelectorAll('.equip-card').forEach(card => {
        card.style.display = (filter === 'all' || card.dataset.type === filter) ? '' : 'none';
      });
    });
  });

  // 4. Status filter select (missions list)
  const statusFilter = document.getElementById('statusFilter');
  if (statusFilter) {
    statusFilter.addEventListener('change', e => {
      const val = e.target.value;
      document.querySelectorAll('tr[data-status]').forEach(row => {
        row.style.display = (val === 'all' || row.dataset.status === val) ? '' : 'none';
      });
    });
  }

  // 5. Add equipment to mission via Fetch API
  const addEquipForm = document.getElementById('addEquipForm');
  if (addEquipForm) {
    addEquipForm.addEventListener('submit', async e => {
      e.preventDefault();
      const missionId = addEquipForm.dataset.missionId;
      const equipId   = addEquipForm.querySelector('[name="equipment_id"]').value;
      const quantity  = addEquipForm.querySelector('[name="quantity"]').value || 1;
      const resultEl  = document.getElementById('addEquipResult');

      try {
        const res = await fetch(`/api/missions/${missionId}/equipment`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ equipment_id: parseInt(equipId), quantity: parseInt(quantity) })
        });
        const data = await res.json();
        if (data.success) {
          resultEl.textContent = 'Sprzęt dodany pomyślnie.';
          resultEl.className = 'alert alert--success';
          setTimeout(() => location.reload(), 1200);
        } else {
          resultEl.textContent = data.error || 'Błąd podczas dodawania.';
          resultEl.className = 'alert alert--error';
        }
      } catch {
        resultEl.textContent = 'Błąd połączenia.';
        resultEl.className = 'alert alert--error';
      }
      resultEl.style.display = 'flex';
    });
  }

  // 6. Stats refresh on dashboard
  if (document.getElementById('statOpenMissions')) {
    fetch('/api/stats')
      .then(r => r.json())
      .then(data => {
        const el = document.getElementById('statOpenMissions');
        if (el) el.textContent = String(data.missions.open_missions).padStart(2, '0');
      })
      .catch(() => {});
  }

  // 7. Live Operations Map (Leaflet + Fetch API) – tylko na dashboardzie
  initOpsMap();

});

// ============================================================
// 7a. Live Operations Map – inicjalizacja mapy Leaflet osadzonej
//     w panelu "Live Operations Map" na dashboardzie. Mapa jest
//     wyśrodkowana na Tatrach, a aktywne akcje ratunkowe są pobierane
//     asynchronicznie przez Fetch API z endpointu /api/missions
//     i nanoszone jako kolorowe markery (kolor zależny od statusu akcji).
// ============================================================

function initOpsMap() {
    const mapEl = document.getElementById('opsMap');

    // Brak kontenera (inna strona) lub brak biblioteki Leaflet – nic nie rób.
    if (!mapEl || typeof L === 'undefined') return;

    const TATRA_CENTER = [49.2319, 19.9817]; // Kasprowy Wierch – środek sektora operacyjnego

    const map = L.map(mapEl, {
        zoomControl: true,
        attributionControl: true,
    }).setView(TATRA_CENTER, 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map);

    // Marker bazy dowodzenia TOPR
    L.circleMarker(TATRA_CENTER, {
        radius: 8,
        color: '#ef4444',
        fillColor: '#ef4444',
        fillOpacity: 0.9,
        weight: 2,
    })
        .addTo(map)
        .bindPopup('<strong>Baza TOPR – Sektor 7</strong><br>Centrum dowodzenia (Kasprowy Wierch)');

    const STATUS_COLORS = {
        open: '#f59e0b',
        active: '#ef4444',
        completed: '#22c55e',
        cancelled: '#6b7280',
    };

    // Pobierz listę akcji przez Fetch API (ten sam endpoint co lista akcji /missions)
    // i nanieś na mapę te, które mają zapisane współrzędne (kolumna missions.coordinates).
    fetch('/api/missions')
        .then(res => res.json())
        .then(missions => {
            (missions || []).forEach(mission => {
                const coords = parseCoordinates(mission.coordinates);
                if (!coords) return;

                const color = STATUS_COLORS[mission.status] || '#9ca3af';

                L.circleMarker(coords, {
                    radius: 7,
                    color,
                    fillColor: color,
                    fillOpacity: 0.75,
                    weight: 2,
                })
                    .addTo(map)
                    .bindPopup(
                        '<strong>' + escapeHtml(mission.title) + '</strong><br>' +
                        escapeHtml(mission.location) + '<br>' +
                        'Status: ' + escapeHtml(mission.status) + '<br>' +
                        '<a href="/missions/' + encodeURIComponent(mission.id) + '">Szczegóły akcji →</a>'
                    );
            });
        })
        .catch(() => {
            // Cicha awaria – mapa bazowa i tak pozostaje użyteczna nawet bez markerów akcji.
        });
}

/**
 * Parsuje wartość zapisaną w missions.coordinates (format "lat,lng",
 * np. "49.2298,19.9822") na parę liczb [lat, lng] zrozumiałą dla Leaflet.
 * Zwraca null, jeśli format jest nieprawidłowy lub pole jest puste.
 */
function parseCoordinates(raw) {
    if (!raw || typeof raw !== 'string') return null;

    const parts = raw.split(',').map(p => parseFloat(p.trim()));
    if (parts.length !== 2 || parts.some(Number.isNaN)) return null;

    return parts;
}

/**
 * Minimalne escapowanie HTML dla treści wstawianych do popupów Leaflet
 * przez innerHTML (ochrona przed XSS – tytuły/lokalizacje akcji pochodzą
 * z bazy danych i mogły zostać wprowadzone przez użytkownika).
 */
function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value === null || value === undefined ? '' : String(value);
    return div.innerHTML;
}

// 8. Confirm delete helper (called inline via onsubmit)
function confirmDelete(msg) {
  return confirm(msg || 'Czy na pewno chcesz usunąć ten element?');
}

// ============================================================
// 9. Form validation (wzorzec z zajęć)
// ============================================================

function isEmail(email) {
  return /\S+@\S+\.\S+/.test(email);
}

function arePasswordsSame(password, confirmedPassword) {
  return password === confirmedPassword;
}

function isPasswordStrong(password) {
  return password.length >= 8;
}

function markValidation(element, condition) {
  if (!condition) {
    element.classList.add('no-valid');
  } else {
    element.classList.remove('no-valid');
  }
}

// --- Formularz logowania ---
const loginForm = document.getElementById('loginForm');
if (loginForm) {
  const emailInput = loginForm.querySelector('input[name="email"]');

  function validateLoginEmail() {
    setTimeout(() => {
      markValidation(emailInput, isEmail(emailInput.value));
    }, 1000);
  }

  emailInput.addEventListener('keyup', validateLoginEmail);

  loginForm.addEventListener('submit', e => {
    const emailOk    = isEmail(emailInput.value);
    const passwordEl = loginForm.querySelector('input[name="password"]');
    const passwordOk = passwordEl.value.length > 0;

    markValidation(emailInput, emailOk);
    markValidation(passwordEl, passwordOk);

    if (!emailOk || !passwordOk) {
      e.preventDefault();
    }
  });
}

// --- Formularz rejestracji ---
const registerForm = document.getElementById('registerForm');
if (registerForm) {
  const emailInput    = registerForm.querySelector('input[name="email"]');
  const passwordInput = registerForm.querySelector('input[name="password"]');
  const password2Input = registerForm.querySelector('input[name="password2"]');

  function validateRegisterEmail() {
    setTimeout(() => {
      markValidation(emailInput, isEmail(emailInput.value));
    }, 1000);
  }

  function validateRegisterPassword() {
    setTimeout(() => {
      markValidation(passwordInput, isPasswordStrong(passwordInput.value));
    }, 1000);
  }

  function validateRegisterPassword2() {
    setTimeout(() => {
      const condition = arePasswordsSame(passwordInput.value, password2Input.value);
      markValidation(password2Input, condition);
    }, 1000);
  }

  emailInput.addEventListener('keyup', validateRegisterEmail);
  passwordInput.addEventListener('keyup', validateRegisterPassword);
  password2Input.addEventListener('keyup', validateRegisterPassword2);

  registerForm.addEventListener('submit', e => {
    const emailOk    = isEmail(emailInput.value);
    const passwordOk = isPasswordStrong(passwordInput.value);
    const sameOk     = arePasswordsSame(passwordInput.value, password2Input.value);
    const usernameEl = registerForm.querySelector('input[name="username"]');
    const usernameOk = usernameEl.value.trim().length > 0;

    markValidation(emailInput,    emailOk);
    markValidation(passwordInput, passwordOk);
    markValidation(password2Input, sameOk);
    markValidation(usernameEl,    usernameOk);

    if (!emailOk || !passwordOk || !sameOk || !usernameOk) {
      e.preventDefault();
    }
  });
}

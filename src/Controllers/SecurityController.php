<?php

class SecurityController extends AppController
{
    private UserRepository $userRepository;

    public function __construct()
    {
        // D1: UserRepository zarządzany jako singleton (jedna spójna instancja)
        $this->userRepository = UserRepository::getInstance();
    }

    /**
     * Wspólny helper do renderowania formularza auth (login/register) wraz
     * z komunikatem błędu i świeżym tokenem CSRF. Eliminuje powielanie bloku
     * render([...'error'..., 'csrfToken'...]) w wielu miejscach kontrolera.
     *
     * @param string   $view     nazwa widoku (np. 'auth/login', 'auth/register')
     * @param string   $message  treść komunikatu błędu
     * @param int|null $httpCode opcjonalny kod HTTP do ustawienia (np. 403, 429)
     */
    private function renderAuthError(string $view, string $message, ?int $httpCode = null): void
    {
        if ($httpCode !== null) {
            http_response_code($httpCode);
        }

        $this->render($view, [
            'error'     => $message,
            'csrfToken' => SessionService::generateCsrfToken(),
        ]);
    }

    public function login(): void
    {
        if (SessionService::isLoggedIn()) {
            $this->redirect('/dashboard');
        }

        if (!$this->isPost()) {
            $this->render('auth/login', [
                'error'     => SessionService::getFlash('error'),
                'success'   => SessionService::getFlash('success'),
                'csrfToken' => SessionService::generateCsrfToken(),
            ]);
            return;
        }

        // B2: Weryfikacja CSRF tokena
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!SessionService::validateCsrfToken($csrfToken)) {
            $this->renderAuthError('auth/login', 'Nieprawidłowe żądanie. Spróbuj ponownie.', 403);
            return;
        }

        $email    = $this->getPost('email');
        $password = $this->getPost('password');

        // A4: Limit prób logowania / blokada czasowa
        $lockoutRemaining = SessionService::getLoginLockoutRemaining($email);
        if ($lockoutRemaining > 0) {
            $minutes = (int)ceil($lockoutRemaining / 60);
            $this->renderAuthError(
                'auth/login',
                "Zbyt wiele nieudanych prób logowania. Spróbuj ponownie za ok. {$minutes} min.",
                429
            );
            return;
        }

        // D2: Limit długości inputów
        if (strlen($email) > 255 || strlen($password) > 255) {
            $this->renderAuthError('auth/login', 'Nieprawidłowe dane logowania.');
            return;
        }

        if (empty($email) || empty($password)) {
            $this->renderAuthError('auth/login', 'Uzupełnij wszystkie pola.');
            return;
        }

        // C1: Walidacja formatu email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->renderAuthError('auth/login', 'Nieprawidłowy format adresu email.');
            return;
        }

        $user = $this->userRepository->getUserByEmail($email);

        // B1: Generyczny komunikat — nie zdradzamy czy email istnieje
        if (!$user || !password_verify($password, $user->getPassword())) {
            // E5: Logowanie nieudanych prób (bez hasła)
            error_log("Failed login attempt for email: {$email} from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            // A4: Zlicz nieudaną próbę – po przekroczeniu progu uruchomi się blokada
            SessionService::registerFailedLogin($email);
            $this->renderAuthError('auth/login', 'Nieprawidłowy email lub hasło.');
            return;
        }

        if (!$user->isActive()) {
            $this->renderAuthError('auth/login', 'Konto jest nieaktywne.');
            return;
        }

        // A4: Poprawne logowanie – wyczyść licznik nieudanych prób
        SessionService::clearLoginAttempts($email);

        // B3: Regeneracja ID sesji po logowaniu (ochrona przed session fixation)
        session_regenerate_id(true);

        SessionService::set('user_id',       $user->getId());
        SessionService::set('user_email',    $user->getEmail());
        SessionService::set('user_username', $user->getUsername());
        SessionService::set('user_role',     $user->getRoleName());
        SessionService::set('is_logged_in',  true);

        // Rotacja tokena CSRF po zalogowaniu
        SessionService::rotateCsrfToken();

        $this->redirect('/dashboard');
    }

    public function register(): void
    {
        if (SessionService::isLoggedIn()) {
            $this->redirect('/dashboard');
        }

        if (!$this->isPost()) {
            $this->render('auth/register', [
                'csrfToken' => SessionService::generateCsrfToken(),
            ]);
            return;
        }

        // C2: Weryfikacja CSRF tokena
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!SessionService::validateCsrfToken($csrfToken)) {
            $this->renderAuthError('auth/register', 'Nieprawidłowe żądanie. Spróbuj ponownie.', 403);
            return;
        }

        $email     = $this->getPost('email');
        $password  = $this->getPost('password');
        $password2 = $this->getPost('password2');
        $username  = $this->getPost('username');

        // D2: Limity długości
        if (strlen($email) > 255 || strlen($username) > 50 || strlen($password) > 255) {
            $this->renderAuthError('auth/register', 'Przekroczono maksymalną długość pól.');
            return;
        }

        if (empty($email) || empty($password) || empty($username)) {
            $this->renderAuthError('auth/register', 'Uzupełnij wszystkie pola.');
            return;
        }

        // C1: Walidacja formatu email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->renderAuthError('auth/register', 'Nieprawidłowy format adresu email.');
            return;
        }

        // B4: Walidacja złożoności hasła
        if (strlen($password) < 8) {
            $this->renderAuthError('auth/register', 'Hasło musi mieć minimum 8 znaków.');
            return;
        }

        if ($password !== $password2) {
            $this->renderAuthError('auth/register', 'Hasła nie są identyczne.');
            return;
        }

        $existing = $this->userRepository->getUserByEmail($email);

        // C4: Nie zdradzamy czy email już istnieje
        if ($existing) {
            $this->renderAuthError(
                'auth/register',
                'Jeśli podany adres jest prawidłowy, otrzymasz wiadomość z potwierdzeniem.'
            );
            return;
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $this->userRepository->createUser($email, $hashedPassword, $username);

        SessionService::rotateCsrfToken();
        SessionService::flash('success', 'Konto zostało utworzone. Możesz się zalogować.');
        $this->redirect('/login');
    }

    public function logout(): void
    {
        SessionService::destroy();
        $this->redirect('/login');
    }
}

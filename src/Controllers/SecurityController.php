<?php

class SecurityController extends AppController
{
    private UserRepository $userRepository;

    public function __construct()
    {
        // D1: UserRepository zarządzany jako singleton (jedna spójna instancja)
        $this->userRepository = UserRepository::getInstance();
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
            http_response_code(403);
            $this->render('auth/login', [
                'error'     => 'Nieprawidłowe żądanie. Spróbuj ponownie.',
                'csrfToken' => SessionService::generateCsrfToken(),
            ]);
            return;
        }

        $email    = $this->getPost('email');
        $password = $this->getPost('password');

        // A4: Limit prób logowania / blokada czasowa
        $lockoutRemaining = SessionService::getLoginLockoutRemaining($email);
        if ($lockoutRemaining > 0) {
            $minutes = (int)ceil($lockoutRemaining / 60);
            http_response_code(429);
            $this->render('auth/login', [
                'error'     => "Zbyt wiele nieudanych prób logowania. Spróbuj ponownie za ok. {$minutes} min.",
                'csrfToken' => SessionService::generateCsrfToken(),
            ]);
            return;
        }

        // D2: Limit długości inputów
        if (strlen($email) > 255 || strlen($password) > 255) {
            $this->render('auth/login', [
                'error'     => 'Nieprawidłowe dane logowania.',
                'csrfToken' => SessionService::generateCsrfToken(),
            ]);
            return;
        }

        if (empty($email) || empty($password)) {
            $this->render('auth/login', [
                'error'     => 'Uzupełnij wszystkie pola.',
                'csrfToken' => SessionService::generateCsrfToken(),
            ]);
            return;
        }

        // C1: Walidacja formatu email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->render('auth/login', [
                'error'     => 'Nieprawidłowy format adresu email.',
                'csrfToken' => SessionService::generateCsrfToken(),
            ]);
            return;
        }

        $user = $this->userRepository->getUserByEmail($email);

        // B1: Generyczny komunikat — nie zdradzamy czy email istnieje
        if (!$user || !password_verify($password, $user->getPassword())) {
            // E5: Logowanie nieudanych prób (bez hasła)
            error_log("Failed login attempt for email: {$email} from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            // A4: Zlicz nieudaną próbę – po przekroczeniu progu uruchomi się blokada
            SessionService::registerFailedLogin($email);
            $this->render('auth/login', [
                'error'     => 'Nieprawidłowy email lub hasło.',
                'csrfToken' => SessionService::generateCsrfToken(),
            ]);
            return;
        }

        if (!$user->isActive()) {
            $this->render('auth/login', [
                'error'     => 'Konto jest nieaktywne.',
                'csrfToken' => SessionService::generateCsrfToken(),
            ]);
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
            http_response_code(403);
            $this->render('auth/register', [
                'error'     => 'Nieprawidłowe żądanie. Spróbuj ponownie.',
                'csrfToken' => SessionService::generateCsrfToken(),
            ]);
            return;
        }

        $email     = $this->getPost('email');
        $password  = $this->getPost('password');
        $password2 = $this->getPost('password2');
        $username  = $this->getPost('username');

        // D2: Limity długości
        if (strlen($email) > 255 || strlen($username) > 50 || strlen($password) > 255) {
            $this->render('auth/register', [
                'error'     => 'Przekroczono maksymalną długość pól.',
                'csrfToken' => SessionService::generateCsrfToken(),
            ]);
            return;
        }

        if (empty($email) || empty($password) || empty($username)) {
            $this->render('auth/register', [
                'error'     => 'Uzupełnij wszystkie pola.',
                'csrfToken' => SessionService::generateCsrfToken(),
            ]);
            return;
        }

        // C1: Walidacja formatu email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->render('auth/register', [
                'error'     => 'Nieprawidłowy format adresu email.',
                'csrfToken' => SessionService::generateCsrfToken(),
            ]);
            return;
        }

        // B4: Walidacja złożoności hasła
        if (strlen($password) < 8) {
            $this->render('auth/register', [
                'error'     => 'Hasło musi mieć minimum 8 znaków.',
                'csrfToken' => SessionService::generateCsrfToken(),
            ]);
            return;
        }

        if ($password !== $password2) {
            $this->render('auth/register', [
                'error'     => 'Hasła nie są identyczne.',
                'csrfToken' => SessionService::generateCsrfToken(),
            ]);
            return;
        }

        $existing = $this->userRepository->getUserByEmail($email);

        // C4: Nie zdradzamy czy email już istnieje
        if ($existing) {
            $this->render('auth/register', [
                'error'     => 'Jeśli podany adres jest prawidłowy, otrzymasz wiadomość z potwierdzeniem.',
                'csrfToken' => SessionService::generateCsrfToken(),
            ]);
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

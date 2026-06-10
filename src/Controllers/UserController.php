<?php

class UserController extends AppController
{
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->userRepo = UserRepository::getInstance(); // D1: singleton
    }

    public function index(): void
    {
        SessionService::requireCoordinator();

        $users = $this->userRepo->getAllUsers();
        $roles = $this->userRepo->getRoles();

        $this->render('users/index', [
            'users'   => $users,
            'roles'   => $roles,
            'success' => SessionService::getFlash('success'),
            'error'   => SessionService::getFlash('error'),
        ]);
    }

    public function edit(): void
    {
        SessionService::requireCoordinator();

        $id   = (int)($_GET['id'] ?? 0);
        $user = $this->userRepo->getUserById($id);

        if (!$user) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }

        $profile = $this->userRepo->getProfileByUserId($id);
        $roles   = $this->userRepo->getRoles();

        $this->render('users/edit', [
            'user'    => $user,
            'profile' => $profile,
            'roles'   => $roles,
        ]);
    }

    public function update(): void
    {
        SessionService::requireCoordinator();

        $id      = (int)($_GET['id'] ?? 0);
        $section = $this->getPost('section'); // 'account' lub 'profile'

        if ($section === 'account') {
            $roleId = (int)$this->getPost('role_id');
            if ($roleId > 0) {
                $this->userRepo->updateUser($id, [
                    'role_id'   => $roleId,
                    'is_active' => $this->getPost('is_active') === '1' ? 1 : 0,
                ]);
            }
        } else {
            $this->userRepo->updateProfile($id, [
                'first_name' => $this->getPost('first_name'),
                'last_name'  => $this->getPost('last_name'),
                'rank'       => $this->getPost('rank')  ?: null,
                'phone'      => $this->getPost('phone') ?: null,
                'bio'        => $this->getPost('bio')   ?: null,
            ]);
        }

        SessionService::flash('success', 'Dane użytkownika zostały zaktualizowane.');
        $this->redirect('/users/' . $id . '/edit');
    }

    public function delete(): void
    {
        SessionService::requireCoordinator();

        $id      = (int)($_GET['id'] ?? 0);
        $current = SessionService::get('user_id');

        if ($id === $current) {
            SessionService::flash('error', 'Nie możesz usunąć własnego konta.');
            $this->redirect('/users');
        }

        $this->userRepo->deleteUser($id);
        SessionService::flash('success', 'Użytkownik został dezaktywowany.');
        $this->redirect('/users');
    }

    public function profile(): void
    {
        SessionService::requireLogin();

        $userId  = (int)SessionService::get('user_id');
        $user    = $this->userRepo->getUserById($userId);
        $profile = $this->userRepo->getProfileByUserId($userId);

        // Zabezpieczenie: user nie powinien być null dla zalogowanego,
        // ale jeśli sesja jest stale (np. konto usunięte) – wyloguj
        if (!$user) {
            SessionService::destroy();
            $this->redirect('/login');
            return;
        }

        $this->render('users/profile', [
            'user'    => $user,
            'profile' => $profile,
            'success' => SessionService::getFlash('success'),
            'error'   => SessionService::getFlash('error'),
        ]);
    }

    public function updateProfile(): void
    {
        SessionService::requireLogin();

        $userId = (int)SessionService::get('user_id');

        $this->userRepo->updateProfile($userId, [
            'first_name' => $this->getPost('first_name'),
            'last_name'  => $this->getPost('last_name'),
            'rank'       => $this->getPost('rank')  ?: null,
            'phone'      => $this->getPost('phone') ?: null,
            'bio'        => $this->getPost('bio')   ?: null,
        ]);

        SessionService::flash('success', 'Profil został zaktualizowany.');
        $this->redirect('/profile');
    }

    public function apiList(): void
    {
        SessionService::requireLogin();

        $users = $this->userRepo->getAllUsers();
        $data  = array_map(fn(User $u) => [
            'id'       => $u->getId(),
            'username' => $u->getUsername(),
            'email'    => $u->getEmail(),
            'role'     => $u->getRoleName(),
        ], $users);

        $this->json($data);
    }
}

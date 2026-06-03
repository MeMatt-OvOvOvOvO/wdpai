<?php

class UserController extends AppController
{
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
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

        $id = (int)($_GET['id'] ?? 0);

        $this->userRepo->updateUser($id, [
            'role_id'   => (int)$this->getPost('role_id'),
            'is_active' => $this->getPost('is_active') === '1',
        ]);

        $this->userRepo->updateProfile($id, [
            'first_name' => $this->getPost('first_name'),
            'last_name'  => $this->getPost('last_name'),
            'rank'       => $this->getPost('rank')  ?: null,
            'phone'      => $this->getPost('phone') ?: null,
            'bio'        => $this->getPost('bio')   ?: null,
        ]);

        SessionService::flash('success', 'Dane użytkownika zostały zaktualizowane.');
        $this->redirect('/users');
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

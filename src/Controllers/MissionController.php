<?php

class MissionController extends AppController
{
    private MissionRepository $missionRepo;
    private UserRepository    $userRepo;

    public function __construct()
    {
        $this->missionRepo = new MissionRepository();
        $this->userRepo    = UserRepository::getInstance(); // D1: singleton
    }

    public function index(): void
    {
        SessionService::requireLogin();

        $userId   = (int)SessionService::get('user_id');
        $missions = SessionService::isCoordinator()
            ? $this->missionRepo->getAllMissions()
            : $this->missionRepo->getMissionsForRescuer($userId);

        $incidentTypes = $this->missionRepo->getIncidentTypes();

        $this->render('missions/index', [
            'missions'      => $missions,
            'incidentTypes' => $incidentTypes,
            'success'       => SessionService::getFlash('success'),
            'error'         => SessionService::getFlash('error'),
        ]);
    }

    public function create(): void
    {
        SessionService::requireCoordinator();

        $incidentTypes = $this->missionRepo->getIncidentTypes();
        $rescuers      = $this->userRepo->getAllUsers();

        $this->render('missions/create', [
            'incidentTypes' => $incidentTypes,
            'rescuers'      => $rescuers,
        ]);
    }

    public function store(): void
    {
        SessionService::requireCoordinator();

        $title          = $this->getPost('title');
        $location       = $this->getPost('location');
        $coordinates    = $this->getPost('coordinates');
        $incidentTypeId = $this->getPost('incident_type_id');
        $description    = $this->getPost('description');
        $startTime      = $this->getPost('start_time');

        if (empty($title) || empty($location)) {
            SessionService::flash('error', 'Tytuł i lokalizacja są wymagane.');
            $this->redirect('/missions/new');
        }

        $missionId = $this->missionRepo->createMission([
            'title'            => $title,
            'location'         => $location,
            'coordinates'      => $coordinates ?: null,
            'incident_type_id' => $incidentTypeId ?: null,
            'description'      => $description ?: null,
            'start_time'       => $startTime ?: date('Y-m-d H:i:sP'),
            'created_by'       => SessionService::get('user_id'),
        ]);

        SessionService::flash('success', 'Akcja ratunkowa została utworzona.');
        $this->redirect('/missions/' . $missionId);
    }

    public function show(): void
    {
        SessionService::requireLogin();

        $id      = (int)($this->getQuery('id') ?: $_GET['id'] ?? 0);
        $mission = $this->missionRepo->getMissionById($id);

        if (!$mission) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }

        // Ratownik może widzieć tylko akcje, do których jest przypisany
        if (!SessionService::isCoordinator()) {
            $userId   = (int)SessionService::get('user_id');
            $assigned = $this->missionRepo->getMissionRescuers($id);
            $isAssigned = array_filter($assigned, fn($r) => (int)$r['user_id'] === $userId);
            if (empty($isAssigned)) {
                http_response_code(403);
                $this->render('errors/403');
                return;
            }
        }

        $rescuers  = $this->missionRepo->getMissionRescuers($id);
        $equipment = $this->equipmentRepo()->getEquipmentForMission($id);
        $duration  = $this->missionRepo->getMissionDuration($id);
        $allUsers  = $this->userRepo->getAllUsers();
        $allEquip  = $this->equipmentRepo()->getEquipmentByStatus('ready');

        $this->render('missions/show', [
            'mission'   => $mission,
            'rescuers'  => $rescuers,
            'equipment' => $equipment,
            'duration'  => $duration,
            'allUsers'  => $allUsers,
            'allEquip'  => $allEquip,
            'success'   => SessionService::getFlash('success'),
            'error'     => SessionService::getFlash('error'),
        ]);
    }

    public function edit(): void
    {
        SessionService::requireCoordinator();

        $id      = (int)($_GET['id'] ?? 0);
        $mission = $this->missionRepo->getMissionById($id);

        if (!$mission) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }

        $incidentTypes = $this->missionRepo->getIncidentTypes();
        $this->render('missions/edit', [
            'mission'       => $mission,
            'incidentTypes' => $incidentTypes,
        ]);
    }

    public function update(): void
    {
        SessionService::requireCoordinator();

        $id = (int)($_GET['id'] ?? 0);

        $this->missionRepo->updateMission($id, [
            'title'            => $this->getPost('title'),
            'location'         => $this->getPost('location'),
            'coordinates'      => $this->getPost('coordinates') ?: null,
            'incident_type_id' => $this->getPost('incident_type_id') ?: null,
            'status'           => $this->getPost('status'),
            'description'      => $this->getPost('description') ?: null,
            'end_time'         => $this->getPost('end_time') ?: null,
        ]);

        SessionService::flash('success', 'Akcja została zaktualizowana.');
        $this->redirect('/missions/' . $id);
    }

    public function delete(): void
    {
        SessionService::requireCoordinator();

        $id = (int)($_GET['id'] ?? 0);
        $this->missionRepo->deleteMission($id);

        SessionService::flash('success', 'Akcja została usunięta.');
        $this->redirect('/missions');
    }

    public function addRescuer(): void
    {
        SessionService::requireCoordinator();

        $missionId = (int)($_GET['id'] ?? 0);
        $userId    = (int)$this->getPost('user_id');
        $role      = $this->getPost('role') ?: 'rescuer';

        if (!$userId) {
            SessionService::flash('error', 'Wybierz ratownika.');
            $this->redirect('/missions/' . $missionId);
        }

        $this->missionRepo->addRescuerToMission($missionId, $userId, $role);
        SessionService::flash('success', 'Ratownik został przypisany do akcji.');
        $this->redirect('/missions/' . $missionId);
    }

    public function removeRescuer(): void
    {
        SessionService::requireCoordinator();

        $missionId = (int)($_GET['id'] ?? 0);
        $userId    = (int)$this->getPost('user_id');

        $this->missionRepo->removeRescuerFromMission($missionId, $userId);
        SessionService::flash('success', 'Ratownik został usunięty z akcji.');
        $this->redirect('/missions/' . $missionId);
    }

    public function apiList(): void
    {
        SessionService::requireLogin();

        $missions = $this->missionRepo->getAllMissions();
        $data = array_map(fn(Mission $m) => [
            'id'               => $m->getId(),
            'title'            => $m->getTitle(),
            'location'         => $m->getLocation(),
            'coordinates'      => $m->getCoordinates(), // dla mapy Leaflet (lat,lng)
            'status'           => $m->getStatus(),
            'incident_type'    => $m->getIncidentTypeName(),
            'start_time'       => $m->getStartTime(),
        ], $missions);

        $this->json($data);
    }

    // Lazy getter dla EquipmentRepository (DI bez kontenera)
    private ?EquipmentRepository $_equipmentRepo = null;
    private function equipmentRepo(): EquipmentRepository
    {
        if ($this->_equipmentRepo === null) {
            $this->_equipmentRepo = new EquipmentRepository();
        }
        return $this->_equipmentRepo;
    }
}

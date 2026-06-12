<?php

class EquipmentController extends AppController
{
    private EquipmentRepository $equipmentRepo;
    private MissionRepository   $missionRepo;

    public function __construct()
    {
        $this->equipmentRepo = new EquipmentRepository();
        $this->missionRepo   = new MissionRepository();
    }

    public function index(): void
    {
        SessionService::requireLogin();

        $equipment      = $this->equipmentRepo->getAllEquipment();
        $equipmentTypes = $this->equipmentRepo->getEquipmentTypes();
        $stats          = $this->equipmentRepo->getStats();

        $this->render('equipment/index', [
            'equipment'      => $equipment,
            'equipmentTypes' => $equipmentTypes,
            'stats'          => $stats,
            'success'        => SessionService::getFlash('success'),
            'error'          => SessionService::getFlash('error'),
        ]);
    }

    public function create(): void
    {
        SessionService::requireCoordinator();

        $equipmentTypes = $this->equipmentRepo->getEquipmentTypes();
        $this->render('equipment/create', ['equipmentTypes' => $equipmentTypes]);
    }

    public function store(): void
    {
        SessionService::requireCoordinator();

        $name           = $this->getPost('name');
        $serialNumber   = $this->getPost('serial_number');
        $typeId         = $this->getPost('type_id');
        $status         = $this->getPost('status') ?: 'ready';
        $lastInspection = $this->getPost('last_inspection');
        $serviceLife    = $this->getPost('service_life_pct');
        $notes          = $this->getPost('notes');

        if (empty($name) || empty($serialNumber)) {
            SessionService::flash('error', 'Nazwa i numer seryjny są wymagane.');
            $this->redirect('/equipment/new');
        }

        $this->equipmentRepo->createEquipment([
            'name'             => $name,
            'serial_number'    => $serialNumber,
            'type_id'          => $typeId          ?: null,
            'status'           => $status,
            'last_inspection'  => $lastInspection  ?: null,
            'service_life_pct' => $serviceLife !== '' ? (int)$serviceLife : null,
            'notes'            => $notes           ?: null,
        ]);

        SessionService::flash('success', 'Sprzęt został zarejestrowany.');
        $this->redirect('/equipment');
    }

    public function show(): void
    {
        SessionService::requireLogin();

        $id        = (int)($_GET['id'] ?? 0);
        $equipment = $this->equipmentRepo->getEquipmentById($id);

        if (!$equipment) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }

        $this->render('equipment/show', ['equipment' => $equipment]);
    }

    public function edit(): void
    {
        SessionService::requireCoordinator();

        $id        = (int)($_GET['id'] ?? 0);
        $equipment = $this->equipmentRepo->getEquipmentById($id);

        if (!$equipment) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }

        $equipmentTypes = $this->equipmentRepo->getEquipmentTypes();
        $this->render('equipment/edit', [
            'equipment'      => $equipment,
            'equipmentTypes' => $equipmentTypes,
        ]);
    }

    public function update(): void
    {
        SessionService::requireCoordinator();

        $id = (int)($_GET['id'] ?? 0);

        $serviceLife = $this->getPost('service_life_pct');

        $this->equipmentRepo->updateEquipment($id, [
            'name'             => $this->getPost('name'),
            'serial_number'    => $this->getPost('serial_number'),
            'type_id'          => $this->getPost('type_id')          ?: null,
            'status'           => $this->getPost('status'),
            'last_inspection'  => $this->getPost('last_inspection')  ?: null,
            'service_life_pct' => $serviceLife !== '' ? (int)$serviceLife : null,
            'notes'            => $this->getPost('notes')            ?: null,
        ]);

        SessionService::flash('success', 'Dane sprzętu zostały zaktualizowane.');
        $this->redirect('/equipment/' . $id);
    }

    public function delete(): void
    {
        SessionService::requireCoordinator();

        $id = (int)($_GET['id'] ?? 0);
        $this->equipmentRepo->deleteEquipment($id);

        SessionService::flash('success', 'Sprzęt został usunięty.');
        $this->redirect('/equipment');
    }

    public function apiList(): void
    {
        SessionService::requireLogin();

        $equipment = $this->equipmentRepo->getAllEquipment();
        $data = array_map(fn(Equipment $e) => [
            'id'            => $e->getId(),
            'name'          => $e->getName(),
            'serial_number' => $e->getSerialNumber(),
            'type'          => $e->getTypeName(),
            'status'        => $e->getStatus(),
        ], $equipment);

        $this->json($data);
    }

    public function apiLoanEquipment(): void
    {
        SessionService::requireCoordinator();

        $missionId   = (int)($_GET['id'] ?? 0);
        $input       = json_decode(file_get_contents('php://input'), true);
        $equipmentId = (int)($input['equipment_id'] ?? 0);
        $quantity    = (int)($input['quantity']     ?? 1);

        if (!$missionId || !$equipmentId) {
            $this->jsonError('Nieprawidłowe dane.', 400);
        }

        $mission = $this->missionRepo->getMissionById($missionId);
        if (!$mission || in_array($mission->getStatus(), ['completed', 'cancelled'])) {
            $this->jsonError('Nie można dodać sprzętu do zakończonej lub anulowanej akcji.', 422);
        }

        $result = $this->equipmentRepo->loanEquipmentToMission($missionId, $equipmentId, $quantity);
        $this->json(['success' => $result]);
    }
}

<?php

class DashboardController extends AppController
{
    private MissionRepository   $missionRepo;
    private EquipmentRepository $equipmentRepo;
    private UserRepository      $userRepo;

    public function __construct()
    {
        $this->missionRepo   = new MissionRepository();
        $this->equipmentRepo = new EquipmentRepository();
        $this->userRepo      = UserRepository::getInstance(); // D1: singleton
    }

    public function index(): void
    {
        SessionService::requireLogin();

        $missionStats   = $this->missionRepo->getStats();
        $equipmentStats = $this->equipmentRepo->getStats();

        // Koordynator widzi wszystkie aktywne akcje, ratownik tylko swoje
        $userId = (int)SessionService::get('user_id');
        if (SessionService::isCoordinator()) {
            $activeMissions = $this->missionRepo->getActiveMissions();
        } else {
            $allMyMissions  = $this->missionRepo->getMissionsForRescuer($userId);
            $activeMissions = array_values(array_filter(
                $allMyMissions,
                fn($m) => in_array($m->getStatus(), ['active', 'open'])
            ));
        }

        // Aktywni ratownicy (przypisani do aktywnych akcji)
        $activeRescuersCount = 0;
        foreach ($activeMissions as $mission) {
            $rescuers = $this->missionRepo->getMissionRescuers($mission->getId());
            $activeRescuersCount += count($rescuers);
        }

        $this->render('dashboard/index', [
            'missionStats'        => $missionStats,
            'equipmentStats'      => $equipmentStats,
            'activeMissions'      => $activeMissions,
            'activeRescuersCount' => $activeRescuersCount,
        ]);
    }

    public function apiStats(): void
    {
        SessionService::requireLogin();

        $this->json([
            'missions'  => $this->missionRepo->getStats(),
            'equipment' => $this->equipmentRepo->getStats(),
        ]);
    }
}

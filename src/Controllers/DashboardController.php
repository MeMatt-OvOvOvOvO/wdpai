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
        $activeMissions = $this->missionRepo->getActiveMissions();

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

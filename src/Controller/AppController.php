<?php

namespace App\Controller;

use App\HelperFunctions\MachineCalculationFunctions;
use App\Repository\MachineRepository;
use App\HelperFunctions\ResponseFunctions as response;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class AppController extends AbstractController
{
    public function __construct(
        private MachineRepository $machineRepository,
        private MachineCalculationFunctions $machineFunctions
    ){}

    #[Route('/status', name: 'app_status', methods: ['GET'])]
    public function index(): JsonResponse {
        /* 
            вычисляем состояние приложения 
        */

        $allMachines = $this->machineRepository->getAllMachines();

        // [машина, заянятый cpu, занятая memory, список процессов]
        $machineResources = [];
        foreach ($allMachines as $machine){
            [$usedCpu, $usedMemory, $processes] = $this->machineFunctions->resourceCalculation($machine);

            $machineResources[] = [$machine, $usedCpu, $usedMemory, $processes];
        }

        // сортируем по возрастанию (по сумме ресурсов)
        usort($machineResources, function($a, $b){
            return $this->machineFunctions->loadRating($a[0], $a[1], $a[2]) <=>
                   $this->machineFunctions->loadRating($b[0], $b[1], $b[2]);
        });

        return response::printAppStatus($machineResources);
    }
}

<?php

namespace App\Service;

use App\HelperFunctions\MachineCalculationFunctions;
use App\Repository\MachineRepository;

class AppStatus {
    public function __construct(
        private MachineRepository $machineRepository,
        private MachineCalculationFunctions $machineFunctions
    ){}

    public function printStatus(){
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
            return $a[1] + $a[2] <=> $b[1] + $b[2];
        });

    }
}
<?php

namespace App\Service;

use App\Entity\Machine;
use App\HelperFunctions\MachineCalculationFunctions;
use App\Repository\MachineRepository;
use App\Repository\ProcessRepository;

class DeleteMachineFunction {
    public function __construct(
        private ProcessRepository $processRepository,
        private MachineRepository $machineRepository,
        private MachineCalculationFunctions $machineFunctions
    ){}

    public function checkDeleteMachine(Machine $deletingMachine): array {
        /* 
            проверяем возможность удаления машины, т е
            сможем ли раскидать процессы по другим машинам
        */

        $machineProcess = $this->processRepository->findMachineProcesses($deletingMachine);
        $allMachines = $this->machineRepository->getAllMachines();

        // для каждой машины хранится [used cpu, used memory, machine, [new processes]]
        $machineResources = [];

        foreach ($allMachines as $machine){
            if ($machine->getId() === $deletingMachine->getId()) continue;

            [$used_cpu, $used_memory] = $this->machineFunctions->resourceCalculation($machine);

            $machineResources[] = [$used_cpu, $used_memory, $machine, []];
        }

        // список проессов, для которых не нашлось новой машины
        $orphanedProcesses = [];

        $foundMachine = true;
        foreach ($machineProcess as $process){
            if ($foundMachine){
                // сортируем по сумме свободных ресурсов в порядке убывания
                usort($machineResources, function($a, $b){
                    return $this->machineFunctions->loadRating($a[2], $a[0], $a[1]) <=> 
                           $this->machineFunctions->loadRating($b[2], $b[0], $b[1]);
                });
            }

            $foundMachine = false;

            $pr_cpu = $process->getCpu();
            $pr_memory = $process->getMemory();

            // пытаемся найти машину для процесса
            foreach ($machineResources as $key => [$cpu, $memory, $machine, $processList]){
                if ($cpu + $pr_cpu <= $machine->getTotalCpu() &&
                    $memory + $pr_memory <= $machine->getTotalMemory()) {

                    $machineResources[$key] = [
                        $cpu + $pr_cpu,
                        $memory + $pr_memory,
                        $machine,
                        [...$processList, $process]
                    ];

                    $foundMachine = true;
                    break;
                }
            }

            // осиротевший процесс
            if (!$foundMachine) $orphanedProcesses[] = $process; 
        }

        // возвращаем список: [список осиротевших, список машин и их новые процессы]
        return [$orphanedProcesses, $machineResources];
    }

    public function deleteMachine(Machine $deletedMachine): array {
        /*
            удаляем машины, если это возмонжно,
            инчае возвращаем список процессов, для которых не смогли найти новые машины
        */

        [$orphanedProcesses, $machineResources] = $this->checkDeleteMachine($deletedMachine);

        if (!empty($orphanedProcesses)){
            return $orphanedProcesses;
        }

        foreach ($machineResources as [$cpu, $memory, $machine, $processList]){
            foreach ($processList as $process){
                $this->processRepository->move($process, $machine);
            }
        }

        // удаляем машину и сохраняем все изменения в бд
        $this->machineRepository->delete($deletedMachine);
        $this->processRepository->saveChanges();

        return [];
    }
}
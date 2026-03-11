<?php

namespace App\Service;

use App\Entity\Machine;
use App\Entity\Process;
use App\Repository\MachineRepository;
use App\Repository\processRepository;

class Rebalancing{
    public function __construct(
        private ProcessRepository $processRepository,
        private MachineRepository $machineRepository
    ){}

    public function loadRating(Machine $machine, int $usedCpu, int $usedMemory): float{
        return max(
            $usedMemory/$machine->getTotalMemory(),
            $usedCpu/$machine->getTotalCpu()
        );
    }

    public function resourceCalculation(Machine $machine): array {
        $processes = $this->processRepository->findMachineProcess($machine);

        $usedMemory = 0;
        $usedCpu = 0;

        foreach ($processes as $process){
            $usedMemory += $process->getMemory();
            $usedCpu += $process->getCpu();
        }

        return [$usedCpu, $usedMemory, $processes];
    }

    public function processSelection(Machine $sourceMachine, Machine $targetMachine): Process | null{
        [$usedCpu, $usedMemory, $processes] = $this->resourceCalculation($sourceMachine);
        [$usedTargetCpu, $usedTargetMemory] = $this->resourceCalculation($targetMachine);

        $sourceLoad = $this->loadRating($sourceMachine, $usedCpu, $usedMemory);
        $bestLoad = $sourceLoad;
        $bestProcess = null;

        usort($processes, function($a, $b){
            return $b->getCpu() + $b->getMemory() <=> $a->getCpu() + $a->getMemory();
        });

        // поиск самого тяжелого процесса, который не будет делать нагрузку на целевой машине >, чем на исходной
        foreach ($processes as $process){
            $processCpu = $process->getCpu();
            $processMemory = $process->getMemory();

            $newSourceLoad = $this->loadRating($sourceMachine, $usedCpu - $processCpu, $usedMemory - $processMemory);

            if ($bestLoad <= $newSourceLoad) continue;
            if ($usedTargetCpu + $processCpu > $targetMachine->getTotalCpu() ||
                $usedTargetMemory + $processMemory > $targetMachine->getTotalMemory()) continue;

            $targetLoadAfter = $this->loadRating(
                $targetMachine, 
                $usedTargetCpu + $processCpu, 
                $usedTargetMemory + $processMemory);

            if ($targetLoadAfter < $sourceLoad){
                $bestLoad = $newSourceLoad;
                $bestProcess = $process;
            } 
        }

        return $bestProcess;
    }

    public function rebalancing(): Machine | null {
        $machines = $this->machineRepository->getAllMachines();

        if (empty($machines)) {
            return null;
        }

        // хранит пары: [загрузка машины, машина]
        $machineLoads = [];
        foreach ($machines as $machine){
            [$usedCpu, $usedMemory] = $this->resourceCalculation($machine);
            $load = $this->loadRating($machine, $usedCpu, $usedMemory); 
            
            $machineLoads[] = [$load, $machine];
        }

        // сортируем
        usort($machineLoads, function($a, $b){
            return $a[0] <=> $b[0];
        });

        $mx = end($machineLoads)[1];
        
        // перебираем машины, начиная с самых незагруженных
        foreach ($machineLoads as $pair){
            $mn = $pair[1];

            if ($mx->getId() === $mn->getId()) break;

            $relocatableProcess = $this->processSelection($mx, $mn);
            if ($relocatableProcess !== null){
                $this->processRepository->moveProcess($relocatableProcess, $mn);
                return $mn;
            }
        }

        return null;
    }
}
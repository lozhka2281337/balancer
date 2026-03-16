<?php

namespace App\Service;

use App\Config\BalancerThreshold;
use App\Entity\Machine;
use App\Entity\Process;
use App\Repository\MachineRepository;
use App\Repository\ProcessRepository;
use App\HelperFunctions\MachineCalculationFunctions;

class Rebalancing{
    public function __construct(
        private ProcessRepository $processRepository,
        private MachineRepository $machineRepository,
        private MachineCalculationFunctions $machineCalculator
    ){}

    public function processSelection(Machine $sourceMachine, Machine $targetMachine): Process | null{
        [$usedCpu, $usedMemory, $processes] = $this->machineCalculator->resourceCalculation($sourceMachine);
        [$usedTargetCpu, $usedTargetMemory] = $this->machineCalculator->resourceCalculation($targetMachine);

        $sourceLoad = $this->machineCalculator->loadRating($sourceMachine, $usedCpu, $usedMemory);
        $bestLoad = $sourceLoad;
        $bestProcess = null;

        usort($processes, function(Process $a, Process $b){
            return $b->getCpu() + $b->getMemory() <=> $a->getCpu() + $a->getMemory();
        });

        // поиск самого тяжелого процесса, который не будет делать нагрузку на целевой машине >, чем на исходной
        foreach ($processes as $process){
            $processCpu = $process->getCpu();
            $processMemory = $process->getMemory();

            $newSourceLoad = $this->machineCalculator->loadRating($sourceMachine, $usedCpu - $processCpu, $usedMemory - $processMemory);

            if ($bestLoad <= $newSourceLoad) continue;
            if ($usedTargetCpu + $processCpu > $targetMachine->getTotalCpu() ||
                $usedTargetMemory + $processMemory > $targetMachine->getTotalMemory()) continue;

            $targetLoadAfter = $this->machineCalculator->loadRating(
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

    public function moveProcess($machineLoads, Machine $mx): Machine | null {    
        // перебираем машины, начиная с самых незагруженных
        foreach ($machineLoads as $pair){
            $mn = $pair[1];

            // если попалась одна и та же машина
            if ($mx->getId() === $mn->getId()) continue;

            $relocatableProcess = $this->processSelection($mx, $mn);
            if ($relocatableProcess !== null){
                $this->processRepository->move($relocatableProcess, $mn);
                $this->processRepository->saveChanges();
                return $mn;
            }
        }

        return null;
    }

    public function rebalance(): array {
        $machines = $this->machineRepository->getAllMachines();

        if (empty($machines)) {
            return [];
        }
        /*
            собираем массив, состоящий из пар: [исходная машина, целевая машина],
            где исходная машина - машина, из которой взяли процесс,
            а целевая машина - машина, на котороую поолжили новый процесс
        */
        $maxIterations = count($this->processRepository->findAll());
        $iterations = 0;

        $sourceTargetMachines = [];
        for (;;) {
            // ограничиваем количество итераций цикла
            if ($iterations++ >= $maxIterations) break;

            // хранит пары: [загрузка машины, машина]
            $machineLoads = [];
            foreach ($machines as $machine){
                [$usedCpu, $usedMemory] = $this->machineCalculator->resourceCalculation($machine);
                $load = $this->machineCalculator->loadRating($machine, $usedCpu, $usedMemory); 
                
                $machineLoads[] = [$load, $machine];
            }

            // сортируем по возрастанию нагрузки
            usort($machineLoads, function(
                $a, $b){
                return $a[0] <=> $b[0];
            });

            // машины с max/min нагрузкой и их нагрузки
            [$maxLoad, $mx] = end($machineLoads);
            [$minLoad, $mn] = $machineLoads[0];

            // если нагрузка равномерная - return
            $threshold = BalancerThreshold::SUITABLE_MACHINE_THRESHOLD->value();
            if ($minLoad / $maxLoad > $threshold){
                return $sourceTargetMachines;
            }

            $source = $mx;
            $target = $this->moveProcess($machineLoads, $source);

            // если не нашлось машины для снижения нагрузки - return 
            if ($target === null) return $sourceTargetMachines;

            $sourceTargetMachines[] = [$source, $target];
        }

        return $sourceTargetMachines;
    }
}
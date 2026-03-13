<?php

namespace App\HelperFunctions;

use App\Entity\Machine;
use App\Repository\ProcessRepository;

class MachineCalculationFunctions{
    public function __construct(
        private ProcessRepository $processRepository
    ){}

    public function loadRating(Machine $machine, int $usedCpu, int $usedMemory): float{
        return max(
            $usedMemory/$machine->getTotalMemory(),
            $usedCpu/$machine->getTotalCpu()
        );
    }

    public function resourceCalculation(Machine $machine): array {
        $processes = $this->processRepository->findMachineProcesses($machine);

        $usedMemory = 0;
        $usedCpu = 0;

        foreach ($processes as $process){
            $usedMemory += $process->getMemory();
            $usedCpu += $process->getCpu();
        }

        return [$usedCpu, $usedMemory, $processes];
    }
}
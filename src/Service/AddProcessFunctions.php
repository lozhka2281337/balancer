<?php

namespace App\Service;

use App\Entity\Machine;
use App\Entity\Process;
use App\Repository\MachineRepository;
use App\Repository\ProcessRepository;
use Doctrine\ORM\EntityManagerInterface;

class AddProcessFunctions {
    public function __construct(
        private ProcessRepository $processRepository,
        private MachineRepository $machineRepository
    ){}

    public function isMachineSuitable(Machine $machine, Process $process): bool {
        // функция проверяет, может ли процесс разместиться на ней

        $cpu = $process->getCpu();
        $memory = $process->getMemory();
        
        $machineProcesses = $this->processRepository->findMachineProcess($machine); //$this->em->getRepository(Process::class)->findBy(['machine' => $machine]);
        $usedMemory = 0;
        $usedCpu = 0;

        foreach ($machineProcesses as $pr){
            $usedCpu += $pr->getCpu();
            $usedMemory += $pr->getMemory();
        }

        if ($machine->getTotalCpu() - $usedCpu >= $cpu || 
            $machine->getTotalMemory() - $usedMemory >= $memory ){
                return true;
        }      
        
        return false;
    }

    public function SearchMachine(Process $process): Machine | null {
        // выбираем машины, на которых может поместиться новый процесс
        $machines = $this->machineRepository->findAll();

        foreach ($machines as $machine){        
            if ($this->isMachineSuitable($machine, $process))
                return $machine;
        }

        return null;
    }
}
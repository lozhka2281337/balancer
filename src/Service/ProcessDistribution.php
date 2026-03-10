<?php

namespace App\Service;

use App\Entity\Machine;
use App\Entity\Process;
use Doctrine\ORM\EntityManagerInterface;

class ProcessDistribution {
    public function __construct(
        private EntityManagerInterface $em
    ){}

    public function loadRating(Process $process, Machine $machine, int $used_memory, int $used_cpu): float{
        $load = max(
            ($used_memory + $process->getMemory())/$machine->getTotalMemory(),
            ($used_cpu + $process->getCpu())/$machine->getTotalCpu()
        );

        return $load;
    }

    public function filter(Process $process): array{
        $loads = [];

        $cpu = $process->getCpu();
        $memory = $process->getMemory();

        // выбираем машины, на которых может поместиться новый процесс
        $machines = $this->em->getRepository(Machine::class)->findAll();

        foreach ($machines as $key => $machine){        
            $machine_processes = $this->em->getRepository(Process::class)->findBy(['machine' => $machine]);
            $used_memory = 0;
            $used_cpu = 0;

            foreach ($machine_processes as $pr){
                $used_cpu += $pr->getCpu();
                $used_memory += $pr->getMemory();
            }

            if ($machine->getTotalCpu() - $used_cpu < $cpu || 
                $machine->getTotalMemory() - $used_memory < $memory 
                ){
                unset($machines[$key]);
            } else {
                array_push($loads, $this->loadRating($process, $machine, $used_memory, $used_cpu));
            }
        }

        return [$machines, $loads];
    }

    public function distribution(Process $process): null | Machine {
        [$machines, $loads] = $this->filter($process);

        if (empty($loads)){
            return null;
        }

        $machine_index = array_search(min($loads), $loads);
        return $machines[$machine_index];
    }
}
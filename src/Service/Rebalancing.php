<?php

namespace App\Service;

use App\Entity\Machine;
use App\Entity\Process;
use App\Repository\MachineRepository;
use Doctrine\ORM\EntityManagerInterface;

class Rebalancing{
    private function __construct(
        private MachineRepository $machineRepository
    ){}

    public function processSelection(Machine $machine){
        

    }
}
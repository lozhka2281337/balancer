<?php

namespace App\Repository;

use App\Entity\Machine;
use App\Entity\Process;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @extends ServiceEntityRepository<Process>
 */
class ProcessRepository extends ServiceEntityRepository
{
    public function __construct(
        private ManagerRegistry $registry
        )
    {
        parent::__construct($registry, Process::class);
    }

    public function saveChanges(){
        $this->getEntityManager()->flush();
    }

    public function store(Process $process): Process {
        $this->getEntityManager()->persist($process);
        $this->saveChanges();
        return $process;
    }

    public function remove(Process $process): void {
        $this->getEntityManager()->remove($process);
        $this->getEntityManager()->flush();
    }

    public function findMachineProcesses(Machine $machine): array {
        return $this->findBy(['machine' => $machine]);
    }

    public function move(Process $process, Machine $machine): void{
        $process->setMachine($machine);
        $this->getEntityManager()->persist($process);
    }
}

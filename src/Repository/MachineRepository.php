<?php

namespace App\Repository;

use App\Entity\Machine;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Machine>
 */
class MachineRepository extends ServiceEntityRepository
{
    public function __construct(
        private ManagerRegistry $registry,
    ){
        parent::__construct($registry, Machine::class);
    }

    public function saveChanges(){
        $this->getEntityManager()->flush();
    }

    public function store(Machine $machine): Machine {
        $this->getEntityManager()->persist($machine);
        $this->saveChanges();
        
        return $machine;
    }

    public function getAllMachines(): array {
        return $this->findAll(); 
    }

    public function delete(Machine $machine): void {
        $this->getEntityManager()->remove($machine);
    }
}

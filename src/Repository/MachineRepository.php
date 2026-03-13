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

    public function store(Machine $machine): Machine {
        $this->getEntityManager()->persist($machine);
        $this->getEntityManager()->flush();
        
        return $machine;
    }

    public function getAllMAchines(): array {
        return $this->findAll(); 
    }

    public function delete(Machine $machine): array {
        $this->getEntityManager()->remove($machine);
        $this->getEntityManager()->flush();

        return [];
    }
}

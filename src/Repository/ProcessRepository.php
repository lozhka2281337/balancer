<?php

namespace App\Repository;

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

    public function store(Process $process): Process {
        $this->getEntityManager()->persist($process);
        $this->getEntityManager()->flush();

        return $process;
    }
}

<?php

namespace App\Tests\Repository;

use App\Entity\Machine;
use App\Repository\MachineRepository;
use App\Tests\DatabaseTestCase;

class MachineRepositoryTest extends DatabaseTestCase
{
    private MachineRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = static::getContainer()->get(MachineRepository::class);
    }

    // store сохраняет машину и возвращает её с назначенным id
    public function testStorePersistsMachine(): void
    {
        $machine = new Machine();
        $machine->setTotalCpu(8);
        $machine->setTotalMemory(16);

        $stored = $this->repository->store($machine);

        $this->assertNotNull($stored->getId());
        $this->assertEquals(8, $stored->getTotalCpu());
        $this->assertEquals(16, $stored->getTotalMemory());
    }

    // getAllMachines возвращает все сохранённые машины
    public function testGetAllMachinesReturnsAllMachines(): void
    {
        $machine1 = new Machine();
        $machine1->setTotalCpu(4);
        $machine1->setTotalMemory(8);
        $this->repository->store($machine1);

        $machine2 = new Machine();
        $machine2->setTotalCpu(8);
        $machine2->setTotalMemory(16);
        $this->repository->store($machine2);

        $machines = $this->repository->getAllMachines();

        $this->assertCount(2, $machines);
    }

    // getAllMachines возвращает пустой массив при отсутствии машин
    public function testGetAllMachinesReturnsEmptyArrayWhenNoMachines(): void
    {
        $machines = $this->repository->getAllMachines();

        $this->assertIsArray($machines);
        $this->assertEmpty($machines);
    }

    // delete помечает машину для удаления и после saveChanges она исчезает
    public function testDeleteRemovesMachine(): void
    {
        $machine = new Machine();
        $machine->setTotalCpu(4);
        $machine->setTotalMemory(8);
        $this->repository->store($machine);

        $this->assertCount(1, $this->repository->getAllMachines());

        $this->repository->delete($machine);
        $this->repository->saveChanges();

        $this->assertCount(0, $this->repository->getAllMachines());
    }

    // saveChanges сохраняет изменения в базе данных
    public function testSaveChangesFlushesEntityManager(): void
    {
        $machine = new Machine();
        $machine->setTotalCpu(4);
        $machine->setTotalMemory(8);

        $this->entityManager->persist($machine);
        $this->repository->saveChanges();

        $this->assertNotNull($machine->getId());
    }

    // find находит машину по id
    public function testFindReturnsMachineById(): void
    {
        $machine = new Machine();
        $machine->setTotalCpu(4);
        $machine->setTotalMemory(8);
        $this->repository->store($machine);

        $found = $this->repository->find($machine->getId());

        $this->assertNotNull($found);
        $this->assertEquals($machine->getId(), $found->getId());
    }

    // find возвращает null для несуществующего id
    public function testFindReturnsNullForNonExistentId(): void
    {
        $found = $this->repository->find(9999);

        $this->assertNull($found);
    }
}

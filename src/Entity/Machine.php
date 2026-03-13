<?php

namespace App\Entity;

use App\Repository\MachineRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MachineRepository::class)]
class Machine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\Positive]
    #[ORM\Column]
    private ?int $totalMemory = null;

    #[Assert\Positive]
    #[ORM\Column]
    private ?int $totalCpu = null;

    /**
     * @var Collection<int, Process>
     */
    #[ORM\OneToMany(targetEntity: Process::class, mappedBy: 'machine')]
    private Collection $processes;

    public function __construct()
    {
        $this->processes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTotalMemory(): ?int
    {
        return $this->totalMemory;
    }

    public function setTotalMemory(int $totalMemory): static
    {
        $this->totalMemory = $totalMemory;

        return $this;
    }

    public function getTotalCpu(): ?int
    {
        return $this->totalCpu;
    }

    public function setTotalCpu(int $totalCpu): static
    {
        $this->totalCpu = $totalCpu;

        return $this;
    }

    /**
     * @return Collection<int, Process>
     */
    public function getProcesses(): Collection
    {
        return $this->processes;
    }

    public function addProcess(Process $process): static
    {
        if (!$this->processes->contains($process)) {
            $this->processes->add($process);
            $process->setmachine($this);
        }

        return $this;
    }

    public function removeProcess(Process $process): static
    {
        if ($this->processes->removeElement($process)) {
            if ($process->getmachine() === $this) {
                $process->setmachine(null);
            }
        }

        return $this;
    }
}

<?php

namespace App\Controller;

use App\Entity\Machine;
use App\Repository\MachineRepository;
use App\Validator\MachineValidator;
use App\HelperFunctions\ResponseFunctions as response;
use App\Service\DeleteMachineFunction;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class MachineController extends AbstractController
{
    public function __construct(
        private MachineValidator $machineValidator,
        private MachineRepository $machineRepository,
        private DeleteMachineFunction $deleteMachineFunction
    ){}

    #[Route('/add_machine', name: 'add_new_machine', methods: ['POST'])]
    public function add_machine(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ($data == null){
            return response::error('json невалидный', 422);
        }

        if (!isset($data['cpu'], $data['memory'])){
            return response::error('поля cpu и memory должны быть заполнены',  422);
        }

        // создаем машину
        $machine = new Machine;
        $machine->setTotalCpu((int)$data['cpu']);
        $machine->setTotalMemory((int)$data['memory']);

        $errors = $this->machineValidator->validate($machine);

        if (!empty($errors)){
            return response::errors($errors, 422);
        }

        // через репозиторий сохраняем в бд
        $machine = $this->machineRepository->store($machine);

        return $this->json([
            'memory' => $machine->getTotalMemory(),
            'cpu' => $machine->getTotalCpu()
        ], 201);
    }

    #[Route('/remove_machine', name: 'remove_machine', methods: ['POST'])]
    public function remove_machine(Request $request): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['id'])){
            return response::error('поле id должно быть заполнено', 422);
        }

        $id = $data['id'];
        if (!is_numeric($id)){
            return response::error('поле id должно быть числом', 422);
        }

        $machine = $this->machineRepository->find((int)$id);
        if ($machine == null){
            return response::error('машины с таким id нет', 422);
        }

        // получем список процессов, которым не нашлась новая машина
        $orphanedProcesses =  $this->deleteMachineFunction->deleteMachine($machine); 

        // елси нет осиротевших процессов - возваращаем 201
        if (empty($orphanedProcesses))
            return response::success('машина успешно удалена', 201);

        return response::errorDeleteMachine($orphanedProcesses, 422);
    }
}

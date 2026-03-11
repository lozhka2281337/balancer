<?php

namespace App\Controller;

use App\Entity\Process;
use App\Repository\ProcessRepository;
use App\Validator\ProcessValidator;
use App\HelperFunctions\ResponseFunctions as response;
use App\Service\AddProcessFunctions;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ProcessController extends AbstractController
{
    public function __construct(
        private ProcessValidator $processValidator,
        private ProcessRepository $processRepository,
        private AddProcessFunctions $APF
    ){}

    #[Route('/add_process', name: 'add_new_process', methods: ['POST'])]
    public function addProcess(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // проверка на валидность данных
        if ($data == null) return response::error('json невалидный', 422);
        if (!isset($data['cpu'], $data['memory'])) return response::error('поля cpu и memory должны быть заполнены', 422);
       
        // создаем процесс
        $process = new Process;
        $process->setCpu((int)$data['cpu']);
        $process->setMemory((int)$data['memory']);

        $errors = $this->processValidator->validate($process);
        if (!empty($errors)) 
            return response::errors($errors, 422);

        // ищем подходящую машину
        $targetMachine = $this->APF->SearchMachine($process);
        if ($targetMachine === null) 
            return response::error('не нашлось подходящей машины', 422);
        $process->setMachine($targetMachine);

        // через репозиторий сохраняем в бд
        $process = $this->processRepository->store($process);

        return $this->json([
            'memory' => $process->getMemory(),
            'cpu' => $process->getCpu()
        ]);
    }

    #[Route('/remove_process', name: 'remove_process', methods: ['POST'])]
    public function removeProcess(Request $request): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if ($data == null) return response::error('json невалидный', 422);        
        if (!isset($data['id'])){
            return response::error('поле id должно быть заполнено', 422);
        }

        $id = $data['id'];
        if (!is_numeric($id)){
            return response::error('поле id должно быть числом', 422);
        }

        $process = $this->processRepository->find((int)$id);
        if ($process == null){
            return response::error('процесса с таким id нет');
        }

        $this->processRepository->remove($process);

        return response::success('процесс успешно удален');
    }
}

<?php

namespace App\Validator;

use App\Entity\Machine;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MachineValidator{
    public function __construct(
        private ValidatorInterface $validator
    ){}
    

    public function validate(Machine $machine): array {
        $errors = $this->validator->validate($machine);

        if (count($errors) == 0){
            return [];
        }

        $messages = [];
        foreach ($errors as $error){
            $messages[$error->getPropertyPath()][] = $error->getMessage();
        }

        return $messages;
    }
}
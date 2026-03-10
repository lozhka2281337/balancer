<?php

namespace App\Validator;

use App\Entity\Process;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProcessValidator{
    public function __construct(
        private ValidatorInterface $validator
    ){}
    

    public function validate(Process $process): array {
        $errors = $this->validator->validate($process);

        if (count($errors) > 0){
            $messages = [];

            foreach ($errors as $error){
                $messages[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $messages;
        }

        return [];
    }
}
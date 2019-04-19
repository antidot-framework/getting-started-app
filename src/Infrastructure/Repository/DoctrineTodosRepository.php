<?php
// src/Infrastructure/Repository/DoctrineTodosRepository.php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Model\Todo;
use App\Domain\TodosRepository;
use Doctrine\ORM\EntityRepository;

class DoctrineTodosRepository extends EntityRepository implements TodosRepository
{
    public function getAll(): array
    {
        return $this->findAll();
    }

    public function add(string $description): void
    {
        $todo = new Todo(null, $description);
        $this->_em->persist($todo);
        $this->_em->flush();
    }
}

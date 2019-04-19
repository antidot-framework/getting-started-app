<?php
// src/Infrastructure/Repository/DoctrineTodosRepository.php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\TodosRepository;
use Doctrine\ORM\EntityRepository;

class DoctrineTodosRepository extends EntityRepository implements TodosRepository
{
    public function getAll(): array
    {
        return $this->findAll();
    }
}

<?php
// src/Infrastructure/Repository/DoctrineTodosRepository.php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Model\Todo;
use App\Domain\TodosRepository;
use Doctrine\ORM\EntityRepository;
use InvalidArgumentException;

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

    public function update(int $id, string $description): void
    {
        /** @var ?Todo $todo */
        $todo = $this->findOneBy(['id' => $id]);
        if (null === $todo) {
            throw new InvalidArgumentException('Invalid Todo id given.');
        }

        $todo->update($description);
        $this->_em->flush($todo);
    }

    public function remove(int $id): void
    {
        $todo = $this->findOneBy(['id' => $id]);
        if (null === $todo) {
            throw new InvalidArgumentException('Invalid Todo id given.');
        }

        $this->_em->remove($todo);
        $this->_em->flush();
    }
}

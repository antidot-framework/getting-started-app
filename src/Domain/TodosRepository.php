<?php
// src/Domain/TodosRepository.php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Model\Todo;

interface TodosRepository
{
    /**
     * @return array<Todo>
     */
    public function getAll(): array;

    public function add(string $description): void;

    public function update(int $id, string $description): void;

    public function remove(int $id): void;
}

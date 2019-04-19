<?php
// src/Domain/Model/Todo.php

declare(strict_types=1);

namespace App\Domain\Model;

class Todo
{
    private $id;
    private $description;

    public function __construct(?int $id, string $description)
    {
        $this->id = $id;
        $this->description = $description;
    }

    public function update(string $description): void
    {
        $this->description = $description;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function description(): string
    {
        return $this->description;
    }
}

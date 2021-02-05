<?php
// src/Application/Http/Handler/EditTodo.php

declare(strict_types=1);

namespace App\Application\Http\Handler;

use App\Domain\TodosRepository;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\RedirectResponse;

class EditTodo implements RequestHandlerInterface
{
    private TodosRepository $todosRepository;

    public function __construct(TodosRepository $todosRepository)
    {
        $this->todosRepository = $todosRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int)$request->getAttribute('id');
        $description = $request->getAttribute('description');
        $session = $request->getAttribute('session');

        try {
            $this->todosRepository->update($id, $description);
            $session->setFlash('success', 'Todo successfully updated.');
        } catch (InvalidArgumentException $e) {
            $session->setFlash('error', $e->getMessage());
        }

        return new RedirectResponse('/');
    }
}

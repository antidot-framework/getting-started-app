<?php
// src/Application/Http/Handler/AddTodo.php

declare(strict_types=1);

namespace App\Application\Http\Handler;

use App\Domain\TodosRepository;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\RedirectResponse;

use function sprintf;

class AddTodo implements RequestHandlerInterface
{
    private TodosRepository $todosRepository;

    public function __construct(TodosRepository $todosRepository)
    {
        $this->todosRepository = $todosRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $description = $request->getAttribute('description');
        $session = $request->getAttribute('session');

        try {
            $this->todosRepository->add($description);
            $session->setFlash('success', 'Todo successfully added to list.');
        } catch (InvalidArgumentException $e) {
            $session->setFlash('error', sprintf('%s', $e->getMessage()));
        }

        return new RedirectResponse('/');
    }
}

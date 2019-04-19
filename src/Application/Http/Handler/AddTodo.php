<?php
// src/Application/Http/Handler/AddTodo.php

declare(strict_types=1);

namespace App\Application\Http\Handler;

use App\Domain\TodosRepository;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webmozart\Assert\Assert;
use Zend\Diactoros\Response\RedirectResponse;

class AddTodo implements RequestHandlerInterface
{
    /** @var TodosRepository */
    private $todosRepository;

    public function __construct(TodosRepository $todosRepository)
    {
        $this->todosRepository = $todosRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody() ?? [];
        try {
            $this->assertValidData($data);
            $this->todosRepository->add($data['description']);
        } catch (InvalidArgumentException $e) {
            $session = $request->getAttribute('session');
            $session->setFlash('error', $e->getMessage());
        }

        return new RedirectResponse('/');
    }

    private function assertValidData(array $data): void
    {
        Assert::keyExists($data, 'description');
        Assert::string($data['description']);
        Assert::maxLength($data['description'], 255);
        Assert::minLength($data['description'], 5);
    }
}

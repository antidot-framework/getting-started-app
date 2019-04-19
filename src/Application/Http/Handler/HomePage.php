<?php
// src/Application/Http/Handler/HomePage.php

declare(strict_types=1);

namespace App\Application\Http\Handler;

use Antidot\Render\TemplateRenderer;
use App\Domain\TodosRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;

class HomePage implements RequestHandlerInterface
{
    /** @var TodosRepository */
    private $todosRepository;
    /** @var TemplateRenderer */
    private $templateRenderer;

    public function __construct(TodosRepository $todosRepository, TemplateRenderer $templateRenderer)
    {
        $this->todosRepository = $todosRepository;
        $this->templateRenderer = $templateRenderer;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $todos = $this->todosRepository->getAll();

        return new HtmlResponse(
            $this->templateRenderer->render('todos/index', ['todos' => $todos])
        );
    }
}

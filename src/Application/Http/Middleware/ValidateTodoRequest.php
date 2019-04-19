<?php
// src/Application/Http/Middleware/ValidateTodoRequest.php

declare(strict_types=1);

namespace App\Application\Http\Middleware;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webmozart\Assert\Assert;
use Zend\Diactoros\Response\RedirectResponse;

class ValidateTodoRequest implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $data = $request->getParsedBody();

        try {
            Assert::isArray($data);
            Assert::keyExists($data, 'description');
            Assert::string($data['description']);
            Assert::maxLength($data['description'], 255);
            Assert::minLength($data['description'], 5);
        } catch (InvalidArgumentException $e) {
            $session = $request->getAttribute('session');
            $session->setFlash('error', $e->getMessage());
            return new RedirectResponse('/');
        }

        return $handler->handle($request->withAttribute('description', $data['description']));
    }
}

Getting Started
=================

This example app is made with the purpose of learning to use [Antidot Framework](https://antidotfw.io/) with [doctrine ORM](https://www.doctrine-project.org/), [Pug templating system](https://www.phug-lang.com/), and some PHP framework agnostic techniques

You can find full repository [here]() on Github, the repo is committed step by step to facilitate the follow of the tutorial

## Step 1: Create Project

Create a project using [composer](https://getcomposer.org/download/) package manager:

````bash
composer create-project antidot-fw/antidot-framework-starter:dev-master dev
mv dev/.* dev/* ./ && rmdir dev
php -S 127.0.0.1:8000 -t public
````

![Default homepage](/images/default-homepage.jpg)

To run Antidot Framework in dev mode, rename `config/services/dependencies.dev.yaml.dist` to `config/services/dependencies.dev.yaml`

````bash
mv config/services/dependencies.dev.yaml.dist config/services/dependencies.dev.yaml
````

Or use the cli tool.

```bash
bin/console config:development-mode
```

Open another console and check the built-in Cli tool

````bash
bin/console
````

![Default console tool](/images/default-console.jpg)

````bash
bin/console config:show:container
````

![Default container config](/images/default-container.jpg)

## Step-2: Application Requirements

We will make another simple Todo App with the purpose of showing the basic usage of all the components that form part of the Antidot Framework.

The basic functionality is a CRUD system to Create, Read, Update and Delete Todos.

To do this task we will need a database to store todos and at least a minimum template system to show UI to the user.

The UI should be composed by 4 pages or views:

* Home page/Todo list
* Add Todo
* Update Todo/Delete Todo
* Show Todo

With the given info we can start adding our first Request Handler.

````php
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
````

For this class, we need two things, first one the `TodosRepository` where we go to save our todos, and a `TemplateRenderer` to render todos on a web page.

### Step-2.1: Doctrine integration

First, we can install an integration package with Doctrine Dbal. One of the advantages of using a PSR-11 compatible dependency Injection container is the opportunity to use a lot of packages made by PHP community, for example we can install [DASPRiD/container-interop-doctrine](https://github.com/DASPRiD/container-interop-doctrine). 

For simplicity we have created a package on top of container-interop-doctrine adding console tool integration with Antidot framework, and a very useful `EntityRepositoryFactory`

````bash
composer require antidot-fw/doctrine
````

Then enable it in `config/services` folder.

````yaml
# config/services/dependencies.prod.yaml
services:
  ...
  App\Domain\TodosRepository:
    factory: [Antidot\Persistence\Doctrine\Container\EntityRepositoryFactory, 'App\Domain\Model\Todo']
    
parameters:
  ...
  doctrine:
    connection:
      orm_default:
        params:
          path: var/database.sqlite
````

We define a lot of stuff here, we can start to add mapping files in `config/doctrine` directory

````yaml
# config/doctrine/Todo.orm.yml
App\Domain\Model\Todo:
  type: entity
  table: todos
  repositoryClass: App\Infrastructure\Repository\DoctrineTodosRepository
  id:
    id:
      type: integer
      generator:
        strategy: AUTO
  fields:
    description:
      type: string
      length: 255
````

Create an entity inside our model.

````php
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

    public function id(): int
    {
        return $this->id;
    }

    public function description(): string
    {
        return $this->description;
    }
}

````

Now create sqlite file

````bash
touch var/database.sqlite
````

We can see all the added commands by typing `bin/console` in a terminal.

![Doctrine ORM Console](/images/doctrine-console.jpg)

Then create a database using doctrine console tool

````bash
bin/console orm:schema-tool:create
````

We need a repository interface to isolate our Domain and Application layers from the Persistence Layer

````php
<?php
// src/Domain/TodosRepository.php

declare(strict_types=1);

namespace App\Domain;

interface TodosRepository
{
    public function getAll(): array;
}
````

Now we do an implementation of `TodosRepository` using doctrine `EntityRepository`

````php
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
````

### Step-2.2: Php Pug Integration

Now we need a templating system to render our views. For this task, we can use Pug template renderer

````bash
composer require antidot-fw/phug-template-renderer
````

First of all, we need to configure application template renderer in `config/services` directory

````yaml
# config/services/dependencies.prod.yaml
services:
  ...
  Antidot\Render\TemplateRenderer:
    factory: [Antidot\Render\Phug\Container\PugRendererFactory]
````

When we create our request handler we define a template to render for views, now we need to create them

We can add a default layout for all our views

````pug
// templates/base.pug
html
    head
        title Antidot Todo List app
        link(rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css")
        link(href="//fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet")
    body
        main
            section(class="container")
                block content

    script(type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js")
    block scripts
````

And the home page view with an empty list of todos 

````pug
extends ../base.pug
// templates/todos/index.pug

block content
    h1 Todos app List

    ul(class="collection")
        each todo in todos
            li(class="collection-item")= todo.description()
                a(href="#")
                    i(class="material-icons right red-text") delete
                a(href="#")
                    i(class="material-icons right yellow-text") edit

````

![Step 2 Result](/images/step-2-result.jpg)

## Step-3: Adding Todos

In the second step, we create a model for Todos and a persistence layer using SQLite thanks to Doctrine ORM, in this step we are going to create a use case to add new Todos to the list.

**Use case**

* The user needs a form to send a description
* A Todo should have a description between 5 and 255 characters
* The user should be notified if given data does not fit the requirements
* The todo should be displayed on the list after success submission

### Step 3.1: Antidot Session Integration

We need to install Antidot session middleware package, made on top of [Aura Session](https://github.com/auraphp/Aura.Session), to use flash messages to show invalid submission.

We are going to use [webmozart/assert](https://github.com/webmozart/assert) library to validate description, and we need to draw some kind of form on the web page to allow the users to submit Todos.

````bash
composer require antidot-fw/session
composer require webmozart/assert
````

Add the middleware to the pipeline after to the `ExceptionLoggerMiddleware`

````php
<?php
// router/middleware.php

declare(strict_types=1);

use Antidot\Application\Http\Application;
use Antidot\Logger\Application\Http\Middleware\ExceptionLoggerMiddleware;
use Antidot\Session\Application\Http\Middleware\SessionMiddleware;
...

return static function (Application $app) : void {
    ...
    $app->pipe(ExceptionLoggerMiddleware::class);
    $app->pipe(SessionMiddleware::class);
    ...
};
````

### Step-3.2: Implementing use case

Now we are going to create a new Request Handler.

````php
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
````

First we get Post params from the parsed body of the request, then we assert that the given parameters are valid, if not, we throw an invalid argument exception, we catch it and we save an exception message in the session flash, then we redirect the user to the form, in this case, the home page. If parameters are valid this is passed to the repository who saves it in the storage system and then redirects the user to the homepage.

Add the route in `router/routes.php` file

````php
<?php
// router/routes.php

declare(strict_types=1);

use Antidot\Application\Http\Application;
use App\Application\Http\Handler\AddTodo;
use App\Application\Http\Handler\MarkdownHandler;
use Psr\Container\ContainerInterface;

return static function (Application $app, ContainerInterface $container) : void {
    $app->get('/', [MarkdownHandler::class], 'home');
    $app->post('/todos/add', [AddTodo::class], 'add_todo');
};
````

And add RequestHandler to dependencies config

````yaml
# config/services/dependencies.prod.yaml
services:
  ...
  App\Application\Http\Handler\AddTodo:
  ...
````

Update `TodosRepository` to allow adding new Todos

````php
<?php
// src/Domain/TodosRepository.php
declare(strict_types=1);

namespace App\Domain;

interface TodosRepository
{
    ...
    public function add(string $description): void;
}
````

Update `DoctrineTodosRepository` too

````php
<?php
// src/Infrastructure/Repository/DoctrineTodosRepository.php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Model\Todo;
...

class DoctrineTodosRepository extends EntityRepository implements TodosRepository
{
    ...
    public function add(string $description): void
    {
        $todo = new Todo(null, $description);
        $this->_em->persist($todo);
        $this->_em->flush();
    }
}
````

Here we create new `Todo` object with null id and the valid description, it is persisted by entity manager and then the entity manager is flushed to save changes in the persistence system.

The only thing we miss is the form to submit todos

````pug
extends ../base.pug
// templates/todos/index.pug

block content
    h1 Todos app List

    if error
        div(class="card row red lighten-3 white-text" style="padding: 20px 15px")
            dic(class="card-content")
                span= error

    div(class="row")
        div(class="col-12")
            form(action="/todos/add" method="POST")
                div(class="row")
                    div(class="input-field col-12")
                        input(type="text" placeholder="Type Todo Description" name="description" id="description")
                div
                    button(class="waves-effect waves-light btn-large" type="submit")
                        i(class="material-icons left") save
                        | Add
    ...
````

Open Getting Started App in a browser and check it

![Step 3 Result](/images/step-3-result.jpg)

## Step-4: Remove Todo

In the previous step we saw how to save new entities in Doctrine repositories, In this one, we are going to allow the user to remove Todos.

**Use case**

* User should click on Todo list item delete button and confirm the disclaimer
* Todo should be removed

### Step-4.1: Implementing use case

Create new request handler

````php
<?php
// src/Application/Http/Handler/RemoveTodo.php

declare(strict_types=1);

namespace App\Application\Http\Handler;

use App\Domain\TodosRepository;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\RedirectResponse;

use function sprintf;

class RemoveTodo implements RequestHandlerInterface
{
    private $todosRepository;

    public function __construct(TodosRepository $todosRepository)
    {
        $this->todosRepository = $todosRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int)$request->getAttribute('id');
        $session = $request->getAttribute('session');

        try {
            $this->todosRepository->remove($id);
            $session->setFlash('success', 'Todo successfully removed.');
        } catch (InvalidArgumentException $e) {
            $session->setFlash('error', sprintf('%s', $e->getMessage()));
        }

        return new RedirectResponse('/');
    }
}
````

At the begin of the handler we get the Todo id from the request attributes, we see how to pass it as route param, then we pass that id to `remove` method of repository, if "id" is valid Todo with given "id" should be deleted, and success message should be added to flash session, if "id" is not valid error message should be added to flash, then it should be redirected to home page.

We need a dynamic route to execute handler with a given "id"

````php
<?php
// router/routes.php

declare(strict_types=1);

use App\Application\Http\Handler\RemoveTodo;
...

return static function (Application $app, ContainerInterface $container) : void {
    ...
    $app->post('/todos/remove/{id}', [RemoveTodo::class], 'remove_todo');
};
````

Update again `TodosRepository` adding new `remove` method

````php
<?php
// src/Domain/TodosRepository.php

declare(strict_types=1);

namespace App\Domain;

interface TodosRepository
{
    ...
    public function remove(int $id): void;
}
````

Update `DoctrineTodosRepository` according to interface

````php
<?php
// src/Infrastructure/Repository/DoctrineTodosRepository.php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use InvalidArgumentException;
...

class DoctrineTodosRepository extends EntityRepository implements TodosRepository
{
    ...
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
````

Add the handler in `config/services` directory

````yaml
# config/services/dependencies.prod.yaml
services:
  ...
  App\Application\Http\Handler\RemoveTodo:
    
````

Open again `templates/todos/index.pug` file and add disclaimer and form

````pug
// templates/todos/index.pug
extends ../base.pug

block content
    h1 Todos app List

    if success
        div(class="row")
            div(class="card row green lighten-3 white-text" style="padding: 20px 15px")
                dic(class="card-content")
                    span= success
    ...
    ul(class="collection")
        each todo in todos
            li(class="collection-item")= todo.description()
                a(href='#remove-modal' + todo.id() class="modal-trigger")
                    i(class="material-icons right red-text") delete
                a(href="#")
                    i(class="material-icons right yellow-text text-darken-2") edit

                div(id='remove-modal' + todo.id() class="modal")
                    div(class="modal-content")
                        h5 Are you sure you want to delete this todo?
                        blockquote= todo.description()
                    div(class="modal-footer")
                        form(method="POST" action='/todos/remove/' + todo.id() )
                            button(type="submit" class="waves-effect waves-green btn-flat")
                                i(class="material-icons left red-text") delete
                                | Agree    
````

Materializecss modal component requires to be loaded by javascript

````js
// public/js/main.js
"use strict";

(function () {
  document.addEventListener('DOMContentLoaded', function() {
    var elems = document.querySelectorAll('.modal');
    var instances = M.Modal.init(elems);
  });
})(M, document);
````

And we need to load it in the base layout

````pug
html
    ...
    script(type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js")
    script(type="text/javascript" src="/js/main.js")
    ...
````

Congratulations we arrive at the end of the fourth step, check it in your browser that everything goes well

![Step 4 Result](/images/step-4-result.jpg)
![Step 4 Result](/images/step-4-result-1.jpg)

## Step-5: Edit Todos

In the Steep-4 we learn how to remove Todos from the repository, now we going to create an edit form to allow users updating the Todos

**Use case**

* User should click on Todo list item edit button to open a modal form
* The form should have description "textarea" input
* The user should click on save button after adding a new description
* Todo should be updated

### Step-5.1: Implementing use case

Add another request handler

````php
<?php
// src/Application/Http/Handler/EditTodo.php

declare(strict_types=1);

namespace App\Application\Http\Handler;

use App\Domain\TodosRepository;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\RedirectResponse;

class EditTodo implements RequestHandlerInterface
{
    private $todosRepository;

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
````

Here we don't validate description, we get validated description from request attributes, this can be done thanks to piped routes we need to add the `TodoValidationRequest` middleware

````php
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
````

Add middleware to dependencies config

````yaml
services:
  ...
  App\Application\Http\Handler\EditTodo:
  App\Application\Http\Middleware\ValidateTodoRequest:
  ...
````

Define the new route in the router

````php
<?php
// router/routes.php

declare(strict_types=1);

use Antidot\Application\Http\Application;
use App\Application\Http\Handler\AddTodo;
use App\Application\Http\Handler\EditTodo;
use App\Application\Http\Middleware\ValidateTodoRequest;
use Psr\Container\ContainerInterface;
...

return static function (Application $app, ContainerInterface $container) : void {
    ...
    $app->post('/todos/add', [ValidateTodoRequest::class, AddTodo::class], 'add_todo');
    $app->post('/todos/edit/{id}', [ValidateTodoRequest::class, EditTodo::class], 'edit_todo');
};
````

And we need to update`AddTodo` request handler to use validation middleware too

````php
<?php
// src/Application/Http/Handler/AddTodo.php

declare(strict_types=1);

namespace App\Application\Http\Handler;

use App\Domain\TodosRepository;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\RedirectResponse;

use function sprintf;

class AddTodo implements RequestHandlerInterface
{
    private $todosRepository;

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
````

Update `HomePage` request handler to add success flash messages to HTML response

````php
<?php
// src/Application/Http/Handler/HomePage.php

declare(strict_types=1);

namespace App\Application\Http\Handler;

...
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;

class HomePage implements RequestHandlerInterface
{
    ...
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute('session');
        ...
        return new HtmlResponse(
            $this->templateRenderer->render('todos/index', [
                ...
                'error' => $session->getFlash('error'),
                'success' => $session->getFlash('success'),
            ])
        );
    }
}
````

Create the `update` method in the `TodosRepository`

````php
<?php
// src/Domain/TodosRepository.php

declare(strict_types=1);

namespace App\Domain;

interface TodosRepository
{
    ...
    public function update(int $id, string $description): void;
}
````

Implement it in doctrine repository

````php
<?php
// src/Infrastructure/Repository/DoctrineTodosRepository.php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

...

class DoctrineTodosRepository extends EntityRepository implements TodosRepository
{
    ...
    public function update(int $id, string $description): void
    {
        $todo = $this->findOneBy(['id' => $id]);
        if (null === $todo) {
            throw new InvalidArgumentException('Invalid Todo id given.');
        }

        $todo->update($description);
        $this->_em->flush($todo);
    }
}
````

And add the `update` method to `Todo` model

````php
<?php
// src/Domain/Model/Todo.php

declare(strict_types=1);

namespace App\Domain\Model;

class Todo
{
    ...
    public function update(string $description): void
    {
        $this->description = $description;
    }
}
````

### Step-5.2: Template inheritance

As we can see the `templates/todos/index.pug` template starts growing, to avoid this situation we can use template inheritance, we are going to move todo list from index to new template

````pug
// templates/todos/list-item.pug
li(class="collection-item")
    p(class="flow-text")
        i(class="material-icons left blue-text") build
        = todo.description()
        a(href='#remove-modal' + todo.id() class="modal-trigger")
            i(class="material-icons right red-text") delete
        a(href='#edit-modal' + todo.id() class="modal-trigger")
            i(class="material-icons right yellow-text text-darken-2") edit

    div(id='remove-modal' + todo.id() class="modal")
        div(class="modal-content")
            h5 Are you sure you want to delete this todo?
            blockquote= todo.description()
        div(class="modal-footer")
            form(method="POST" action='/todos/remove/' + todo.id() )
                button(type="submit" class="waves-effect waves-green btn-flat")
                    i(class="material-icons left red-text") delete
                    | Agree

    div(id='edit-modal' + todo.id() class="modal")
        form(action='/todos/edit/' + todo.id() method="POST")
            div(class="modal-content")
                h5 Edit Todo content
                textarea(class="materialize-textarea" name="description")=todo.description()
            div(class="modal-footer")
                button(type="submit" class="waves-effect waves-green btn-flat")
                    i(class="material-icons left blue-text") save
                    | Save
````

Then declare inheritance inner `index.pug` file

````pug
// templates/todos/index.pug
extends ../base.pug

block content
    ...
    ul(class="collection")
        each todo in todos
            include ./list-item.pug

````

Now we have a fully functional Todo App, and more importantly, we have learned some skills like dependency injection, routing, dependency inversion, request handling and so on

![Step 5 Result](/images/step-5-result.jpg)

Thanks for supporting 

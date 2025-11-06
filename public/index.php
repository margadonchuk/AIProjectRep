<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use Slim\Middleware\ErrorMiddleware;

require __DIR__ . '/../vendor/autoload.php';

$rootPath = dirname(__DIR__);

if (is_file($rootPath . '/.env')) {
    Dotenv::createImmutable($rootPath)->safeLoad();
} else {
    Dotenv::createImmutable($rootPath, '.env.example')->safeLoad();
}

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

$errorMiddleware = new ErrorMiddleware(
    $app->getCallableResolver(),
    $app->getResponseFactory(),
    (bool) ($_ENV['APP_DEBUG'] ?? false),
    true,
    true
);
$app->add($errorMiddleware);

(require __DIR__ . '/../src/routes.php')($app);

$app->run();

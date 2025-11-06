<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use Slim\Middleware\ErrorMiddleware;

require __DIR__ . '/../vendor/autoload.php';

$rootPath = dirname(__DIR__);

$dotenv = Dotenv::createImmutable($rootPath);
if (is_file($rootPath . '/.env')) {
    $dotenv->safeLoad();
} else {
    Dotenv::createImmutable($rootPath, '.env.example')->safeLoad();
}

$debugMode = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL);

if ($debugMode) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

$errorMiddleware = new ErrorMiddleware(
    $app->getCallableResolver(),
    $app->getResponseFactory(),
    $debugMode,
    true,
    true
);
$app->add($errorMiddleware);

(require __DIR__ . '/../src/routes.php')($app);

$app->run();

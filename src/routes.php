<?php

declare(strict_types=1);

use AIProject\Controllers\AiController;
use AIProject\Controllers\ContentController;
use AIProject\Controllers\HealthController;
use AIProject\Controllers\ProjectController;
use AIProject\Services\AiClient;
use AIProject\Services\ContentService;
use AIProject\Services\ProjectRepository;
use GuzzleHttp\Client;
use Slim\App;
use Slim\Psr7\Response;

return static function (App $app): void {
    $rootPath = dirname(__DIR__);
    $dataFile = $rootPath . '/data/projects.json';

    if (!is_file($dataFile)) {
        file_put_contents($dataFile, json_encode([], JSON_PRETTY_PRINT));
    }

    $httpClient = new Client([
        'timeout' => 15,
    ]);

    $projectController = new ProjectController(new ProjectRepository($dataFile));
    $aiController = new AiController(new AiClient($httpClient));
    $contentController = new ContentController(new ContentService($httpClient));
    $healthController = new HealthController();

    $app->get('/api/health', $healthController);
    $app->get('/api/projects', [$projectController, 'list']);
    $app->post('/api/projects', [$projectController, 'create']);
    $app->post('/api/ask', [$aiController, 'chat']);
    $app->get('/api/articles', [$contentController, 'articles']);

    $app->get('/assets/{path:.*}', static function () {
        return new Response(404);
    });
};

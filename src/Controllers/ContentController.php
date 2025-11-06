<?php

declare(strict_types=1);

namespace AIProject\Controllers;

use AIProject\Services\ContentService;
use AIProject\Support\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ContentController
{
    public function __construct(private readonly ContentService $service)
    {
    }

    public function articles(ServerRequestInterface $request): ResponseInterface
    {
        return JsonResponder::success([
            'articles' => $this->service->fetchArticles(),
        ]);
    }
}

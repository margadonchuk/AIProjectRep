<?php

declare(strict_types=1);

namespace AIProject\Controllers;

use AIProject\Support\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class HealthController
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return JsonResponder::success([
            'status' => 'ok',
            'timestamp' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ]);
    }
}

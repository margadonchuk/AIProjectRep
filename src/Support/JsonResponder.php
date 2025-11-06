<?php

declare(strict_types=1);

namespace AIProject\Support;

use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;

final class JsonResponder
{
    /**
     * @param array<string, mixed>|array<int, mixed> $data
     */
    public static function success(array $data = [], int $status = 200): ResponseInterface
    {
        $response = new Response($status);
        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $data,
        ], JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function failure(string $message, int $status = 400, array $errors = []): ResponseInterface
    {
        $response = new Response($status);
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json');
    }
}

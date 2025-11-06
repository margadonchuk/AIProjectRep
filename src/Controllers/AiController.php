<?php

declare(strict_types=1);

namespace AIProject\Controllers;

use AIProject\Services\AiClient;
use AIProject\Support\JsonResponder;
use AIProject\Support\Validator;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AiController
{
    public function __construct(private readonly AiClient $client)
    {
    }

    public function chat(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array<string, mixed> $payload */
        $payload = (array) $request->getParsedBody();

        try {
            Validator::requireFields(['prompt'], $payload);
        } catch (InvalidArgumentException $exception) {
            return JsonResponder::failure($exception->getMessage(), 422);
        }

        $prompt = trim((string) $payload['prompt']);
        $systemPrompt = (string) ($payload['systemPrompt'] ?? 'You are a helpful assistant.');

        $response = $this->client->chat([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $prompt],
        ]);

        return JsonResponder::success(['response' => $response]);
    }
}

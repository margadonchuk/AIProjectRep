<?php

declare(strict_types=1);

namespace AIProject\Services;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class AiClient
{
    public function __construct(
        private readonly ClientInterface $http,
        private readonly ?LoggerInterface $logger = null
    ) {
    }

    /**
     * @param array<int, array<string, string>> $messages
     * @return array<string, mixed>
     */
    public function chat(array $messages): array
    {
        $apiKey = $_ENV['OPENAI_API_KEY'] ?? '';
        if ($apiKey === '') {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        try {
            $response = $this->http->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => $messages,
                    'temperature' => 0.2,
                ],
            ]);
        } catch (GuzzleException $exception) {
            $this->logger?->error('AI chat call failed', ['exception' => $exception]);
            throw new RuntimeException('Unable to reach AI provider.');
        }

        $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($payload)) {
            throw new RuntimeException('Unexpected response from AI provider.');
        }

        return $payload;
    }
}

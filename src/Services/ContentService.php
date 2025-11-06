<?php

declare(strict_types=1);

namespace AIProject\Services;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

final class ContentService
{
    public function __construct(private readonly ClientInterface $http)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchArticles(): array
    {
        $baseUrl = rtrim($_ENV['CONTENT_API_BASE'] ?? '', '/');
        if ($baseUrl === '') {
            return [];
        }

        try {
            $response = $this->http->request('GET', $baseUrl . '/articles');
        } catch (GuzzleException $exception) {
            throw new RuntimeException('Unable to fetch editorial content.', 0, $exception);
        }

        $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($payload)) {
            throw new RuntimeException('Malformed content response.');
        }

        return $payload;
    }
}

<?php

declare(strict_types=1);

namespace AIProject\Controllers;

use AIProject\Services\ProjectRepository;
use AIProject\Support\JsonResponder;
use AIProject\Support\Validator;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ProjectController
{
    public function __construct(private readonly ProjectRepository $repository)
    {
    }

    public function list(ServerRequestInterface $request): ResponseInterface
    {
        return JsonResponder::success([
            'projects' => $this->repository->all(),
        ]);
    }

    public function create(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array<string, mixed> $payload */
        $payload = (array) $request->getParsedBody();

        try {
            Validator::requireFields(['title', 'summary', 'tags'], $payload);
        } catch (InvalidArgumentException $exception) {
            return JsonResponder::failure($exception->getMessage(), 422);
        }

        $project = [
            'title' => trim((string) $payload['title']),
            'summary' => trim((string) $payload['summary']),
            'tags' => array_values(array_map('strval', (array) $payload['tags'])),
            'createdAt' => (new \DateTimeImmutable('now'))->format(DATE_ATOM),
        ];

        $this->repository->append($project);

        return JsonResponder::success(['project' => $project], 201);
    }
}

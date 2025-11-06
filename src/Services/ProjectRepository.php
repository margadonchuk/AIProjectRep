<?php

declare(strict_types=1);

namespace AIProject\Services;

use RuntimeException;

final class ProjectRepository
{
    public function __construct(
        private readonly string $dataFile
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        if (!is_file($this->dataFile)) {
            return [];
        }

        $contents = file_get_contents($this->dataFile);
        if ($contents === false || $contents === '') {
            return [];
        }

        $data = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new RuntimeException('Malformed project data.');
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $project
     */
    public function append(array $project): void
    {
        $all = $this->all();
        $all[] = $project;

        $json = json_encode($all, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        file_put_contents($this->dataFile, $json);
    }
}

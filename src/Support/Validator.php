<?php

declare(strict_types=1);

namespace AIProject\Support;

use InvalidArgumentException;

final class Validator
{
    /**
     * @param array<string> $required
     * @param array<string, mixed> $data
     */
    public static function requireFields(array $required, array $data): void
    {
        $missing = [];

        foreach ($required as $field) {
            if (!array_key_exists($field, $data) || self::isEmpty($data[$field])) {
                $missing[] = $field;
            }
        }

        if ($missing !== []) {
            throw new InvalidArgumentException('Missing required fields: ' . implode(', ', $missing));
        }
    }

    private static function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && count($value) === 0);
    }
}

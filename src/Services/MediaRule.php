<?php

namespace Otas\Mediable\Services;

use InvalidArgumentException;

class MediaRule
{
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png'];
    private const VALID_STATES = ['required', 'sometimes', 'nullable'];

    public static function single(
        string $state = 'required',
        int $maxSize = 1024,
        array $extensions = self::IMAGE_EXTENSIONS,
    ): array {
        self::assertValidState($state);

        return [$state, 'file', 'mimes:' . implode(',', $extensions), 'max:' . $maxSize];
    }

    public static function collection(
        string $field,
        string $state = 'required',
        int $maxSize = 1024,
        array $extensions = self::IMAGE_EXTENSIONS,
        int $minCount = 1,
        int $maxCount = 10,
    ): array {
        self::assertValidState($state);

        $parentRules = [$state, 'array'];

        if ($minCount > 0) {
            $parentRules[] = 'min:' . $minCount;
        }

        if ($maxCount > 0) {
            $parentRules[] = 'max:' . $maxCount;
        }

        $wildcardRules = ['file', 'mimes:' . implode(',', $extensions), 'max:' . $maxSize];

        if ($state === 'required') {
            array_unshift($wildcardRules, 'required_with:' . $field);
        }

        return [
            $field => $parentRules,
            $field . '.*' => $wildcardRules,
        ];
    }

    private static function assertValidState(string $state): void
    {
        if (! in_array($state, self::VALID_STATES, true)) {
            throw new InvalidArgumentException("Invalid media state '{$state}'. Allowed: required, sometimes, nullable.");
        }
    }
}

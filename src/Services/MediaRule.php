<?php

namespace Otas\Mediable\Services;

class MediaRule
{
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png'];

    public static function single(
        string $state = 'required',
        int $maxSize = 1024,
        array $extensions = self::IMAGE_EXTENSIONS,
        array $additionalRules = [],
    ): array {
        return [$state, 'file', 'mimes:' . implode(',', $extensions), 'max:' . $maxSize, ...$additionalRules];
    }

    public static function collection(
        string $field,
        string $state = 'required',
        int $maxSize = 1024,
        array $extensions = self::IMAGE_EXTENSIONS,
        int $minCount = 1,
        int $maxCount = 10,
    ): array {
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
}

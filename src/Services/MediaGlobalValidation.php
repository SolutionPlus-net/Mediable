<?php

namespace Otas\Mediable\Services;

class MediaGlobalValidation
{
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png'];

    public static function fileValidation(
        string $state = 'required',
        int $maxSize = 1024,
        array $extensions = self::IMAGE_EXTENSIONS,
        array $additionalRules = [],
    ): array {
        return [$state, 'file', 'mimes:' . implode(',', $extensions), 'max:' . $maxSize, ...$additionalRules];
    }

    public static function arrayValidation(
        string $state = 'required',
        int $minCount = 1,
        int $maxCount = 5,
    ): array {
        $rules = [$state, 'array'];

        if ($minCount > 0) {
            $rules[] = 'min:' . $minCount;
        }

        if ($maxCount > 0) {
            $rules[] = 'max:' . $maxCount;
        }

        return $rules;
    }
}

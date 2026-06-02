<?php

namespace Otas\Mediable\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Http\UploadedFile;
use Otas\Mediable\Models\TranslatedMedia;

Trait HasTranslatedMedia
{
    use Mediable;

    ## Relations

	public function media(): MorphMany
    {
        return $this->morphMany(TranslatedMedia::class, 'mediable')->orderBy('priority', 'asc');
    }

	public function singleMedia(): MorphOne
    {
        return $this->morphOne(TranslatedMedia::class, 'mediable');
    }

    ## Getters & Setters

    ## Query Scope Methods

    ## Other Methods

    public function addMedia(
        UploadedFile $requestFile,
        string $type = 'photo',
        ?string $disk = null,
        bool $isMain = false,
        ?string $title = null,
        ?string $description = null,
        int $priority = 9999,
    ) {
        $handledFile = $this->storeRequestFile(requestFile: $requestFile, type: $type, disk: $disk);

        if ($isMain) {
            $this->normalizePreviousMainMedia();
        }

        $media = $this->media()->create([
            'path' => $handledFile['path'],
            'type' => $type,
            'extension' => $handledFile['extension'],
            'is_main' => $isMain,
            'priority' => $priority,
            'size' => $handledFile['size'],
        ]);

        request()->dontTranslate = true;
        $media->translate([
            'title' => $title,
            'description' => $description,
        ], (config('translatable.fallback_locale') ?? config('app.fallback_locale')));
        request()->dontTranslate = false;

        return $this;
    }

    public function editMedia(
        UploadedFile $requestFile,
        ?TranslatedMedia $singleMedia,
        string $type = 'photo',
        ?string $disk = null,
        bool $isMain = false,
        ?string $title = null,
        ?string $description = null,
        int $priority = 9999,
    ): void {
        if (!$singleMedia) {
            $this->addMedia(
                requestFile: $requestFile,
                type: $type,
                disk: $disk,
                isMain: $isMain,
                title: $title,
                description: $description,
                priority: $priority,
            );

            return;
        }

        $handledFile = $this->storeRequestFile(requestFile: $requestFile, type: $type, disk: $disk);
        $singleMedia->remove(removeFileWithoutObject: true);

        $singleMedia->update([
            'path' => $handledFile['path'],
            'extension' => $handledFile['extension'],
            'is_main' => $isMain,
            'priority' => $priority,
            'size' => $handledFile['size'],
            'updated_at' => Carbon::now(),
        ]);

        request()->dontTranslate = true;
        $singleMedia->translate([
            'title' => $title ?? $singleMedia->title,
            'description' => $description ?? $singleMedia->description,
        ], (config('translatable.fallback_locale') ?? config('app.fallback_locale')));
        request()->dontTranslate = false;
    }
}
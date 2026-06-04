<?php

namespace Otas\Mediable\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Otas\Mediable\Models\TranslatedMedia;

trait HasTranslatedMedia
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
        ?int $priority = null,
    ): void {
        if (!$singleMedia) {
            $this->addMedia(
                requestFile: $requestFile,
                type: $type,
                disk: $disk,
                isMain: $isMain,
                title: $title,
                description: $description,
                priority: $priority ?? 9999,
            );

            return;
        }

        $handledFile = $this->storeRequestFile(requestFile: $requestFile, type: $type, disk: $disk);

        if ($isMain) {
            $this->normalizePreviousMainMedia();
        }
        
        $oldPath = $singleMedia->storagePath;

        $singleMedia->update([
            'path' => $handledFile['path'],
            'extension' => $handledFile['extension'],
            'priority' => $priority,
            'size' => $handledFile['size'],
            'updated_at' => Carbon::now(),
            'is_main' => $isMain,               
        ]);

        Storage::delete($oldPath);   
                
        request()->dontTranslate = true;
        $singleMedia->translate([
            'title' => $title ?? $singleMedia->title,
            'description' => $description ?? $singleMedia->description,
        ], (config('translatable.fallback_locale') ?? config('app.fallback_locale')));
        request()->dontTranslate = false;
    }
}
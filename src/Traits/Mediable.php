<?php

namespace Otas\Mediable\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Otas\Mediable\Models\Media;
use Otas\Mediable\Models\MediaMeta;
use ReflectionClass;

trait Mediable
{
    ## Relations

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->orderBy('priority', 'asc');
    }

    public function singleMedia(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable');
    }

    public function mainMedia(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')->where('is_main', true);
    }

    ## Getters & Setters

    public function getMediaDirectoryAttribute($value)
    {
        $className = new ReflectionClass($this);
        return Str::plural(strtolower($className->getShortName()));
    }

    public function getPhotosDirectoryAttribute()
    {
        return config('mediable.base_path') . config('mediable.photos.path') . "{$this->mediaDirectory}";
    }

    public function getFilesDirectoryAttribute()
    {
        return config('mediable.base_path') . config('mediable.files.path') . "{$this->mediaDirectory}";
    }

    public function getVideosDirectoryAttribute()
    {
        return config('mediable.base_path') . config('mediable.videos.path') . "{$this->mediaDirectory}";
    }

    public function getMainMediaAttribute()
    {
        if ($this->relationLoaded('mainMedia')) {
            return $this->getRelation('mainMedia');
        }

        $collection = $this->media;

        return $collection->firstWhere('is_main', true) ?? $collection->first();
    }

    public function getNonMainMediaAttribute()
    {
        return $this->media->where('is_main', false);
    }

    public function getIsMainMediaAttribute()
    {
        return (bool) $this->singleMedia->is_main;
    }

    public function getPhotosAttribute()
    {
        return $this->media->where('type', 'photo');
    }

    public function getPhotoAttribute()
    {
        return $this->media->where('type', 'photo')->first();
    }

    public function getFilesAttribute()
    {
        return $this->media->where('type', 'file');
    }

    public function getFileAttribute()
    {
        return $this->media->where('type', 'file')->first();
    }

    public function getVoicesAttribute()
    {
        return $this->media->where('type', 'voice');
    }

    public function getVoiceAttribute()
    {
        return $this->media->where('type', 'voice')->first();
    }

    public function getVideosAttribute()
    {
        return $this->media->where('type', 'video');
    }

    public function getVideoAttribute()
    {
        return $this->media->where('type', 'video')->first();
    }

    public function getUrlsAttribute()
    {
        return $this->media->where('type', 'url');
    }

    public function getUrlAttribute()
    {
        return $this->media->where('type', 'url')->first();
    }

    protected function getVideoIdAttribute()
    {
        return getYoutubeVideoId($this->path);
    }

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
    ): void {

        $handledFile = $this->storeRequestFile(requestFile: $requestFile, type: $type, disk: $disk);

        if ($isMain) {
            $this->normalizePreviousMainMedia();
        }

        $this->media()->create([
            'path' => $handledFile['path'],
            'type' => $type,
            'extension' => $handledFile['extension'],
            'title' => $title,
            'description' => $description,
            'is_main' => $isMain,
            'priority' => $priority,
            'size' => $handledFile['size'],
        ]);
    }

    public function editMedia(
        UploadedFile $requestFile,
        ?Media $singleMedia,
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
            'title' => $title ?? $singleMedia->title,
            'description' => $description ?? $singleMedia->description,
            'priority' => $priority ?? $singleMedia->priority,
            'size' => $handledFile['size'],
            'is_main' => $isMain,            
        ]);
        
        Storage::delete($oldPath);        
    }

    public function deleteMedia(Media $singleMedia): void
    {
        $singleMedia->remove();
    }

    public function deleteAllMedia(): void
    {
        $this->media->each(function ($singleMedia) {
            $this->deleteMedia($singleMedia);
        });
    }

    public function updateOrCreateMediaMeta(Media $media)
    {
        MediaMeta::updateOrCreate(['media_id' => $media->id]);
    }

    private function normalizePreviousMainMedia(): void
    {
        if (optional($this->mainMedia)->is_main) {
            $this->mainMedia->update([
                'is_main' => false
            ]);
        }
    }

    public function newMediaDirectory(string $type): string
    {
        return match ($type) {
            'photo' => $this->photosDirectory,
            'file' => $this->filesDirectory,
            'video' => $this->videosDirectory,
            default => $this->photosDirectory,
        };
    }

    private function storeRequestFile(UploadedFile $requestFile, string $type, ?string $disk = null): array
    {
        $extension = $requestFile->getClientOriginalExtension();
        $name = now()->timestamp . '-' . random_int(100000, 999999);

        $disk = $disk ?? config('filesystems.default');

        $directory = $this->newMediaDirectory(type: $type);

        $path = $requestFile->storeAs($directory, "{$name}.{$extension}", $disk);

        if (!$path) {
            throw new \RuntimeException('Failed to store media file');
        }

        return [
            'path' => $path,
            'size' => $requestFile->getSize(),
            'extension' => $extension,
        ];
    }
}
<?php

namespace Otas\Mediable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Otas\Mediable\Factories\MediaFactory;
use Otas\Mediable\Traits\MediaModelsTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Media extends Model
{
    use HasFactory, MediaModelsTrait;

    protected $table = 'media';

    protected $fillable = [
        'path',
        'type',         // photo, video, file, voice or url
        'extension',
        // 'media_group_name', // non translated. just used to separate the multiple media groups programatically // ! will add later
        'size',         // file size in kb
        'title',        // nullable
        'description',  // nullable
        'priority',     // default 0
        'is_main',

        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_main' => 'boolean',
    ];

    ## Relations

    public function mediaMeta(): HasOne
    {
        return $this->hasOne(MediaMeta::class);
    }

    ## Getters & Setters

    ## Query Scope Methods

    public function scopeByTitle($query, string $title = '')
    {
        return $title == '' ? $query : $query->where('title', $title);
    }

    public function scopeOfGroup($query, $group = '')
    {
        // return $group != '' ? $query->where('media_group_name', $group) : $query;
        return $query; // ! wip: till we add the full functionality
    }

    ## Other Methods

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return MediaFactory::new();
    }

    public function remove(bool $removeFileWithoutObject = false): bool
    {
        Storage::delete($this->storagePath);

        if (!$removeFileWithoutObject) {
            $this->mediaMeta?->remove();
            $this->delete();
        }

        return true;
    }
}

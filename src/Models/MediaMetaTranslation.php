<?php

namespace Otas\Mediable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaMetaTranslation extends Model
{
    protected $fillable = [
        'media_meta_id',

        'locale',

        'meta_title',
        'alternative_text',
    ];

    ## Relations

    public function mediaMeta(): BelongsTo
    {
        return $this->belongsTo(MediaMeta::class);
    }

    ## Getters & Setters

    ## Query Scope Methods

    ## Other Methods    
}

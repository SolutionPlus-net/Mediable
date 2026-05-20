<?php

namespace Otas\Mediable\Http\Controllers;

use Otas\Mediable\Models\Media;
use Otas\Mediable\Http\Resources\MediaResource;
use Otas\Mediable\Http\Requests\MediaUpdateRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MediaController extends Controller
{
    /**
     * Update the specified resource in storage.
     *
     * @param MediaUpdateRequest $request
     * @param Media $medium
     * @return \Illuminate\Http\Response
     */
    public function update(MediaUpdateRequest $request, Media $medium)
    {
        $medium = $request->updateMedia();

        return response([
            'message' => __('One media file updated successfully'),
            'media' => new MediaResource($medium),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Otas\Mediable\Models\Media  $medium
     * @return \Illuminate\Http\Response
     */
    public function destroy($medium)
    {
        $medium = Media::findOrFail($medium);
        if (!$medium->mediable) {
            optional($medium)->remove();
            throw new ModelNotFoundException;
        }

        if ($medium->is_main) {
            return response([
                'message' => __('You can\'t delete a main media file'),
            ], 422);
        }

        $medium->mediable->deleteMedia($medium);
        return response([
            'message' => __('One media file deleted successfully'),
        ]);
    }
}

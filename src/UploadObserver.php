<?php

namespace Frengky\Yupload;

use Illuminate\Support\Str;

class UploadObserver
{
    /**
     * Creating new UUID for each new record
     *
     * @param Upload $upload
     * @return bool
     */
    public function creating(Upload $upload)
    {
        $upload->id = Str::uuid()->toString();
        return true;
    }

    /**
     * Handle when model is updated
     *
     * @param Upload $upload
     * @return bool
     */
    public function updated(Upload $upload)
    {
        $originalPath = $upload->getOriginal('path');
        if ($upload->path != $originalPath) {
            Upload::storage()->delete($originalPath);
        }
        return true;
    }

    /**
     * Handle when model is deleting
     *
     * @param Upload $upload
     * @return bool
     */
    public function deleted(Upload $upload)
    {
        Upload::storage()->delete($upload->getOriginal('path'));
        return true;
    }
}
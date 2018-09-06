<?php

namespace Frengky\Yupload;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class UploadObserver
{
    /**
     * Handle the model "creating" event.
     *
     * @param mixed $upload
     * @return bool
     */
    public function creating($upload)
    {
        if ($upload->file) {
            $upload->{$upload->getKeyName()} = Str::uuid()->toString();
            list($path, $filename) = explode('/', $upload->path);
            if ($storagePath = $upload->storage()->putFileAs($path, $upload->file, $filename)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Handle the model "updating" event.
     *
     * @param mixed $upload
     * @return bool
     */
    public function updating($upload)
    {
        if ($upload->file) {
            list($path, $filename) = explode('/', $upload->path);
            $ext = $upload->file instanceof UploadedFile ? $upload->file->getClientOriginalExtension() : $upload->file->getExtension();
            $hashName = Str::random(40) . ( $ext ? ".$ext" : '' );
            $upload->path = $path . '/' . $hashName;
            if ($storagePath = $upload->storage()->putFileAs($path, $upload->file, $hashName)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Handle the model "updated" event.
     *
     * @param mixed $upload
     * @return bool
     */
    public function updated($upload)
    {
        $originalPath = $upload->getOriginal('path');
        if ($upload->path != $originalPath) {
            $upload->storage()->delete($originalPath);
        }

        return true;
    }

    /**
     * Handle when model is deleted
     *
     * @param mixed $upload
     * @return bool
     */
    public function deleted($upload)
    {
        $upload->storage()->delete($upload->getOriginal('path'));
        
        return true;
    }
}
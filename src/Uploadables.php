<?php

namespace Frengky\Yupload;

use Storage;

trait Uploadables
{
    /**
     * The default storage for storing uploaded files
     *
     * @return mixed
     */
    public static function storage()
    {
        return Storage::disk(env('UPLOAD_STORAGE_DISK', 'public'));
    }

    /**
     * Get the uploadable entity that the upload belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function uploadable()
    {
        return $this->morphTo();
    }

    /**
     * Return a response that force user's browser to download
     * with the original file name
     *
     * @return mixed
     */
    public function download()
    {
        return self::storage()->download($this->path, $this->name);
    }

    /**
     * Check if this uploaded file is an image
     *
     * @return bool
     */
    public function isImage()
    {
        return strpos($this->mimetype, 'image/') === 0;
    }

    /**
     * Cast to string as alternative to get the full url
     *
     * @return string
     */
    public function __toString()
    {
        return self::storage()->url($this->path);
    }
}
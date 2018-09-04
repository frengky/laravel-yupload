<?php

namespace Frengky\Yupload\Concerns;

use Frengky\Yupload\UploadObserver;

use Storage;

trait Uploadables
{
    /**
     * Observing records to maintain the uploaded files
     */
    protected static function bootUploadables()
    {
        static::observe(UploadObserver::class);
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
     * Scope a query to only include uploads of a given type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $type = null)
    {
        if (empty($type)) {
            return $query->whereNull('type');
        }
        return $query->whereIn('type', is_array($type) ? $type : [$type]);
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
<?php

namespace Frengky\Yupload\Concerns;

use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Storage;

use Frengky\Yupload\UploadObserver;

trait Uploadables
{
    /** @var File|UploadedFile */
    protected $uploadedFile;

    /**
     * Observing records to maintain the uploaded files
     */
    protected static function bootUploadables()
    {
        static::observe(UploadObserver::class);
    }

    /**
     * Create an instance of Upload
     *
     * @param File|UploadedFile $file
     * @param string $path
     * @param string $type
     */
    public static function make($file, $path, $type = null)
    {
        if (! $file instanceof UploadedFile && ! $file instanceof File)
            throw new \RuntimeException('File must be instance of File or UploadedFile');

        $model = new self;

        $ext = $file instanceof UploadedFile ? $file->getClientOriginalExtension() : $file->getExtension();
        $hashName = Str::random(40) . ( $ext ? ".$ext" : '' );

        $model->fill([
            'file' => $file,
            'path' => rtrim(ltrim($path, '/'), '/') . '/' . $hashName,
            'type' => empty($type) ? null : $type
        ]);

        return $model;
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
     * The 'file' mutator to set the UploadedFile
     *
     * @param mixed $value
     */
    public function setFileAttribute($value)
    {
        if (! $value instanceof UploadedFile && ! $value instanceof File)
            throw new \RuntimeException('File must be instance of File or UploadedFile');

        if ($value instanceof UploadedFile && ! $value->isValid()) {
            return;
        }

        $this->attributes['mimetype'] = $value->getMimeType();
        $this->attributes['name'] = $value instanceof UploadedFile ? $value->getClientOriginalName() : $value->getFilename();
        $this->attributes['size'] = $value->getSize();

        $this->uploadedFile = $value;
    }

    /**
     * The 'file' accessor to get the UploadedFile
     *
     * @return File|UploadedFile
     */
    public function getFileAttribute()
    {
        return $this->uploadedFile;
    }

    /**
     * Get the storage for storing the uploaded files
     *
     * @return mixed
     */
    public function storage()
    {
        return Storage::disk(config('yupload.storage_disk'));
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
        return $this->storage()->download($this->path, $this->name);
    }

    /**
     * Get the full url
     *
     * @return string
     */
    public function url()
    {
        return $this->storage()->url($this->path);
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
}
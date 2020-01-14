<?php

namespace Frengky\Yupload\Concerns;

use Illuminate\Support\Str;
use Frengky\Yupload\Upload;

trait HasUploads {

    use HasVirtualAttributes;

    /**
     * Treat all prefixed attribute as virtual attribute for uploaded file
     * @var string
     */
    protected $virtualAttributePrefix = 'upload_';

    /**
     * Register model events, all upload related operation should be processed only on saved and deleted.
     */
    protected static function bootHasUploads()
    {
        static::saved(function ($entity) {
            $attributes = $entity->getVirtualAttributes();
            foreach($attributes as $type => $upload) {
                if (is_array($upload)) {
                    $entity->uploads()->saveMany($upload);
                } else if ($upload->uploadable_id) {
                    $upload->save();
                } else {
                    $entity->uploads()->save($upload);
                }
            }
            $entity->clearVirtualAttributes();
        });

        static::deleted(function ($entity) {
            if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($entity)) &&
                !$entity->isForceDeleting()) {
                return;
            }
            $entity->deleteUploads();
            $entity->clearVirtualAttributes();
        });
    }

    /**
     * Get the entity's uploads collections.
     *
     * $userUploads = $user->uploads;
     * $latestUserUploads = $user->uploads()->orderBy('created_at', 'desc')->get();
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function uploads()
    {
        return $this->morphMany(Upload::class, 'uploadable');
    }

    /**
     * The 'uploads' mutatators for storing multiple uploaded file (without type)
     *
     * $user->uploads = $request->photos;
     * $user->uploads = $request->file('photo');
     */
    public function setUploadsAttribute($value)
    {
        $path = Str::snake((new \ReflectionClass($this))->getShortName());

        $files = (array) $value;
        $uploads = [];
        foreach($files as $file) {
            $uploads[] = Upload::make($file, $path);
        }
        $this->addToVirtualAttribute('uploads', $uploads);
    }

    /**
     * Get uploaded file via accessor
     *
     * $photo = $user->upload_photo
     *
     * @return mixed
     */
    protected function getVirtualAttributeValue($key)
    {
        return $this->uploads()->ofType($key)->orderBy('created_at', 'desc')->first();
    }

    /**
     * Set uploaded file via mutator
     *
     * $user->upload_photo = $request->file('photo');
     */
    protected function setVirtualAttributeValue($key, $value)
    {
        if (isset($this->virtualAttributes[$key])) {
            
            $this->virtualAttributes[$key]->file = $value;

        } else {

            if ($this->exists) {
                if ($current = $this->uploads()->ofType($key)->orderBy('created_at', 'desc')->first()) {
                    $current->file = $value;
                    $this->virtualAttributes[$key] = $current;
                    return;
                }
            }

            $path = Str::snake((new \ReflectionClass($this))->getShortName());
            $this->virtualAttributes[$key] = Upload::make($value, $path, $key);
        }
    }

     /**
     * Delete all associated Upload records for this entity (also delete file from the storage)
     *
     * @return int
     */
    public function deleteUploads()
    {
        $count = 0;
        foreach($this->uploads()->get() as $upload) {
            if ($upload->delete()) {
                $count++;
            }
        }
        return $count;
    }
}
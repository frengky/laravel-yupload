<?php

namespace Frengky\Yupload\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

use Frengky\Yupload\Upload;

use Storage;

/**
 * Trait for entity that their uploads are maintained by us
 *
 * @mixin \Illuminate\Database\Eloquent\Concerns\HasRelationships
 */
trait HasUploads
{
    /**
     * Register deleted event on the entity to
     * delete all uploads when entity has been deleted
     */
    protected static function bootHasUploads()
    {
        static::deleted(function ($entity) {
            /** @var HasUploads $entity */
            if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($entity)) &&
                !$entity->isForceDeleting()) {
                return;
            }
            $entity->deleteUploads();
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
     * The mutatators for storing multiple uploaded file (without type)
     *
     * $user->uploads = $request->photos;
     * $user->uploads = $request->file('photo');
     *
     * @param mixed $value
     */
    public function setUploadsAttribute($value)
    {
        $this->uploadManyAs(null, $value);
    }

    /**
     * The accessors for getting all uploaded files
     *
     * @param mixed $value
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUploadsAttribute($value)
    {
        return $this->uploads()->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get the entity's single uploads
     *
     * $userUpload = $user->upload
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function upload()
    {
        return $this->morphOne(Upload::class, 'uploadable');
    }

    /**
     * The mutatators for store or update single uploaded file (without type)
     * If multiple uploaded files are found, assuming the latest one
     *
     * $user->upload = $request->file('photo');
     *
     * @param mixed $value
     */
    public function setUploadAttribute($value)
    {
        if ($value instanceof UploadedFile) {
            try {
                $uploadedFile = $this->uploadedFileAsArray($value);
                $current = $this->uploads()->ofType(null)->orderBy('created_at', 'desc')->first();
                if ($current) {
                    $current->update($uploadedFile);
                } else {
                    $this->upload()->create($uploadedFile);
                }
            } catch (UploadException $e) {
                report($e);
                return;
            }
        }
    }

    /**
     * The accessors for getting single uploaded files
     *
     * @param $value
     * @return Upload|null
     */
    public function getUploadAttribute($value)
    {
        return $this->upload()->first();
    }

    /**
     * Create and return an Upload instance for single uploaded file (with type)
     *
     * @param string|null $type
     * @param UploadedFile|array $file
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function uploadAs($type, UploadedFile $file)
    {
        $type = empty($type) ? null : snake_case(strtolower($type));

        try {
            return $this->uploads()->create(
                $this->uploadedFileAsArray($file, $type)
            );
        } catch (UploadException $e) {
            report($e);
            return null;
        }
    }

    /**
     * Create and return collections of Upload for many uploaded file (with type)
     *
     * @param string|null $type
     * @param array $file
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function uploadManyAs($type, $file)
    {
        $type = empty($type) ? null : snake_case(strtolower($type));
        $files = is_array($file) ? $file : [ $file ];

        $uploads = [];
        foreach ($files as $uploadedFile) {
            if ($uploadedFile instanceof UploadedFile) {
                try {
                    $uploads[] = $this->uploadedFileAsArray($uploadedFile, $type);
                } catch (UploadException $e) {
                    // Report the exception but continue the request
                    report($e);
                    continue;
                }
            }
        }

        return $this->uploads()->createMany($uploads);
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

    /**
     * Store the uploaded file and return array value for model's create arguments
     *
     * @param UploadedFile $file
     * @param string $type
     * @return array
     *
     * @throws UploadException
     */
    protected function uploadedFileAsArray(UploadedFile $file, $type = null)
    {
        if (! $file->isValid())
            throw new UploadException('This uploaded file is not valid!');

        $path = snake_case((new \ReflectionClass($this))->getShortName());

        $ext = $file->getClientOriginalExtension();
        $filename = Str::random(40) . ( $ext ? ".$ext" : '' );
        $fullpath = Storage::disk(config('yupload.storage_disk'))->putFileAs($path, $file, $filename);

        if (empty($fullpath))
            throw new UploadException('Unable to store the uploaded file');

        return [
            'mimetype' => $file->getClientMimeType(),
            'name' => $file->getClientOriginalName(),
            'path' => $path . '/' . $filename,
            'size' => $file->getSize(),
            'type' => $type
        ];
    }

    /**
     * Override get an attribute from the model.
     * This will allow you to quickly retrieve single Upload based by type using accessor
     *
     * $userPhoto = $user->upload_photo;
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if ($type = $this->getUploadTypeFromAttribute($key)) {
            return $this->uploads()->ofType($type)->orderBy('created_at', 'desc')->first();
        }
        return parent::getAttribute($key);
    }

    /**
     * Override set a given attribute on the model.
     * This will allow you to create or update Upload based by type using mutator
     * If multiple records found, asumming the latest one
     *
     * $user->upload_photo = $request->file('photo');
     * $user->upload_wallpaper = $request->file('wallpaper');
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    public function setAttribute($key, $value)
    {
        if ($type = $this->getUploadTypeFromAttribute($key)) {
            if ($value instanceof UploadedFile) {
                try {
                    $current = $this->uploads()->ofType($type)->orderBy('created_at', 'desc')->first();
                    if ($current) {
                        $current->update($this->uploadedFileAsArray($value, $type));
                    } else {
                        $this->uploadAs($type, $value);
                    }
                } catch (UploadException $e) {
                    report($e);
                    return $this;
                }
            }
            return $this;
        }
        return parent::setAttribute($key, $value);
    }

    /**
     * Get the upload type value from attribute
     *
     * @param string $attribute
     * @return string
     */
    protected function getUploadTypeFromAttribute($attribute)
    {
        $prefix = 'upload_';
        if (strlen($attribute) > strlen($prefix) && strpos($attribute, $prefix) === 0) {
            return substr($attribute, strlen($prefix), strlen($attribute));
        }
        return '';
    }

    /**
     * Override entity Create method to process upload_ attributes if found
     *
     * @param array $attributes
     * @return mixed

    public static function create(array $attributes = [])
    {
        $model = static::query()->create($attributes);
        if (! empty($model)) {
            $model->uploadFromAttributes($attributes);
        }
        return $model;
    }*/

    /**
     * Update the model in the database.
     *
     * @param  array  $attributes
     * @param  array  $options
     * @return bool
     */
    public function update(array $attributes = [], array $options = [])
    {
        $result = parent::update($attributes, $options);

        $this->uploadFromAttributes($attributes);

        return $result;
    }

    /**
     * Look for upload_* in attributes and execute if found
     */
    public function uploadFromAttributes(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            if ($type = $this->getUploadTypeFromAttribute($key)) {
                $attribute = 'upload_'.$type;
                $this->$attribute = $value;
            } else {
                switch ($key) {
                    case 'upload':
                        $this->upload = $value;
                        break;
                    case 'uploads':
                        $this->uploads = $value;
                        break;
                }
            }
        }
    }
}
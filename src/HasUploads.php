<?php

namespace Frengky\Yupload;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Trait for entity that their uploads are maintained by us
 *
 * @mixin \Illuminate\Database\Eloquent\Concerns\HasRelationships
 */
trait HasUploads
{
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
                $current = $this->uploadsFor(null)->orderBy('created_at', 'desc')->first();
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
     * Get the Upload query based by type value
     *
     * @param string|null $type
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function uploadsFor($type = null)
    {
        if (empty($type)) {
            return $this->uploads()->whereNull('type');
        }

        return $this->uploads()->whereIn('type', is_array($type) ? $type : [$type]);
    }

    /**
     * Delete all associated Upload records for this entity (also delete file from the storage)
     */
    public function deleteUploads()
    {
        collect($this->uploads()->get())->each(function($upload) {
            $upload->delete();
        });
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
        $fullpath = Upload::storage()->putFileAs($path, $file, $filename);

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
            return $this->uploadsFor($type)->orderBy('created_at', 'desc')->first();
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
                    $current = $this->uploadsFor($type)->orderBy('created_at', 'desc')->first();
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
     * Override perform the actual delete query on this model instance.
     *
     * @return void
     */
    protected function performDeleteOnModel()
    {
        parent::performDeleteOnModel();

        // Delete all left over uploads
        $this->deleteUploads();
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
}
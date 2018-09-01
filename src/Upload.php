<?php

namespace Frengky\Yupload;

use Illuminate\Database\Eloquent\Model;

/**
 * Model for handling file upload
 *
 * @property string $id
 * @property string $uploadable_type
 * @property int $uploadable_id
 * @property string $mimetype
 * @property string $name
 * @property string $path
 * @property int $size
 * @property string|null $type
 *
 * @property-read \Illuminate\Database\Eloquent\Relations\MorphTo $uploadable
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @mixin \Eloquent
 */
class Upload extends Model
{
    use Uploadables;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'uploads';

    /**
     * The guarded attributes on the model.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [ 'uploadable_type', 'uploadable_id' ];
}
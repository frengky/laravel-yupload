<?php

namespace Frengky\Yupload\Tests\Model;

use Illuminate\Database\Eloquent\Model;
use Frengky\Yupload\Concerns\HasUploads;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
    use SoftDeletes, HasUploads;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at', 'deleted_at'
    ];
}
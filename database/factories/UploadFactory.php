<?php

use Faker\Generator as Faker;
use Frengky\Yupload\Upload;
use Illuminate\Http\File;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(Upload::class, function (Faker $faker) {

    $image = $faker->image(null, 640, 480, 'people');
    $file = new File($image);
    $ext = $file->getExtension();
    $filename = Str::random(40) . ".$ext";

    $path = config('yupload.tmp_path');
    $fullpath = Upload::storage()->putFileAs($path, $file, $filename);

    return [
        'id' => $faker->uuid,
        'mimetype' => $file->getMimeType(),
        'name' => $faker->sentence(2) . ".$ext",
        'path' => $path . '/' . $filename,
        'size' => $file->getSize()
    ];
});

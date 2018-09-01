# Laravel Yupload

Laravel package for easy file uploads maintenance

What this packages do?

- Manages file uploads related to a model
- Uploaded file are stored using `Storage`, no need path config, respecting your `config/filesystem.php`
- Dont need to create extra database tables/fields, thanks to dynamic `upload_*` mutator and accessor.
- Store multiple files via `uploads` mutator and accessor
- Any uploaded files are maintained, its deleted and replaced automatically on updates. 

## Installation
Install the package via `Composer`
```
composer require frengky/yupload
```
Then publish the configuration files to your `app/config`
```
php artisan vendor:publish --tag=config
```
Finally, run migrations to create the `uploads` table
```
php artisan migrate
```

## Usage

For each of your model that have file uploads, use the `HasUploads` traits
```php
use Frengky\Yupload\HasUploads;

class User extends Authenticatable
{
    use HasUploads;
    //
}

class Product extends Model
{
    use HasUploads;
    //
}
```


## Some examples

Saving uploaded file via `uploads` and `upload_` mutators
```php

class ProfileController extends Controller
{
    public function saveProfile(Request $request)
    {
        $user = User::find(1);
        
        // Store single file
        $user->upload_photo = $request->file('photo');
        
        // Update, the previous file will be replaced
        $user->upload_photo = $request->file('anotherphoto');
        
        // Store multiple files
        $user->uploads = $request->files;
        $user->uploads = $request->file('anotherfile1');
        $user->uploads = $request->file('anotherfile2');
    }
    
    public function downloadPhoto()
    {
        // Force downloading the file
        return User::find(1)->upload_photo->download();
    }
}

```

Acessing uploaded file via `uploads` and `upload_` accessor
```php
$onlyPhoto = $product->upload_image1;
if ($onlyPhoto->isImage()) {
   // do something
}

// Getting full url to the uploaded file
$photoUrl = (string) $product->upload_image1;

// All uploaded file for this entity
$allFiles = $product->uploads;
```

Deleting uploaded file

```php
// Delete single uploaded file
$userPhoto = $user->upload_photo;
$userPhoto->delete();

// Delete all uploaded file related to this entity
$user->deleteUploads();

// Deleting the entity also delete all related uploaded file
$user->delete();
```
> If your model use **SoftDeletes**, then the uploaded file will be preserved, only deleted on forceDelete();

That's all for now.
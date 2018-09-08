# Laravel Yupload

Laravel package for easy file uploads maintenance. It helps to maintain file upload on each of your model.

What this packages do?

- Manages file uploads related to a model
- Uploaded file are stored using `Storage`, no need path config, respecting your `config/filesystem.php`
- Create extra database tables/fields is not required, thanks to dynamic `upload_*` mutator and accessor.
- Store multiple files via `uploads` mutator and accessor
- Any uploaded files are automatically maintained, its deleted and replaced on updates. 

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

## Applying on your model

For each of your model that have file uploads, use the `HasUploads` traits
```php
use Frengky\Yupload\Concerns\HasUploads;

class User extends Authenticatable
{
    use HasUploads;
    // store uploaded files under 'user/' path
}

class Product extends Model
{
    use HasUploads;
    // store upload under 'product/' path
}

```
> The physical file will be stored using `Storage` to your desired disk. 
It will using your model name (in lowercase) as path.

## Storing uploaded file
Saving uploaded file via `uploads` and `upload_*` mutators

```php
$user = User::find(1);

// Store uploaded file via dynamic mutator
$user->upload_photo = $request->file('photo');
$user->upload_screenshot = $request->file('screenshot');
$user->uploads = $request->file('all_documents');

// via create
$newUser = User::create([
    'name' => 'Foo',
    'email' => 'foo@example.com',
    'upload_photo' => $request->file('photo')
]);

// store or update via fill
$user->fill([
    'upload_photo' => $request->file('photo')
]);

// store via relationship
$user->uploads()->save(
    Upload::make($request->file('photo'))
);

// Store multiple files via predefined 'uploads' mutators
$user->uploads = $request->files;
$user->uploads = $request->file('anotherfile1');
$user->uploads = $request->file('anotherfile2');

// Finally call save() your model as usual to save the related uploaded files
$user->save();
```
> All `upload_*` prefixed attributes are virtual mutator, creating database fields for each upload type is not needed.
The `uploads` attribute are predefined mutator to store multiple files.

## Accessing uploaded file
Acessing uploaded file via `uploads` and `upload_*` accessor
```php
$product = Product::find(1);

// via accessor
$photo = $product->upload_photo;

// via relationship
$photo = $product->uploads()->ofType('photo');

// All uploaded file for this entity
$allFiles = $product->uploads;
```
## Update/replace uploaded file
```php
$product = Product::find(1);

// via accessor
$product->upload_photo = $request->file('photo');
$product->save();

// via 'Upload' model
$photo = $product->uploads()->ofType('photo');
$photo->file = $request->file('photo');
$photo->save();
```
> The previous physical file will be automatically deleted on save()

## Deleting uploaded file

```php
// Delete single uploaded file
$photo = $user->upload_photo;
$photo->delete();

// Delete all uploaded file related to this entity
$user->deleteUploads();

// Deleting the entity also delete all related uploaded file
$user->delete();
```
> If your model use **SoftDeletes**, then the uploaded file will be preserved, only deleted on forceDelete();

That's all for now.
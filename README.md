# Laravel Yupload

Laravel package for easy file uploads maintenance

## Installation
```
composer require frengky/yupload
```

## Usage
Configure up your model that have file uploads operations
```
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
What this packages do?

- Uploaded files are stored using Laravel's Storage (Default disks: public)
- You dont have to create additional database tables/fields
- The 'upload_*' mutators and accessors is unlimited.
- Attach 'HasUploads' in any models and stop worrying about storing upload files. 

Example:
```

class ProfileController extends Controller
{
    public function saveProfile(Request $request)
    {
        $user = User::find(1);
        
        // Store uploaded photo for this model
        $user->upload_photo = $request->file('photo');
        
        // Update, the previous file will be replaced
        $user->upload_photo = $request->file('anotherphoto');
        
        // Store multiple attachments
        $user->uploads = $request->files;
        
        $product = Product::find(1);
        $product->upload_image1 = $request->file('image1'); 
        $product->uploads = $request->images;
    }
}

```
More detailed guide coming soon.
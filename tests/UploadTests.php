<?php

namespace Frengky\Yupload\Tests;

use Frengky\Yupload\Events\UploadCompleted;
use Frengky\Yupload\Tests\Model\User;

use Frengky\Yupload\Upload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadTests extends TestCase
{
    /**
     * Setup the test environment
     */
    protected function setUp()
    {
       parent::setUp();

       // Setup fake storage disk for testing
       Storage::fake('testing');

       // Create test user
       factory(User::class, 3)->create();
    }

    public function testUploadByCreate()
    {
        $this->assertDatabaseMissing('uploads', [ 'name' => 'photo.jpg' ]);
        $this->assertDatabaseMissing('uploads', [ 'name' => 'one.jpg' ]);
        $this->assertDatabaseMissing('uploads', [ 'name' => 'two.jpg' ]);

        $user = User::create([
            'name' => 'Test',
            'email' => 'test',
            'password' => 'test',
            'upload_photo' => UploadedFile::fake()->image('photo.jpg'),
            'uploads' => [
                UploadedFile::fake()->image('one.jpg'),
                UploadedFile::fake()->image('two.jpg')
            ]
        ]);

        // Begins common check
        $this->assertCount(3, $user->uploads);
        $this->assertCount(1, $user->uploads()->ofType('photo')->get());
        $this->assertCount(2, $user->uploads()->ofType(null)->get());

        $photo = $user->upload_photo;
        $this->assertNotEmpty($photo);
        $this->assertDatabaseHas('uploads', [ 'uploadable_id' => $user->id, 'name' => $photo->name ]);
        $this->assertNotEmpty($photo->path);
        Storage::disk('testing')->assertExists($photo->path);

        $photo->delete();
        $this->assertDatabaseMissing('uploads', [ 'name' => $photo->name ]);
        Storage::disk('testing')->assertMissing($photo->path);

        $user->deleteUploads();
        foreach($user->uploads as $upload) {
            $this->assertDatabaseMissing('uploads', [ 'name' => $upload->name ]);
            Storage::disk('testing')->assertMissing($upload->path);
        }
    }

    public function testUploadByAccessorAndMutators()
    {
        $user = User::find(1);
        $user->upload_photo = UploadedFile::fake()->image('photo.jpg');
        $user->uploads = [
            UploadedFile::fake()->image('one.jpg'),
            UploadedFile::fake()->image('two.jpg')
        ];

        $this->assertDatabaseMissing('uploads', [ 'name' => 'photo.jpg' ]);
        $this->assertDatabaseMissing('uploads', [ 'name' => 'one.jpg' ]);
        $this->assertDatabaseMissing('uploads', [ 'name' => 'two.jpg' ]);

        $user->save();

        // Begins common check
        $this->assertCount(3, $user->uploads);
        $this->assertCount(1, $user->uploads()->ofType('photo')->get());
        $this->assertCount(2, $user->uploads()->ofType(null)->get());

        $photo = $user->upload_photo;
        $this->assertNotEmpty($photo);
        $this->assertDatabaseHas('uploads', [ 'uploadable_id' => $user->id, 'name' => $photo->name ]);
        $this->assertNotEmpty($photo->path);
        Storage::disk('testing')->assertExists($photo->path);

        $photo->delete();
        $this->assertDatabaseMissing('uploads', [ 'name' => $photo->name ]);
        Storage::disk('testing')->assertMissing($photo->path);

        $user->deleteUploads();
        foreach($user->uploads as $upload) {
            $this->assertDatabaseMissing('uploads', [ 'name' => $upload->name ]);
            Storage::disk('testing')->assertMissing($upload->path);
        }
    }


    public function testUploadUpdating()
    {
        $user = User::find(1);
        $user->fill([
            'upload_photo' => UploadedFile::fake()->image('photo.jpg'),
            'uploads' => [
                UploadedFile::fake()->image('one.jpg'),
                UploadedFile::fake()->image('two.jpg')
            ]
        ]);

        $this->assertDatabaseMissing('uploads', [ 'name' => 'photo.jpg' ]);
        $this->assertDatabaseMissing('uploads', [ 'name' => 'one.jpg' ]);
        $this->assertDatabaseMissing('uploads', [ 'name' => 'two.jpg' ]);

        $user->save();

        $photo = $user->upload_photo;
        $this->assertNotEmpty($photo);
        $this->assertDatabaseHas('uploads', [ 'uploadable_id' => $user->id, 'name' => $photo->name ]);
        $this->assertNotEmpty($photo->path);
        Storage::disk('testing')->assertExists($photo->path);

        $user->upload_photo = UploadedFile::fake()->image('photo2.jpg');
        $user->save();

        $newPhoto = $user->upload_photo;
        $this->assertNotEmpty($newPhoto);
        $this->assertDatabaseHas('uploads', [ 'uploadable_id' => $user->id, 'name' => 'photo2.jpg' ]);
        $this->assertNotEmpty($newPhoto->path);
        Storage::disk('testing')->assertExists($newPhoto->path);

        $this->assertDatabaseMissing('uploads', [ 'uploadable_id' => $user->id, 'name' => 'photo.jpg' ]);
        Storage::disk('testing')->assertMissing($photo->path);

        // Begins common check
        $this->assertCount(3, $user->uploads);
        $this->assertCount(1, $user->uploads()->ofType('photo')->get());
        $this->assertCount(2, $user->uploads()->ofType(null)->get());

        $newPhoto->delete();
        $this->assertDatabaseMissing('uploads', [ 'name' => $newPhoto->name ]);
        Storage::disk('testing')->assertMissing($newPhoto->path);

        $user->deleteUploads();
        foreach($user->uploads as $upload) {
            $this->assertDatabaseMissing('uploads', [ 'name' => $upload->name ]);
            Storage::disk('testing')->assertMissing($upload->path);
        }
    }

    public function testEntitySoftDeletes()
    {
        $this->expectsEvents([
            UploadCompleted::class
        ]);

        $user = User::find(3);
        $user->upload_picture = UploadedFile::fake()->image('picture.jpg');
        $user->save();

        $picture = $user->upload_picture;

        $this->assertTrue($picture->isImage());
        $this->assertNotEmpty((string) $picture);

        $user->delete(); // Soft delete
        $this->assertDatabaseHas('uploads', ['name' => $picture->name]);
        Storage::disk('testing')->assertExists($picture->path);

        $user->forceDelete();
        $this->assertDatabaseMissing('uploads', ['name' => $picture->name]);
        Storage::disk('testing')->assertMissing($picture->path);
    }
}
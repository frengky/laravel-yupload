<?php

namespace Frengky\Yupload\Tests;

use Frengky\Yupload\Tests\Model\User;

use Frengky\Yupload\Upload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadTests extends TestCase
{
    /** @var User */
    protected $user;

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
       $this->user = User::find(1);
    }

    /**
     * Test uploaded file should be saved, stored and deleted correctly
     */
    public function testSingleUploadAndDeleting()
    {
        $this->user->upload = UploadedFile::fake()->image('photo.jpg');

        $photo = $this->user->upload;
        $this->assertNotEmpty($photo);
        $this->assertDatabaseHas('uploads', [
            'uploadable_id' => $this->user->id,
            'name' => 'photo.jpg'
        ]);
        Storage::disk('testing')->assertExists($photo->path);

        $photo->delete();
        $this->assertDatabaseMissing('uploads', [ 'name' => 'photo.jpg' ]);
        Storage::disk('testing')->assertMissing($photo->path);

        $this->assertEmpty($this->user->upload);
    }

    /**
     * Test upload_* mutator accessor, old uploaded file and records should be removed on updates
     */
    public function testSingleMutatorAccessorUpdate()
    {
        $this->user->upload_selfie = UploadedFile::fake()->image('selfie1.jpg');
        $old = $this->user->upload_selfie;
        $this->assertNotEmpty($old);

        $this->user->upload_selfie = UploadedFile::fake()->image('selfie2.jpg');
        $new = $this->user->upload_selfie;
        $this->assertNotEmpty($new);

        $this->assertDatabaseMissing('uploads', [ 'name' => $old->name ]);
        Storage::disk('testing')->assertMissing($old->path);

        $this->assertDatabaseHas('uploads', [ 'name' => $new->name ]);
        Storage::disk('testing')->assertExists($new->path);

        $new->delete();
        $this->assertDatabaseMissing('uploads', [ 'name' => $new->name ]);
        Storage::disk('testing')->assertMissing($new->path);
    }

    /**
     * Test multiple uploaded file should be saved, stored and deleted correctly
     */
    public function testMultipleUploadAndDeleting()
    {
        $user = User::find(2);
        $user->upload_picture = UploadedFile::fake()->image('picture.jpg');
        $picture = $user->upload_picture;

        $user->uploads = UploadedFile::fake()->image('image1.jpg');
        $user->uploads = [
            UploadedFile::fake()->image('image2.jpg'),
            UploadedFile::fake()->image('image3.jpg')
        ];

        $this->assertNotEmpty($picture);

        $all = $user->uploads;
        $this->assertCount(4, $all);

        foreach($all as $each) {
            $this->assertDatabaseHas('uploads', [ 'name' => $each->name ]);
            Storage::disk('testing')->assertExists($each->path);
        }

        // Delete everything
        $user->forceDelete();
        $this->assertDatabaseMissing('users', [ 'id' => 2 ]);
        $this->assertEmpty($user->upload_picture);
        $this->assertEmpty($user->uploads);
        $this->assertCount(0, $user->uploads);

        $this->assertDatabaseMissing('uploads', [ 'name' => $picture->name ]);
        Storage::disk('testing')->assertMissing($picture->path);

        foreach($all as $each) {
            $this->assertDatabaseMissing('uploads', [ 'name' => $each->name ]);
            Storage::disk('testing')->assertMissing($each->path);
        }
    }

    public function testEntitySoftDeletes()
    {
        $user = User::find(3);
        $user->upload_picture = UploadedFile::fake()->image('picture.jpg');
        $picture = $user->upload_picture;

        $user->delete(); // Soft delete
        $this->assertDatabaseHas('uploads', ['name' => $picture->name]);
        Storage::disk('testing')->assertExists($picture->path);

        $user->forceDelete();
        $this->assertDatabaseMissing('uploads', ['name' => $picture->name]);
        Storage::disk('testing')->assertMissing($picture->path);
    }
}
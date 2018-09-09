<?php

namespace Frengky\Yupload\Events;

use Illuminate\Queue\SerializesModels;
use Frengky\Yupload\Upload;

class UploadEvent
{
    use SerializesModels;

    /** @var Upload */
    protected $upload;

    /** @var string */
    protected $storagePath;

    /**
     * Create a new event instance.
     *
     * @param Upload $upload
     */
    public function __construct(Upload $upload, $storagePath)
    {
        $this->upload = $upload;
        $this->storagePath = $storagePath;
    }

    /**
     * Get Upload instance for this event
     *
     * @return Upload
     */
    public function getUpload()
    {
        return $this->upload;
    }

    /**
     * Get storage path for this Upload event
     *
     * @return string
     */
    public function getStoragePath()
    {
        return $this->storagePath;
    }
}
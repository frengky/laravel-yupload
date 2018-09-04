<?php

return [

    /**
     * All uploaded files will be put into the following storage disk
     */
    'storage_disk'   =>  env('UPLOAD_STORAGE_DISK', 'public'),

    /**
     * Temporary path name relative to storage_disk
     */
    'tmp_path'       =>  'tmp'
];
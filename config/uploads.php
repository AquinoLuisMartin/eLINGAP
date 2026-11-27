<?php

return [
    'optimize_images' => env('UPLOAD_OPTIMIZE_IMAGES', true),
    'profile_photo_disk' => env('PROFILE_PHOTO_DISK', 'local'),
    'senior_document_disk' => env('SENIOR_DOCUMENT_DISK', 'local'),
    'senior_document_bucket' => env('SENIOR_DOCUMENT_BUCKET'),
];

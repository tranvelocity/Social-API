<?php

return [
    'name' => 'Media',
    'supported_video_extensions' => ['mp4', 'mov', 'wmv', 'wav'],
    'supported_image_extensions' => ['apng', 'png', 'jpeg', 'jpg'],
    'image_upload_size_limit' => 10240, // 10MB
    'video_upload_size_limit' => 1024000, // 1G
    's3_media_directory' => 'medias',
    's3_image_directory' => 'images',
    's3_video_directory' => 'videos',
    's3_thumbnail_directory' => 'thumbnails',
];

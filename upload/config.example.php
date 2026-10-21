<?php
// Copy to config.php on the server. Never commit credentials.
return [
 'app_url' => 'https://app.example.de', // No trailing slash; subdirectory e.g. https://example.de/vereinsapp is supported.
 'database' => ['host'=>'MYSQL-HOST-FROM-STRATO','port'=>3306,'name'=>'DATABASE','user'=>'DATABASE-USER','password'=>''],
 // Use an absolute writable directory OUTSIDE the web root if possible.
 'storage_path' => __DIR__.'/var',
 // Generate a unique secret of at least 32 characters; clear after installation.
 'setup_token' => '',
 'club_name' => 'VfL Ramsdorf',
 'legal_url' => '', // Full HTTPS URL to your confirmed imprint.
 'privacy_url' => '', // Full HTTPS URL to your privacy notice for this app.
];

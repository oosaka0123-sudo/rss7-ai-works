<?php
/**
 * Copy this file to config.local.php on the server and replace the hash.
 * Generate it once with: php -r "echo password_hash('NEW PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"
 * config.local.php is ignored by Git and must never be committed.
 */
return [
    'users' => [
        'mako' => [
            'name' => 'MAKO',
            'password_hash' => '$2y$10$replace.with.a.real.password.hash',
        ],
    ],
    'max_upload_bytes' => 5 * 1024 * 1024,
];

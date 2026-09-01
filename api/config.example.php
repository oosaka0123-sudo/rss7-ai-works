<?php
/**
 * Copy this file to config.local.php on the server and replace the hash.
 * Generate it once with: php -r "echo password_hash('NEW PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"
 * config.local.php is ignored by Git and must never be committed.
 */
return [
    // Replace this with a writable absolute path outside every public_html/document root.
    'private_data_dir' => '/absolute/path/outside/public_html/rss7-private',
    'contact_recipient' => 'info@rss7.net',
    'contact_sender' => 'info@rss7.net',
    // Generate with: php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
    'contact_rate_secret' => 'replace-with-a-random-64-character-hex-value',
    'users' => [
        'mako' => [
            'name' => 'MAKO',
            'password_hash' => '$2y$10$replace.with.a.real.password.hash',
        ],
    ],
    'max_upload_bytes' => 5 * 1024 * 1024,
];

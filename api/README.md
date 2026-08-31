# RSS7 blog API setup

The API source contains no passwords or API keys.

1. Copy `config.example.php` to `config.local.php` on the production server.
2. Generate a new password hash with PHP's `password_hash()` and put only the hash in `config.local.php`.
3. Keep `config.local.php` out of Git. It is already listed in `.gitignore` and rejected by the quality checker.
4. Serve the admin page and API from the same HTTPS origin. Cross-origin requests are intentionally unsupported.
5. Make `data/articles.json` and `images/blog/` writable by the PHP process, without granting broader permissions than required.

The original plaintext passwords supplied from the legacy server must not be reused.

# RSS7 blog API setup

The API source contains no passwords or API keys.

1. Copy `config.example.php` to `config.local.php` on the production server.
2. Generate a new password hash with PHP's `password_hash()` and put only the hash in `config.local.php`.
3. Keep `config.local.php` out of Git. It is already listed in `.gitignore` and rejected by the quality checker.
4. Serve the admin page and API from the same HTTPS origin. Cross-origin requests are intentionally unsupported.
5. Replace the example `private_data_dir` with a writable absolute path outside every `public_html`/document root. Drafts and the complete article database are stored only there.
6. Make `images/blog/` writable by the PHP process, without granting broader permissions than required. The private data directory itself must also be writable so atomic rename can succeed.
7. Confirm that requesting `data/articles.json` on the production host returns HTTP 403. Apache uses `data/.htaccess` for this; GitHub Pages ignores it so the committed published-only snapshot remains available for static preview.

The original plaintext passwords supplied from the legacy server must not be reused.

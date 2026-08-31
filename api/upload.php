<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

const UPLOAD_DIR = __DIR__ . '/../images/blog/';
const PUBLIC_PREFIX = 'images/blog/';
const ALLOWED_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
];

require_method('GET', 'POST');
require_admin();

if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true)) {
    json_response(['success' => false, 'message' => '画像フォルダを作成できません'], 500);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    $images = [];
    foreach (glob(UPLOAD_DIR . '*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE) ?: [] as $path) {
        if (!is_file($path)) continue;
        $name = basename($path);
        $images[] = ['name' => $name, 'url' => PUBLIC_PREFIX . rawurlencode($name), 'size' => filesize($path)];
    }
    usort($images, static fn($a, $b) => strcmp($b['name'], $a['name']));
    json_response(['success' => true, 'images' => $images]);
}

require_csrf();
if (($_GET['action'] ?? '') === 'delete') {
    $input = request_json();
    $name = basename((string)($input['name'] ?? ''));
    if (!preg_match('/^[a-zA-Z0-9._~-]+\.(jpg|jpeg|png|webp|gif)$/', $name)) {
        json_response(['success' => false, 'message' => 'ファイル名が不正です'], 400);
    }
    $path = UPLOAD_DIR . $name;
    if (!is_file($path) || !unlink($path)) json_response(['success' => false, 'message' => '削除できません'], 404);
    json_response(['success' => true, 'message' => '削除しました']);
}

if (!isset($_FILES['image']) || !is_array($_FILES['image'])) json_response(['success' => false, 'message' => '画像がありません'], 400);
$file = $_FILES['image'];
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) json_response(['success' => false, 'message' => 'アップロードに失敗しました'], 400);
$config = local_config();
$maxBytes = (int)($config['max_upload_bytes'] ?? 5 * 1024 * 1024);
if (($file['size'] ?? 0) < 1 || $file['size'] > $maxBytes) json_response(['success' => false, 'message' => '画像サイズが上限を超えています'], 413);

$tmp = (string)$file['tmp_name'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = (string)$finfo->file($tmp);
$dimensions = @getimagesize($tmp);
if (!isset(ALLOWED_TYPES[$mime]) || $dimensions === false) json_response(['success' => false, 'message' => '対応していない画像形式です'], 415);
[$width, $height] = $dimensions;
if ($width < 1 || $height < 1 || $width > 12000 || $height > 12000 || ($width * $height) > 40000000) {
    json_response(['success' => false, 'message' => '画像の寸法が上限を超えています'], 413);
}
$extension = ALLOWED_TYPES[$mime];
$name = bin2hex(random_bytes(16)) . '.' . $extension;
if (!move_uploaded_file($tmp, UPLOAD_DIR . $name)) json_response(['success' => false, 'message' => '画像を保存できません'], 500);
json_response(['success' => true, 'message' => 'アップロードしました', 'name' => $name, 'url' => PUBLIC_PREFIX . $name]);

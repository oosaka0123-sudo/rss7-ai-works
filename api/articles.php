<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

const PUBLIC_ARTICLES_FILE = __DIR__ . '/../data/articles.json';

function read_article_file(string $path): array
{
    if (!is_file($path)) json_response(['success' => false, 'message' => '記事データがありません'], 500);
    $contents = file_get_contents($path);
    $decoded = $contents === false ? null : json_decode($contents, true);
    if (!is_array($decoded) || !isset($decoded['articles']) || !is_array($decoded['articles'])) {
        json_response(['success' => false, 'message' => '記事データが破損しています'], 500);
    }
    return $decoded;
}

function private_articles_file(): string
{
    $config = local_config();
    $directory = (string)($config['private_data_dir'] ?? '');
    if ($directory === '' || $directory[0] !== DIRECTORY_SEPARATOR) {
        json_response(['success' => false, 'message' => '非公開データ保存先が未設定です'], 503);
    }
    if (!is_dir($directory) && !mkdir($directory, 0700, true)) {
        json_response(['success' => false, 'message' => '非公開データ保存先を作成できません'], 500);
    }
    $resolvedDirectory = realpath($directory);
    $documentRoot = realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__)));
    if ($resolvedDirectory === false || $documentRoot === false
        || $resolvedDirectory === $documentRoot
        || strpos($resolvedDirectory . DIRECTORY_SEPARATOR, $documentRoot . DIRECTORY_SEPARATOR) === 0) {
        json_response(['success' => false, 'message' => '非公開データ保存先は公開領域の外に指定してください'], 500);
    }
    return $resolvedDirectory . DIRECTORY_SEPARATOR . 'articles.json';
}

function write_articles_atomic(string $path, array $data): void
{
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE);
    $temporary = $path . '.tmp.' . bin2hex(random_bytes(6));
    if ($json === false || file_put_contents($temporary, $json . "\n", LOCK_EX) === false || !rename($temporary, $path)) {
        if (is_file($temporary)) @unlink($temporary);
        json_response(['success' => false, 'message' => '記事データを保存できません'], 500);
    }
}

function published_articles(array $data): array
{
    return ['articles' => array_values(array_filter(
        $data['articles'] ?? [],
        static fn($article) => ($article['status'] ?? '') === 'published'
    ))];
}

function read_private_articles(): array
{
    $privateFile = private_articles_file();
    $lock = fopen($privateFile . '.lock', 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) json_response(['success' => false, 'message' => '記事データをロックできません'], 500);
    try {
        if (!is_file($privateFile)) write_articles_atomic($privateFile, read_article_file(PUBLIC_ARTICLES_FILE));
        return read_article_file($privateFile);
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function mutate_articles(callable $callback): array
{
    $privateFile = private_articles_file();
    $lock = fopen($privateFile . '.lock', 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) json_response(['success' => false, 'message' => '記事データをロックできません'], 500);
    try {
        if (!is_file($privateFile)) write_articles_atomic($privateFile, read_article_file(PUBLIC_ARTICLES_FILE));
        $db = read_article_file($privateFile);
        $result = $callback($db);
        write_articles_atomic($privateFile, $db);
        return $result;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function clean_text($value, int $max): string
{
    return mb_substr(trim((string)$value), 0, $max);
}

function clean_article_html(string $html): string
{
    if (!class_exists('DOMDocument')) {
        return nl2br(htmlspecialchars(strip_tags($html), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }
    $allowed = ['p', 'br', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'strong', 'em', 'b', 'i', 'blockquote', 'a', 'code', 'pre', 'hr'];
    $document = new DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="UTF-8"><div id="rss7-body">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $root = $document->getElementById('rss7-body');
    if (!$root) return '';
    $nodes = [];
    foreach ($root->getElementsByTagName('*') as $node) $nodes[] = $node;
    foreach (array_reverse($nodes) as $node) {
        $tag = strtolower($node->nodeName);
        if (!in_array($tag, $allowed, true)) {
            while ($node->firstChild) $node->parentNode->insertBefore($node->firstChild, $node);
            $node->parentNode->removeChild($node);
            continue;
        }
        $href = $tag === 'a' ? trim((string)$node->getAttribute('href')) : '';
        while ($node->attributes && $node->attributes->length) $node->removeAttributeNode($node->attributes->item(0));
        if ($tag === 'a' && preg_match('#^https?://#i', $href)) {
            $node->setAttribute('href', $href);
            $node->setAttribute('target', '_blank');
            $node->setAttribute('rel', 'noopener noreferrer');
        }
    }
    $output = '';
    foreach ($root->childNodes as $child) $output .= $document->saveHTML($child);
    return $output;
}

require_method('GET', 'POST');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    $status = (string)($_GET['status'] ?? 'published');
    if ($status === 'all') {
        require_admin();
        $db = read_private_articles();
        $articles = $db['articles'];
    } else {
        $articles = published_articles(read_private_articles())['articles'];
    }
    json_response(['success' => true, 'articles' => $articles]);
}

require_admin();
require_csrf();
$input = request_json();
$action = (string)($input['action'] ?? '');

if ($action === 'delete') {
    $id = clean_text($input['id'] ?? '', 80);
    if ($id === '') json_response(['success' => false, 'message' => '記事IDがありません'], 400);
    mutate_articles(static function (&$db) use ($id): array {
        $db['articles'] = array_values(array_filter($db['articles'], static fn($article) => (string)($article['id'] ?? '') !== $id));
        return [];
    });
    json_response(['success' => true, 'message' => '削除しました']);
}

if ($action !== 'save') json_response(['success' => false, 'message' => '操作が不正です'], 400);

$title = clean_text($input['title'] ?? '', 160);
$body = clean_article_html(trim((string)($input['body'] ?? '')));
if ($title === '' || $body === '' || mb_strlen($body) > 50000) {
    json_response(['success' => false, 'message' => 'タイトルまたは本文が不正です'], 400);
}
$categories = ['AI', 'WEB', 'VIDEO', 'SNS'];
$statuses = ['published', 'draft'];
$cat = in_array($input['cat'] ?? '', $categories, true) ? $input['cat'] : 'AI';
$status = in_array($input['status'] ?? '', $statuses, true) ? $input['status'] : 'draft';
$id = clean_text($input['id'] ?? '', 80) ?: bin2hex(random_bytes(12));
$article = mutate_articles(static function (&$db) use ($id, $title, $body, $cat, $status, $input): array {
    $existing = null;
    foreach ($db['articles'] as $candidate) if ((string)($candidate['id'] ?? '') === $id) { $existing = $candidate; break; }
    $article = [
        'id' => $id,
        'title' => $title,
        'body' => $body,
        'excerpt' => clean_text($input['excerpt'] ?? strip_tags($body), 120),
        'cat' => $cat,
        'status' => $status,
        'tag' => clean_text($input['tag'] ?? '', 60),
        'image' => clean_text($input['image'] ?? '', 500),
        'emoji' => clean_text($input['emoji'] ?? ($existing['emoji'] ?? '📝'), 8),
        'date' => clean_text($input['date'] ?? '', 20) ?: date('Y.m.d'),
        'updated' => date('Y-m-d H:i:s'),
    ];
    if (isset($existing['slug'])) $article['slug'] = clean_text($existing['slug'], 100);
    if (isset($existing['auto'])) $article['auto'] = (bool)$existing['auto'];
    $found = false;
    foreach ($db['articles'] as $index => $candidate) {
        if ((string)($candidate['id'] ?? '') === $id) { $db['articles'][$index] = $article; $found = true; break; }
    }
    if (!$found) array_unshift($db['articles'], $article);
    return $article;
});
json_response(['success' => true, 'message' => '保存しました', 'article' => $article]);

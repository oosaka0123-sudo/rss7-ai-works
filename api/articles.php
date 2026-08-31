<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

const ARTICLES_FILE = __DIR__ . '/../data/articles.json';

function read_articles(): array
{
    if (!is_file(ARTICLES_FILE)) return ['articles' => []];
    $decoded = json_decode((string)file_get_contents(ARTICLES_FILE), true);
    return is_array($decoded) && isset($decoded['articles']) && is_array($decoded['articles']) ? $decoded : ['articles' => []];
}

function write_articles_atomic(array $data): void
{
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE);
    $temporary = ARTICLES_FILE . '.tmp.' . bin2hex(random_bytes(6));
    if ($json === false || file_put_contents($temporary, $json . "\n", LOCK_EX) === false || !rename($temporary, ARTICLES_FILE)) {
        if (is_file($temporary)) @unlink($temporary);
        json_response(['success' => false, 'message' => '記事データを保存できません'], 500);
    }
}

function mutate_articles(callable $callback): array
{
    $lock = fopen(ARTICLES_FILE . '.lock', 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) json_response(['success' => false, 'message' => '記事データをロックできません'], 500);
    try {
        $db = read_articles();
        $result = $callback($db);
        write_articles_atomic($db);
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
    $db = read_articles();
    $status = (string)($_GET['status'] ?? 'published');
    if ($status === 'all') require_admin();
    $articles = $status === 'all'
        ? $db['articles']
        : array_values(array_filter($db['articles'], static fn($article) => ($article['status'] ?? '') === 'published'));
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

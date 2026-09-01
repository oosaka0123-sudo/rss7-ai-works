<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

const CONTACT_SERVICES = [
    'WEB制作',
    'アプリ開発',
    'AI動画CM制作',
    'AI導入コンサルティング',
    '自分史・終活ビデオ',
    'SNS運用代行',
    'AI楽曲制作',
    'LINE公式・メルマガ設定',
    'その他・複数サービス',
];

function contact_text($value, int $max): string
{
    return mb_substr(trim((string)$value), 0, $max);
}

function contact_header_text(string $value): string
{
    return trim(str_replace(["\r", "\n"], ' ', $value));
}

function contact_request_json(): array
{
    $declaredLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($declaredLength > 16384) json_response(['success' => false, 'message' => '送信内容が大きすぎます'], 413);
    $raw = file_get_contents('php://input', false, null, 0, 16385);
    if ($raw === false || strlen($raw) > 16384) json_response(['success' => false, 'message' => '送信内容が大きすぎます'], 413);
    $data = json_decode($raw, true);
    if (!is_array($data)) json_response(['success' => false, 'message' => 'JSONが不正です'], 400);
    return $data;
}

function enforce_contact_rate_limit(array $config, int $now): void
{
    $secret = (string)($config['contact_rate_secret'] ?? '');
    if (!preg_match('/^[a-f0-9]{64}$/D', $secret)) {
        json_response(['success' => false, 'message' => 'お問い合わせ受付の設定が未完了です'], 503);
    }
    $address = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $clientKey = hash_hmac('sha256', $address, $secret);
    $directory = private_data_directory();
    $dataPath = $directory . DIRECTORY_SEPARATOR . 'contact-rate.json';
    $lock = fopen($directory . DIRECTORY_SEPARATOR . 'contact-rate.lock', 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) json_response(['success' => false, 'message' => '送信制限を確認できません'], 500);
    try {
        $events = [];
        if (is_file($dataPath)) {
            $decoded = json_decode((string)file_get_contents($dataPath), true);
            if (!is_array($decoded)) json_response(['success' => false, 'message' => '送信制限データが破損しています'], 500);
            $events = $decoded;
        }
        $events = array_values(array_filter($events, static function ($event) use ($now): bool {
            return is_array($event) && isset($event['key'], $event['time'])
                && is_string($event['key']) && is_int($event['time']) && $event['time'] > $now - 86400;
        }));
        $clientHour = count(array_filter($events, static fn($event) => $event['key'] === $clientKey && $event['time'] > $now - 3600));
        if ($clientHour >= 3 || count($events) >= 50) {
            json_response(['success' => false, 'message' => '送信回数が多すぎます。時間をおいてお試しください'], 429);
        }
        $events[] = ['key' => $clientKey, 'time' => $now];
        $json = json_encode($events, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        $temporary = $dataPath . '.tmp.' . bin2hex(random_bytes(6));
        if ($json === false || file_put_contents($temporary, $json . "\n", LOCK_EX) === false || !rename($temporary, $dataPath)) {
            if (is_file($temporary)) @unlink($temporary);
            json_response(['success' => false, 'message' => '送信制限を保存できません'], 500);
        }
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

require_method('GET', 'POST');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    $_SESSION['contact_form_issued_at'] = time();
    json_response(['success' => true, 'csrf' => csrf_token()]);
}

require_csrf();
$input = contact_request_json();

// Bots commonly fill hidden fields. Return a neutral response without sending mail.
if (contact_text($input['website'] ?? '', 200) !== '') {
    json_response(['success' => true, 'message' => 'お問い合わせを受け付けました']);
}

$issuedAt = (int)($_SESSION['contact_form_issued_at'] ?? 0);
if ($issuedAt < 1 || time() - $issuedAt < 2) {
    json_response(['success' => false, 'message' => '少し待ってから、もう一度送信してください'], 429);
}

$now = time();
$attempts = array_values(array_filter(
    $_SESSION['contact_attempts'] ?? [],
    static fn($time) => is_int($time) && $time > $now - 3600
));
if (count($attempts) >= 3) {
    json_response(['success' => false, 'message' => '送信回数が多すぎます。時間をおいてお試しください'], 429);
}

$name = contact_text($input['name'] ?? '', 80);
$company = contact_text($input['company'] ?? '', 120);
$email = contact_text($input['email'] ?? '', 254);
$tel = contact_text($input['tel'] ?? '', 40);
$service = contact_text($input['service'] ?? '', 80);
$budget = contact_text($input['budget'] ?? '', 40);
$message = contact_text($input['message'] ?? '', 2000);
$agreed = ($input['agreed'] ?? false) === true;

if ($name === '' || $message === '' || !$agreed || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => '必須項目を正しく入力してください'], 400);
}
if (!in_array($service, CONTACT_SERVICES, true)) {
    json_response(['success' => false, 'message' => 'ご相談内容を選択してください'], 400);
}
if ($tel !== '' && !preg_match('/^[0-9+()\-\s]{6,40}$/', $tel)) {
    json_response(['success' => false, 'message' => '電話番号を正しく入力してください'], 400);
}

$config = local_config();
$recipient = (string)($config['contact_recipient'] ?? '');
$sender = (string)($config['contact_sender'] ?? '');
if (preg_match('/[\r\n]/', $recipient . $sender)
    || !filter_var($recipient, FILTER_VALIDATE_EMAIL)
    || !preg_match('/^[A-Za-z0-9._%+-]+@rss7[.]net$/D', $sender)) {
    json_response(['success' => false, 'message' => 'お問い合わせ受付の設定が未完了です'], 503);
}
enforce_contact_rate_limit($config, $now);

$safeService = contact_header_text($service);
$subject = '【RSS7 AI Works】お問い合わせ：' . $safeService;
$encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
$body = implode("\n", [
    'RSS7 AI Worksのお問い合わせフォームから受信しました。',
    '',
    'お名前：' . $name,
    '会社名・屋号：' . ($company !== '' ? $company : '未入力'),
    'メールアドレス：' . $email,
    '電話番号：' . ($tel !== '' ? $tel : '未入力'),
    'ご相談内容：' . $service,
    'ご予算：' . ($budget !== '' ? $budget : '未選択'),
    '',
    'お問い合わせ内容：',
    $message,
    '',
    '受付日時：' . date('Y-m-d H:i:s T'),
]);
$headers = implode("\r\n", [
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: 8bit',
    'From: RSS7 AI Works <' . $sender . '>',
    'Reply-To: ' . $email,
    'X-Mailer: RSS7-Contact-Form',
]);

$attempts[] = $now;
$_SESSION['contact_attempts'] = $attempts;
if (!mail($recipient, $encodedSubject, $body, $headers)) {
    json_response(['success' => false, 'message' => '送信処理を完了できませんでした。LINEまたはメールでお問い合わせください'], 500);
}

unset($_SESSION['contact_form_issued_at']);
json_response(['success' => true, 'message' => 'お問い合わせを受け付けました']);

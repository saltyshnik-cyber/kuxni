<?php
declare(strict_types=1);

require __DIR__ . '/../config.php';

$isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
    || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

function respond(bool $ok, string $message, int $code = 200, bool $ajax = true, ?array $errors = null): never
{
    if (!$ajax) {
        redirect('/index.php?sent=' . ($ok ? '1' : '0') . '#lead');
    }

    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');

    echo json_encode(
        ['ok' => $ok, 'message' => $message, 'errors' => $errors ?? []],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Метод не поддерживается.', 405, $isAjax);
}

if (!csrf_ok($_POST['csrf'] ?? null)) {
    respond(false, 'Обновите страницу и отправьте форму заново.', 419, $isAjax);
}

// Ловушка для ботов: скрытое поле должно остаться пустым.
if (trim((string)($_POST['company'] ?? '')) !== '') {
    respond(true, 'Заявка принята.', 200, $isAjax);
}

$name    = trim((string)($_POST['name'] ?? ''));
$phone   = trim((string)($_POST['phone'] ?? ''));
$service = trim((string)($_POST['service'] ?? ''));
$comment = trim((string)($_POST['comment'] ?? ''));

$name    = mb_substr(preg_replace('/\s+/u', ' ', $name) ?? $name, 0, 80);
$phone   = mb_substr($phone, 0, 40);
$service = mb_substr($service, 0, 80);
$comment = mb_substr(preg_replace('/[^\P{C}\n]+/u', '', $comment) ?? $comment, 0, 1000);

$errors = [];
if (mb_strlen($name) < 2) {
    $errors['name'] = 'Укажите имя — хотя бы 2 символа.';
}
if (!phone_ok($phone)) {
    $errors['phone'] = 'Проверьте номер: нужно 10–15 цифр.';
}
if ($errors !== []) {
    respond(false, (string)reset($errors), 422, $isAjax, $errors);
}

$ip = (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '');
$ip = trim(explode(',', $ip)[0]);

if (leads_from_ip($ip) >= 5) {
    respond(false, 'Слишком много заявок с этого адреса. Позвоните нам напрямую.', 429, $isAjax);
}

$lead = [
    'id'         => bin2hex(random_bytes(8)),
    'name'       => $name,
    'phone'      => $phone,
    'service'    => $service,
    'comment'    => $comment,
    'status'     => 'new',
    'ip'         => $ip,
    'created_at' => date('Y-m-d H:i:s'),
];

try {
    add_lead($lead);
} catch (RuntimeException $exception) {
    respond(false, 'Не удалось сохранить заявку. Напишите нам в WhatsApp.', 500, $isAjax);
}

send_lead_mail($lead);

respond(true, 'Заявка отправлена — перезвоним в течение рабочего дня.', 200, $isAjax);
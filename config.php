<?php
declare(strict_types=1);

date_default_timezone_set('Europe/Moscow');

/* ---------- Настройки ---------- */
const SITE_NAME      = 'Mebels.krd';
const ADMIN_PASSWORD = 'admin123';                  // смените на хостинге
const ADMIN_EMAIL    = 'info@mebels-krd.ru';        // куда приходят заявки
const MAIL_FROM      = 'no-reply@mebels-krd.ru';    // ящик на домене сайта
const MAIL_FROM_NAME = 'Mebels.krd';

const APP_ROOT     = __DIR__;
const DATA_DIR     = APP_ROOT . '/data';
const UPLOADS_DIR  = APP_ROOT . '/uploads';
const CONTENT_FILE = DATA_DIR . '/content.json';
const LEADS_FILE   = DATA_DIR . '/leads.json';

const LEAD_STATUSES = [
    'new'    => 'Новая',
    'inwork' => 'В работе',
    'done'   => 'Завершена',
];

/** Слоты фотографий на странице: порядок = порядок файлов в uploads/. */
const PHOTO_SLOTS = [
    'hero' => 'Первый экран',
    'g1'   => 'Работы — 1',
    'g2'   => 'Работы — 2',
    'g3'   => 'Работы — 3',
    'g4'   => 'Работы — 4',
    'g5'   => 'Работы — 5',
    'g6'   => 'Работы — 6',
    'g7'   => 'Работы — 7',
    'g8'   => 'Работы — 8',
    'g9'   => 'Работы — 9',
    'k1'   => 'Кухни — большая',
    'k2'   => 'Кухни — 2',
    'k3'   => 'Кухни — 3',
    'k4'   => 'Кухни — 4',
    'k5'   => 'Кухни — 5',
    'cta'  => 'Фон блока «Заявка»',
];

/* ---------- Базовые помощники ---------- */

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function ensure_data_dir(): void
{
    foreach ([DATA_DIR, UPLOADS_DIR] as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }
    if (!is_file(LEADS_FILE)) {
        @file_put_contents(LEADS_FILE, "[]\n", LOCK_EX);
    }
}

/* ---------- Контент ---------- */

function content(): array
{
    $raw  = @file_get_contents(CONTENT_FILE);
    $data = $raw === false ? null : json_decode($raw, true);

    if (!is_array($data)) {
        http_response_code(500);
        exit('data/content.json не найден или повреждён.');
    }

    return $data;
}

function save_content(array $data): bool
{
    ensure_data_dir();
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

    return $json !== false && @file_put_contents(CONTENT_FILE, $json . "\n", LOCK_EX) !== false;
}

/* ---------- Заявки ---------- */

function leads(): array
{
    if (!is_file(LEADS_FILE)) {
        return [];
    }
    $raw   = (string)@file_get_contents(LEADS_FILE);
    $leads = json_decode($raw, true);

    return is_array($leads) ? $leads : [];
}

function add_lead(array $lead): void
{
    ensure_data_dir();
    $handle = fopen(LEADS_FILE, 'c+');
    if ($handle === false) {
        throw new RuntimeException('Не удалось открыть data/leads.json');
    }

    flock($handle, LOCK_EX);
    $raw   = (string)stream_get_contents($handle);
    $leads = json_decode($raw, true);
    $leads = is_array($leads) ? $leads : [];

    array_unshift($leads, $lead);
    $leads = array_slice($leads, 0, 5000);

    $json = json_encode($leads, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, (string)$json . "\n");
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
}

function update_lead_status(string $id, string $status): void
{
    if (!isset(LEAD_STATUSES[$status])) {
        return;
    }
    $leads = leads();
    foreach ($leads as &$lead) {
        if (($lead['id'] ?? '') === $id) {
            $lead['status'] = $status;
            $lead['updated_at'] = date('Y-m-d H:i:s');
            break;
        }
    }
    unset($lead);

    $json = json_encode($leads, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    @file_put_contents(LEADS_FILE, (string)$json . "\n", LOCK_EX);
}

function delete_lead(string $id): void
{
    $leads = array_values(array_filter(leads(), static fn(array $l): bool => ($l['id'] ?? '') !== $id));
    $json  = json_encode($leads, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    @file_put_contents(LEADS_FILE, (string)$json . "\n", LOCK_EX);
}

function send_lead_mail(array $lead): bool
{
    $subject = 'Заявка с сайта ' . SITE_NAME;
    $body    = "Новая заявка с сайта\n\n"
        . 'Имя: ' . $lead['name'] . "\n"
        . 'Телефон: ' . $lead['phone'] . "\n"
        . 'Услуга: ' . ($lead['service'] !== '' ? $lead['service'] : '—') . "\n"
        . 'Комментарий: ' . ($lead['comment'] !== '' ? $lead['comment'] : '—') . "\n"
        . 'Дата: ' . $lead['created_at'] . "\n"
        . 'IP: ' . $lead['ip'] . "\n";

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>',
        'Reply-To: ' . MAIL_FROM,
        'X-Mailer: PHP/' . PHP_VERSION,
    ];

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    return @mail(ADMIN_EMAIL, $encodedSubject, $body, implode("\r\n", $headers));
}

/* ---------- Фотографии ---------- */

function upload_files(): array
{
    $files = [];
    foreach (glob(UPLOADS_DIR . '/*') ?: [] as $file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $files[] = 'uploads/' . basename($file);
        }
    }
    sort($files, SORT_NATURAL | SORT_FLAG_CASE);

    return $files;
}

/**
 * Раскладывает фотографии по слотам страницы.
 * Значение из админки имеет приоритет, иначе слот получает очередной файл из uploads/.
 */
function page_photos(array $content): array
{
    $files  = upload_files();
    $chosen = is_array($content['photos'] ?? null) ? $content['photos'] : [];
    $total  = count($files);
    $index  = 0;
    $result = [];

    foreach (array_keys(PHOTO_SLOTS) as $slot) {
        $path = (string)($chosen[$slot] ?? '');
        if ($path !== '' && is_file(APP_ROOT . '/' . $path)) {
            $result[$slot] = $path;
            continue;
        }
        $result[$slot] = $total > 0 ? $files[$index % $total] : '';
        $index++;
    }

    return $result;
}

function save_uploaded_image(array $file): ?string
{
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Файл не загрузился, попробуйте ещё раз.');
    }
    if (($file['size'] ?? 0) > 12 * 1024 * 1024) {
        throw new RuntimeException('Файл больше 12 МБ.');
    }

    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        throw new RuntimeException('Это не изображение.');
    }

    $extensions = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!isset($extensions[$info[2]])) {
        throw new RuntimeException('Разрешены только JPG, PNG и WebP.');
    }

    ensure_data_dir();
    $name = 'photo-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.' . $extensions[$info[2]];

    if (!move_uploaded_file($file['tmp_name'], UPLOADS_DIR . '/' . $name)) {
        throw new RuntimeException('Не удалось сохранить файл в uploads/.');
    }

    return 'uploads/' . $name;
}

function is_safe_upload_path(string $path): bool
{
    return $path !== ''
        && str_starts_with($path, 'uploads/')
        && basename($path) === substr($path, strlen('uploads/'))
        && is_file(APP_ROOT . '/' . $path);
}
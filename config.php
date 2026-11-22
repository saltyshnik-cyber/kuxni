<?php
declare(strict_types=1);

date_default_timezone_set('Europe/Moscow');

/* ---------- Настройки ---------- */
const SITE_NAME      = 'Mebels.krd';
const ADMIN_PASSWORD = 'admin123';                  // смените на хостинге
const ADMIN_EMAIL    = 'kzotova84@mail.ru';         // куда приходят заявки
const MAIL_FROM      = '';                          // пусто — возьмётся домен сайта
const MAIL_FROM_NAME = 'Mebels.krd';

const APP_ROOT     = __DIR__;
const DATA_DIR     = APP_ROOT . '/data';
const UPLOADS_DIR  = APP_ROOT . '/uploads';
const CONTENT_FILE = DATA_DIR . '/content.json';
const LEADS_FILE   = DATA_DIR . '/leads.json';

/** .jfif — тот же JPEG, просто другое расширение. */
const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'jfif', 'png', 'webp'];

const LEAD_STATUSES = [
    'new'    => 'Новая',
    'inwork' => 'В работе',
    'done'   => 'Завершена',
];

/** Слоты фотографий: ключ => подпись (плоский список, используется сайтом). */
const PHOTO_SLOTS = [
    'hero' => 'Главный экран',
    'g1'   => 'Работы — 1',
    'g2'   => 'Работы — 2',
    'g3'   => 'Работы — 3',
    'g4'   => 'Работы — 4',
    'g5'   => 'Работы — 5',
    'k1'   => 'Кухни — большая',
    'k2'   => 'Кухни — 2',
    'k3'   => 'Кухни — 3',
];

/** Блоки сайта для админки: заголовок, подсказка по пропорциям, слоты. */
const PHOTO_GROUPS = [
    [
        'title' => '1. Главный экран (Hero)',
        'hint'  => 'Вертикальное или квадратное, 4:5 (например 1200×1500). Кухня по центру кадра.',
        'slots' => ['hero'],
    ],
    [
        'title' => '2. Каталог / Наши работы',
        'hint'  => 'Вертикальные, 4:5 (например 1000×1250). Листаются в галерее вбок.',
        'slots' => ['g1', 'g2', 'g3', 'g4', 'g5'],
    ],
    [
        'title' => '3. Кухни и цены',
        'hint'  => 'Большое фото 4:5, остальные 3:2 (например 1200×800). Показываются сеткой.',
        'slots' => ['k1', 'k2', 'k3'],
    ],
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
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Не удалось создать папку ' . basename($dir));
        }
    }
    if (!is_file(LEADS_FILE)) {
        @file_put_contents(LEADS_FILE, "[]\n", LOCK_EX);
    }
}

function phone_ok(string $phone): bool
{
    $digits = preg_replace('/\D+/', '', $phone);

    return $digits !== null && mb_strlen($digits) >= 10 && mb_strlen($digits) <= 15;
}

/* ---------- CSRF и сессия ---------- */

function start_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }

    return $_SESSION['csrf'];
}

function csrf_ok(?string $token): bool
{
    start_session();

    return is_string($token) && $token !== '' && !empty($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $token);
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

/** Путь вида "hero.title" -> значение (или '' если нет). */
function get_path(array $data, string $path): mixed
{
    foreach (explode('.', $path) as $key) {
        if (!is_array($data) || !array_key_exists($key, $data)) {
            return '';
        }
        $data = $data[$key];
    }

    return $data;
}

function set_path(array &$data, string $path, mixed $value): void
{
    $keys = explode('.', $path);
    $last = (string)array_pop($keys);
    $ref  = &$data;

    foreach ($keys as $key) {
        if (!isset($ref[$key]) || !is_array($ref[$key])) {
            $ref[$key] = [];
        }
        $ref = &$ref[$key];
    }

    $ref[$last] = $value;
}

/** Строки «поле | поле» -> список записей. */
function lines_to_items(string $text, array $keys): array
{
    $items = [];
    foreach (preg_split('/\R/u', $text) ?: [] as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $parts = array_map('trim', explode('|', $line, count($keys)));
        $item  = [];
        foreach ($keys as $index => $key) {
            $item[$key] = $parts[$index] ?? '';
        }
        $items[] = $item;
    }

    return $items;
}

function items_to_lines(array $items, array $keys): string
{
    $lines = [];
    foreach ($items as $item) {
        $row = [];
        foreach ($keys as $key) {
            $row[] = (string)($item[$key] ?? '');
        }
        $lines[] = implode(' | ', $row);
    }

    return implode("\n", $lines);
}

function plain_lines(string $text): array
{
    $lines = array_map('trim', preg_split('/\R/u', $text) ?: []);

    return array_values(array_filter($lines, static fn(string $line): bool => $line !== ''));
}

/* ---------- Заявки ---------- */

function leads(): array
{
    if (!is_file(LEADS_FILE)) {
        return [];
    }
    $data = json_decode((string)@file_get_contents(LEADS_FILE), true);

    return is_array($data) ? $data : [];
}

function save_leads(array $leads): void
{
    ensure_data_dir();
    $json = json_encode($leads, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $handle = fopen(LEADS_FILE, 'c+');
    if ($handle === false || $json === false) {
        throw new RuntimeException('Не удалось записать data/leads.json');
    }
    flock($handle, LOCK_EX);
    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, $json . "\n");
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
}

function add_lead(array $lead): void
{
    $leads = leads();
    array_unshift($leads, $lead);
    save_leads(array_slice($leads, 0, 5000));
}

function update_lead_status(string $id, string $status): void
{
    if (!isset(LEAD_STATUSES[$status])) {
        return;
    }
    $leads = leads();
    foreach ($leads as &$lead) {
        if (($lead['id'] ?? '') === $id) {
            $lead['status']     = $status;
            $lead['updated_at'] = date('Y-m-d H:i:s');
            break;
        }
    }
    unset($lead);
    save_leads($leads);
}

function delete_lead(string $id): void
{
    save_leads(array_values(array_filter(
        leads(),
        static fn(array $lead): bool => ($lead['id'] ?? '') !== $id
    )));
}

function leads_from_ip(string $ip, int $seconds = 3600): int
{
    $limit = time() - $seconds;
    $count = 0;
    foreach (leads() as $lead) {
        if (($lead['ip'] ?? '') !== $ip) {
            continue;
        }
        if (strtotime((string)($lead['created_at'] ?? '')) > $limit) {
            $count++;
        }
    }

    return $count;
}

function mail_from_address(): string
{
    if (MAIL_FROM !== '') {
        return MAIL_FROM;
    }

    $host = (string)($_SERVER['SERVER_NAME'] ?? 'localhost');

    return 'no-reply@' . preg_replace('/^www\./', '', $host);
}

function send_lead_mail(array $lead): bool
{
    $from = mail_from_address();

    $body = "Новая заявка с сайта\n\n"
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
        'From: ' . MAIL_FROM_NAME . ' <' . $from . '>',
        'Reply-To: ' . $from,
        'X-Mailer: PHP/' . PHP_VERSION,
    ];

    $subject = '=?UTF-8?B?' . base64_encode('Заявка с сайта ' . SITE_NAME) . '?=';

    return @mail(ADMIN_EMAIL, $subject, $body, implode("\r\n", $headers));
}

/* ---------- Фотографии ---------- */

function upload_files(): array
{
    $files = [];
    foreach (glob(UPLOADS_DIR . '/*') ?: [] as $file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, IMAGE_EXTENSIONS, true)) {
            $files[] = 'uploads/' . basename($file);
        }
    }
    sort($files, SORT_NATURAL | SORT_FLAG_CASE);

    return $files;
}

/**
 * Раскладка фото по слотам без повторов: приоритет у выбора из админки,
 * свободные слоты занимают ещё не использованные файлы из uploads/.
 */
function page_photos(array $content): array
{
    $chosen = is_array($content['photos'] ?? null) ? $content['photos'] : [];
    $result = [];
    $used   = [];

    foreach (array_keys(PHOTO_SLOTS) as $slot) {
        $path = (string)($chosen[$slot] ?? '');
        if ($path !== '' && is_safe_upload_path($path) && !isset($used[$path])) {
            $result[$slot] = $path;
            $used[$path]   = true;
            continue;
        }
        $result[$slot] = '';
    }

    $free = array_values(array_filter(
        upload_files(),
        static fn(string $file): bool => !isset($used[$file])
    ));

    foreach (array_keys(PHOTO_SLOTS) as $slot) {
        if ($result[$slot] !== '') {
            continue;
        }
        $path = array_shift($free);
        if ($path === null) {
            break;
        }
        $result[$slot] = $path;
        $used[$path]   = true;
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
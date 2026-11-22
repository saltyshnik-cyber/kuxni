<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

start_session();

/** Поля-строки вкладки «Тексты и контакты». */
const TEXT_FIELDS = [
    'site.title', 'site.description',
    'contacts.phone_text', 'contacts.phone_raw', 'contacts.whatsapp', 'contacts.telegram',
    'contacts.email', 'contacts.address', 'contacts.hours',
    'contacts.maps_yandex', 'contacts.maps_2gis',
    'hero.kicker', 'hero.title', 'hero.title_accent', 'hero.sub',
    'about.eyebrow', 'about.title', 'about.title_accent',
    'gallery.eyebrow', 'gallery.title', 'gallery.title_accent', 'gallery.lead', 'gallery.hint',
    'works.eyebrow', 'works.title', 'works.title_accent', 'works.lead',
    'kitchens.eyebrow', 'kitchens.title', 'kitchens.title_accent', 'kitchens.lead', 'kitchens.button',
    'reviews.eyebrow', 'reviews.title', 'reviews.score_value', 'reviews.score_text',
    'steps.eyebrow', 'steps.title', 'steps.title_accent',
    'form.eyebrow', 'form.title', 'form.title_accent', 'form.lead', 'form.intro',
    'form.submit', 'form.note',
    'contacts_section.eyebrow', 'contacts_section.title', 'contacts_section.title_accent',
];

/** Списки «строка = несколько полей через |». */
const LIST_FIELDS = [
    'hero.facts'     => ['value', 'label'],
    'about.facts'    => ['value', 'label'],
    'works.items'    => ['title', 'desc', 'price'],
    'kitchens.items' => ['label', 'price', 'note'],
    'reviews.items'  => ['name', 'source', 'text'],
    'steps.items'    => ['title', 'desc'],
];

/** Списки, где строка — это один пункт целиком. */
const PLAIN_FIELDS = ['about.body', 'form.services'];

const TABS = [
    'leads'   => 'Заявки',
    'content' => 'Тексты и контакты',
    'photos'  => 'Фотографии',
];

function field_name(string $path): string
{
    // Точки в именах полей PHP превращает в подчёркивания — разделяем двойным.
    return 'f[' . str_replace('.', '__', $path) . ']';
}

function flash(string $type, string $text): void
{
    $_SESSION['flash'] = ['type' => $type, 'text' => $text];
}

function take_flash(): ?array
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return is_array($message) ? $message : null;
}

function current_tab(): string
{
    $tab = (string)($_POST['tab'] ?? $_GET['tab'] ?? 'leads');

    return isset(TABS[$tab]) ? $tab : 'leads';
}

/* ---------- Вход и выход ---------- */

if (($_GET['action'] ?? '') === 'logout') {
    $_SESSION = [];
    session_destroy();
    redirect('admin.php');
}

$loginError = '';

if (($_POST['action'] ?? '') === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok($_POST['csrf'] ?? null)) {
        $loginError = 'Страница устарела — попробуйте снова.';
    } elseif (hash_equals(ADMIN_PASSWORD, (string)($_POST['password'] ?? ''))) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        redirect('admin.php');
    } else {
        $loginError = 'Неверный пароль.';
    }
}

$isAuthed = ($_SESSION['admin'] ?? false) === true;
$tab      = current_tab();

/* ---------- Действия ---------- */

if ($isAuthed && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action !== 'login' && $action !== '') {
        if (!csrf_ok($_POST['csrf'] ?? null)) {
            flash('err', 'Сессия истекла — повторите действие.');
            redirect('admin.php?tab=' . $tab);
        }

        switch ($action) {
            case 'save_content':
                $data = content();

                foreach (TEXT_FIELDS as $path) {
                    $value = trim((string)($_POST['f'][str_replace('.', '__', $path)] ?? ''));
                    set_path($data, $path, $value);
                }
                foreach (PLAIN_FIELDS as $path) {
                    $value = (string)($_POST['f'][str_replace('.', '__', $path)] ?? '');
                    set_path($data, $path, plain_lines($value));
                }
                foreach (LIST_FIELDS as $path => $keys) {
                    $value = (string)($_POST['f'][str_replace('.', '__', $path)] ?? '');
                    set_path($data, $path, lines_to_items($value, $keys));
                }

                flash(save_content($data) ? 'ok' : 'err',
                    save_content($data) ? 'Тексты сохранены.' : 'Не удалось записать data/content.json');
                redirect('admin.php?tab=content');

            case 'save_photos':
                // Оставлено для совместимости со старыми формами; новая админка использует replace_photo.
                redirect('admin.php?tab=photos');

            case 'replace_photo':
                $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
                $reply  = static function (bool $ok, string $message, string $path = '') use ($isAjax): void {
                    if ($isAjax) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['ok' => $ok, 'message' => $message, 'path' => $path], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    flash($ok ? 'ok' : 'err', $message);
                    redirect('admin.php?tab=photos');
                };

                $slot = (string)($_POST['slot'] ?? '');
                if (!isset(PHOTO_SLOTS[$slot])) {
                    $reply(false, 'Неизвестный блок.');
                }

                try {
                    $path = save_uploaded_image($_FILES['photo'] ?? []);
                    if ($path === null) {
                        $reply(false, 'Выберите файл.');
                    }
                    $data  = content();
                    $old   = (string)($data['photos'][$slot] ?? '');
                    $data['photos'][$slot] = $path;
                    if (!save_content($data)) {
                        $reply(false, 'Не удалось записать data/content.json');
                    }
                    if ($old !== '' && $old !== $path && is_safe_upload_path($old)
                        && !in_array($old, array_values((array)$data['photos']), true)) {
                        @unlink(APP_ROOT . '/' . $old);
                    }
                    $reply(true, 'Фото обновлено.', $path);
                } catch (RuntimeException $exception) {
                    $reply(false, $exception->getMessage());
                }
                break;

            case 'upload_photo':
                redirect('admin.php?tab=photos');

            case 'set_status':
                update_lead_status((string)($_POST['id'] ?? ''), (string)($_POST['status'] ?? ''));
                flash('ok', 'Статус заявки обновлён.');
                redirect('admin.php?tab=leads');

            case 'delete_lead':
                delete_lead((string)($_POST['id'] ?? ''));
                flash('ok', 'Заявка удалена.');
                redirect('admin.php?tab=leads');
        }

        redirect('admin.php?tab=' . $tab);
    }
}

/* ---------- Помощники вывода ---------- */

function input_field(array $c, string $path, string $label, string $type = 'text', string $hint = ''): void
{
    echo '<label class="field"><span>' . e($label)
        . ($hint !== '' ? ' <i>' . e($hint) . '</i>' : '') . '</span>'
        . '<input type="' . e($type) . '" name="' . e(field_name($path)) . '" value="'
        . e((string)get_path($c, $path)) . '"></label>';
}

function area_field(array $c, string $path, string $label, int $rows = 3, string $hint = ''): void
{
    echo '<label class="field wide"><span>' . e($label)
        . ($hint !== '' ? ' <i>' . e($hint) . '</i>' : '') . '</span>'
        . '<textarea name="' . e(field_name($path)) . '" rows="' . $rows . '">'
        . e((string)get_path($c, $path)) . '</textarea></label>';
}

function list_field(array $c, string $path, string $label, array $keys, int $rows = 6, string $hint = ''): void
{
    $value = items_to_lines((array)get_path($c, $path), $keys);

    echo '<label class="field wide"><span>' . e($label)
        . ($hint !== '' ? ' <i>' . e($hint) . '</i>' : '') . '</span>'
        . '<textarea name="' . e(field_name($path)) . '" rows="' . $rows . '">'
        . e($value) . '</textarea></label>';
}

function plain_field(array $c, string $path, string $label, int $rows = 4, string $hint = ''): void
{
    $value = implode("\n", array_map(
        static fn(mixed $line): string => (string)$line,
        (array)get_path($c, $path)
    ));

    echo '<label class="field wide"><span>' . e($label)
        . ($hint !== '' ? ' <i>' . e($hint) . '</i>' : '') . '</span>'
        . '<textarea name="' . e(field_name($path)) . '" rows="' . $rows . '">'
        . e($value) . '</textarea></label>';
}

$flashMessage = take_flash();
$c            = content();
$csrf         = csrf_token();
$slotPaths    = page_photos($c);
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Админка — <?= e(SITE_NAME) ?></title>
<style>
:root{--bg:#0F1210;--panel:#1A211D;--panel2:#212A24;--line:#2E3A32;--text:#EDEAE3;--muted:#98A69C;
--oak:#CF9A62;--oak-hi:#EDC593;--err:#E2715A;--ok:#7CE38B;--r:16px}
*{box-sizing:border-box;margin:0;padding:0}
body{background:var(--bg);color:var(--text);font:15px/1.5 'Golos Text',system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;padding:0 0 60px}
a{color:var(--oak-hi);text-decoration:none}
input,select,textarea,button{font:inherit;color:inherit}
.top{position:sticky;top:0;z-index:10;display:flex;flex-wrap:wrap;align-items:center;gap:14px;padding:12px 18px;background:rgba(15,18,16,.94);border-bottom:1px solid var(--line);backdrop-filter:blur(10px)}
.top b{font-size:17px;letter-spacing:.06em}
.top .sp{flex:1}
.top .out{font-size:13.5px;color:var(--muted)}
.tabs{display:flex;flex-wrap:wrap;gap:8px;padding:14px 18px 0}
.tabs a{padding:10px 16px;border-radius:999px;border:1px solid var(--line);font-size:14px;font-weight:600;color:var(--muted)}
.tabs a.on{background:linear-gradient(135deg,var(--oak-hi),var(--oak));color:#1B1209;border-color:transparent}
.wrap{max-width:1080px;margin:0 auto;padding:18px}
.panel{background:var(--panel);border:1px solid var(--line);border-radius:var(--r);padding:18px;margin-bottom:18px}
.panel h2{font-size:17px;font-weight:700;margin-bottom:14px}
.flash{display:flex;gap:10px;align-items:flex-start;padding:12px 16px;border-radius:12px;margin-bottom:16px;font-size:14.5px;border:1px solid}
.flash.ok{border-color:rgba(124,227,139,.45);background:rgba(124,227,139,.1)}
.flash.err{border-color:rgba(226,113,90,.5);background:rgba(226,113,90,.12)}
.stats{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:16px}
.stats span{padding:8px 14px;border-radius:999px;background:var(--panel2);font-size:13.5px;color:var(--muted)}
.stats b{color:var(--oak-hi)}
.lead{border:1px solid var(--line);border-radius:14px;padding:14px;margin-bottom:12px;background:var(--panel2)}
.lead .head{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-bottom:8px}
.lead .who{font-weight:700;font-size:16px}
.lead .when{font-size:12.5px;color:var(--muted);margin-left:auto}
.lead .ph{font-size:16px;font-weight:700}
.lead .meta{font-size:13.5px;color:var(--muted);margin-top:4px}
.lead .text{margin-top:8px;font-size:14.5px;line-height:1.5;white-space:pre-wrap;word-break:break-word}
.lead .row{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-top:12px}
.badge{padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700;letter-spacing:.04em}
.badge.new{background:rgba(207,154,98,.22);color:var(--oak-hi)}
.badge.inwork{background:rgba(124,163,227,.2);color:#A9C7F5}
.badge.done{background:rgba(124,227,139,.18);color:var(--ok)}
select,input[type=text],input[type=password],textarea{width:100%;padding:11px 13px;border-radius:11px;border:1px solid var(--line);background:#141A16;font-size:14.5px}
textarea{resize:vertical;line-height:1.5}
select:focus,input:focus,textarea:focus{outline:none;border-color:var(--oak)}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:42px;padding:0 18px;border:0;border-radius:999px;background:linear-gradient(135deg,var(--oak-hi),var(--oak));color:#1B1209;font-weight:700;font-size:14px;cursor:pointer}
.btn.ghost{background:transparent;color:var(--text);border:1px solid var(--line)}
.btn.danger{background:transparent;color:var(--err);border:1px solid rgba(226,113,90,.5)}
.grid{display:grid;gap:12px}
@media (min-width:720px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}.grid .wide{grid-column:1/-1}}
.field{display:block}
.field>span{display:block;font-size:12px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--oak);margin-bottom:6px}
.field>span i{font-style:normal;font-weight:500;letter-spacing:0;text-transform:none;color:var(--muted);font-size:12px}
fieldset{border:0;margin:0 0 18px;padding:0}
legend{font-size:13px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);margin-bottom:10px}
.photos{display:grid;gap:14px}
@media (min-width:560px){.photos{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (min-width:860px){.photos{grid-template-columns:repeat(3,minmax(0,1fr))}}
.photo{border:1px solid var(--line);border-radius:14px;overflow:hidden;background:var(--panel2);display:flex;flex-direction:column}
.photo img{width:100%;aspect-ratio:4/3;object-fit:cover;display:block;background:#0B0E0C}
.photo .body{padding:10px 12px 12px;display:flex;flex-direction:column;gap:8px;flex:1}
.photo code{font-size:11.5px;color:var(--muted);word-break:break-all;line-height:1.35}
.photo .used{font-size:12px;color:var(--oak-hi);font-weight:600}
.slots{display:grid;gap:12px}
@media (min-width:720px){.slots{grid-template-columns:repeat(2,minmax(0,1fr))}}
.slot span{display:block;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--oak);margin-bottom:5px}
.muted{color:var(--muted);font-size:13.5px}
.photos-intro{color:var(--muted);font-size:13.5px;margin-bottom:16px}
.pgroup{margin-bottom:26px}
.pgroup h2{font-size:17px;font-weight:700;margin-bottom:4px}
.pgroup .ghint{font-size:13px;color:var(--muted);margin-bottom:14px}
.pcards{display:grid;gap:14px;grid-template-columns:minmax(0,1fr)}
@media (min-width:560px){.pcards{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (min-width:900px){.pcards{grid-template-columns:repeat(3,minmax(0,1fr))}}
.pcard{border:1px solid var(--line);border-radius:16px;overflow:hidden;background:var(--panel2);display:flex;flex-direction:column;min-width:0}
.pcard .pic{position:relative;background:#0B0E0C}
.pcard .pic img{width:100%;aspect-ratio:4/3;object-fit:cover;display:block}
.pcard .pic .empty{display:grid;place-items:center;aspect-ratio:4/3;color:var(--muted);font-size:13px}
.pcard .pic .state{position:absolute;left:10px;bottom:10px;padding:4px 10px;border-radius:999px;background:rgba(15,18,16,.82);font-size:12px;font-weight:600;color:var(--oak-hi)}
.pcard .pbody{padding:12px;display:flex;flex-direction:column;gap:8px;min-width:0}
.pcard .ptitle{font-weight:700;font-size:15px}
.pcard .pratio{font-size:12.5px;color:var(--muted)}
.pcard .btn{width:100%;min-height:46px}
.pcard input[type=file]{position:absolute;width:1px;height:1px;opacity:0;pointer-events:none}
.pcard .pnote{font-size:12.5px;min-height:18px}
.pcard .pnote.ok{color:var(--ok)}.pcard .pnote.err{color:var(--err)}
html,body{max-width:100%;overflow-x:hidden}
.top,.tabs,.wrap{min-width:0}
.login{min-height:100vh;display:grid;place-items:center;padding:24px}
.login .card{width:100%;max-width:360px;background:var(--panel);border:1px solid var(--line);border-radius:20px;padding:26px}
.login h1{font-size:19px;margin-bottom:6px}
.login p{color:var(--muted);font-size:13.5px;margin-bottom:18px}
.login .field{margin-bottom:14px}
.login .btn{width:100%;margin-top:6px}
</style>
</head>
<body>
<?php if (!$isAuthed): ?>
<div class="login">
  <form class="card" method="post" action="admin.php">
    <h1><?= e(SITE_NAME) ?></h1>
    <p>Вход в админку. Пароль меняется в <code>config.php</code>.</p>
    <input type="hidden" name="action" value="login">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <label class="field"><span>Пароль</span>
      <input type="password" name="password" autocomplete="current-password" required autofocus>
    </label>
    <?php if ($loginError !== ''): ?>
    <div class="flash err"><?= e($loginError) ?></div>
    <?php endif; ?>
    <button class="btn" type="submit">Войти</button>
  </form>
</div>
</body>
</html>
<?php exit; endif; ?>

<div class="top">
  <b><?= e(SITE_NAME) ?> · админка</b>
  <span class="sp"></span>
  <a class="out" href="index.php" target="_blank" rel="noopener">Открыть сайт →</a>
  <a class="out" href="admin.php?action=logout">Выйти</a>
</div>

<nav class="tabs">
  <?php foreach (TABS as $key => $label): ?>
  <a class="<?= $tab === $key ? 'on' : '' ?>" href="admin.php?tab=<?= e($key) ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>

<div class="wrap">
<?php if ($flashMessage !== null): ?>
<div class="flash <?= $flashMessage['type'] === 'ok' ? 'ok' : 'err' ?>"><?= e((string)$flashMessage['text']) ?></div>
<?php endif; ?>

<?php if ($tab === 'leads'): ?>
  <?php
  $allLeads = leads();
  $counts   = array_fill_keys(array_keys(LEAD_STATUSES), 0);
  $counts['all'] = count($allLeads);

  foreach ($allLeads as $lead) {
      $status = (string)($lead['status'] ?? 'new');
      if (isset($counts[$status])) {
          $counts[$status]++;
      }
  }

  $filter = (string)($_GET['status'] ?? 'all');
  $filter = isset($counts[$filter]) ? $filter : 'all';
  $shown  = $filter === 'all'
      ? $allLeads
      : array_values(array_filter($allLeads, static fn(array $lead): bool => (string)($lead['status'] ?? 'new') === $filter));
  ?>
  <div class="stats">
    <span>Всего: <b><?= $counts['all'] ?></b></span>
    <?php foreach (LEAD_STATUSES as $key => $label): ?>
    <span><?= e($label) ?>: <b><?= $counts[$key] ?></b></span>
    <?php endforeach; ?>
  </div>

  <div class="tabs" style="padding:0 0 14px">
    <a class="<?= $filter === 'all' ? 'on' : '' ?>" href="admin.php?tab=leads">Все</a>
    <?php foreach (LEAD_STATUSES as $key => $label): ?>
    <a class="<?= $filter === $key ? 'on' : '' ?>" href="admin.php?tab=leads&amp;status=<?= e($key) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>

  <?php if ($shown === []): ?>
  <div class="panel"><p class="muted">Заявок пока нет. Они появятся здесь после отправки формы на сайте.</p></div>
  <?php endif; ?>

  <?php foreach ($shown as $lead):
      $id     = (string)($lead['id'] ?? '');
      $status = (string)($lead['status'] ?? 'new');
      $phone  = (string)($lead['phone'] ?? '');
      $digits = preg_replace('/\D+/', '', $phone) ?? '';
  ?>
  <div class="lead">
    <div class="head">
      <span class="who"><?= e((string)($lead['name'] ?? 'Без имени')) ?></span>
      <span class="badge <?= e($status) ?>"><?= e(LEAD_STATUSES[$status] ?? 'Новая') ?></span>
      <span class="when"><?= e((string)($lead['created_at'] ?? '')) ?></span>
    </div>
    <div class="ph"><a href="tel:<?= e('+' . $digits) ?>"><?= e($phone) ?></a></div>
    <div class="meta">Услуга: <?= e((string)(($lead['service'] ?? '') !== '' ? $lead['service'] : '—')) ?>
      · IP: <?= e((string)($lead['ip'] ?? '—')) ?></div>
    <?php if ((string)($lead['comment'] ?? '') !== ''): ?>
    <div class="text"><?= e((string)$lead['comment']) ?></div>
    <?php endif; ?>
    <div class="row">
      <form method="post" action="admin.php" style="display:flex;gap:8px;align-items:center">
        <input type="hidden" name="action" value="set_status">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="tab" value="leads">
        <input type="hidden" name="id" value="<?= e($id) ?>">
        <select name="status" style="width:auto;min-width:150px">
          <?php foreach (LEAD_STATUSES as $key => $label): ?>
          <option value="<?= e($key) ?>"<?= $key === $status ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn ghost" type="submit">Сохранить</button>
      </form>
      <form method="post" action="admin.php" onsubmit="return confirm('Удалить заявку?')">
        <input type="hidden" name="action" value="delete_lead">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="tab" value="leads">
        <input type="hidden" name="id" value="<?= e($id) ?>">
        <button class="btn danger" type="submit">Удалить</button>
      </form>
    </div>
  </div>
  <?php endforeach; ?>

<?php elseif ($tab === 'content'): ?>
  <form method="post" action="admin.php">
    <input type="hidden" name="action" value="save_content">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="tab" value="content">

    <fieldset class="panel">
      <legend>Сайт и первый экран</legend>
      <div class="grid">
        <?php input_field($c, 'site.title', 'Заголовок вкладки', 'text', 'до 60 символов'); ?>
        <?php area_field($c, 'site.description', 'Описание для поисковиков', 2, 'до 160 символов'); ?>
        <?php input_field($c, 'hero.kicker', 'Надпись над заголовком'); ?>
        <?php input_field($c, 'hero.title', 'Заголовок, первая строка'); ?>
        <?php input_field($c, 'hero.title_accent', 'Заголовок, акцент'); ?>
        <?php area_field($c, 'hero.sub', 'Подзаголовок', 2); ?>
        <?php list_field($c, 'hero.facts', 'Цифры первого экрана', ['value', 'label'], 4, 'значение | подпись — по одному в строке'); ?>
      </div>
    </fieldset>

    <fieldset class="panel">
      <legend>О нас</legend>
      <div class="grid">
        <?php input_field($c, 'about.eyebrow', 'Надзаголовок'); ?>
        <?php input_field($c, 'about.title', 'Заголовок'); ?>
        <?php input_field($c, 'about.title_accent', 'Акцент'); ?>
        <?php plain_field($c, 'about.body', 'Абзацы', 6, 'каждый абзац с новой строки'); ?>
        <?php list_field($c, 'about.facts', 'Цифры блока', ['value', 'label'], 4, 'значение | подпись'); ?>
      </div>
    </fieldset>

    <fieldset class="panel">
      <legend>Работы</legend>
      <div class="grid">
        <?php input_field($c, 'gallery.eyebrow', 'Надзаголовок'); ?>
        <?php input_field($c, 'gallery.title', 'Заголовок'); ?>
        <?php input_field($c, 'gallery.title_accent', 'Акцент'); ?>
        <?php input_field($c, 'gallery.hint', 'Подсказка под галереей'); ?>
        <?php area_field($c, 'gallery.lead', 'Описание', 2); ?>
      </div>
    </fieldset>

    <fieldset class="panel">
      <legend>Услуги</legend>
      <div class="grid">
        <?php input_field($c, 'works.eyebrow', 'Надзаголовок'); ?>
        <?php input_field($c, 'works.title', 'Заголовок'); ?>
        <?php input_field($c, 'works.title_accent', 'Акцент'); ?>
        <?php area_field($c, 'works.lead', 'Описание', 2); ?>
        <?php list_field($c, 'works.items', 'Услуги', ['title', 'desc', 'price'], 8, 'название | описание | цена'); ?>
      </div>
    </fieldset>

    <fieldset class="panel">
      <legend>Цены</legend>
      <div class="grid">
        <?php input_field($c, 'kitchens.eyebrow', 'Надзаголовок'); ?>
        <?php input_field($c, 'kitchens.title', 'Заголовок'); ?>
        <?php input_field($c, 'kitchens.title_accent', 'Акцент'); ?>
        <?php input_field($c, 'kitchens.button', 'Текст кнопки'); ?>
        <?php area_field($c, 'kitchens.lead', 'Описание', 2); ?>
        <?php list_field($c, 'kitchens.items', 'Форматы кухонь', ['label', 'price', 'note'], 6, 'название | цена | пояснение — первая строка крупная, остальные мелкие'); ?>
      </div>
    </fieldset>

    <fieldset class="panel">
      <legend>Отзывы</legend>
      <div class="grid">
        <?php input_field($c, 'reviews.eyebrow', 'Надзаголовок'); ?>
        <?php input_field($c, 'reviews.title', 'Заголовок'); ?>
        <?php input_field($c, 'reviews.score_value', 'Оценка'); ?>
        <?php area_field($c, 'reviews.score_text', 'Подпись к оценке', 2); ?>
        <?php list_field($c, 'reviews.items', 'Отзывы', ['name', 'source', 'text'], 8, 'имя | источник | текст отзыва'); ?>
      </div>
    </fieldset>

    <fieldset class="panel">
      <legend>Как работаем</legend>
      <div class="grid">
        <?php input_field($c, 'steps.eyebrow', 'Надзаголовок'); ?>
        <?php input_field($c, 'steps.title', 'Заголовок'); ?>
        <?php input_field($c, 'steps.title_accent', 'Акцент'); ?>
        <?php list_field($c, 'steps.items', 'Шаги', ['title', 'desc'], 6, 'название | описание'); ?>
      </div>
    </fieldset>

    <fieldset class="panel">
      <legend>Форма заявки</legend>
      <div class="grid">
        <?php input_field($c, 'form.eyebrow', 'Надзаголовок'); ?>
        <?php input_field($c, 'form.title', 'Заголовок'); ?>
        <?php input_field($c, 'form.title_accent', 'Акцент'); ?>
        <?php input_field($c, 'form.intro', 'Заголовок карточки'); ?>
        <?php input_field($c, 'form.submit', 'Текст кнопки'); ?>
        <?php area_field($c, 'form.lead', 'Описание', 2); ?>
        <?php area_field($c, 'form.note', 'Примечание под формой', 3); ?>
        <?php plain_field($c, 'form.services', 'Пункты списка услуг', 7, 'по одному в строке'); ?>
      </div>
    </fieldset>

    <fieldset class="panel">
      <legend>Контакты</legend>
      <div class="grid">
        <?php input_field($c, 'contacts.phone_text', 'Телефон как показывать'); ?>
        <?php input_field($c, 'contacts.phone_raw', 'Телефон для ссылки', 'text', 'только + и цифры'); ?>
        <?php input_field($c, 'contacts.whatsapp', 'Ссылка WhatsApp'); ?>
        <?php input_field($c, 'contacts.telegram', 'Ссылка Telegram'); ?>
        <?php input_field($c, 'contacts.email', 'Почта'); ?>
        <?php input_field($c, 'contacts.hours', 'Режим работы'); ?>
        <?php input_field($c, 'contacts.address', 'Адрес'); ?>
        <?php input_field($c, 'contacts.maps_yandex', 'Ссылка на Яндекс Карты'); ?>
        <?php input_field($c, 'contacts.maps_2gis', 'Ссылка на 2ГИС'); ?>
        <?php input_field($c, 'contacts_section.eyebrow', 'Надзаголовок блока контактов'); ?>
        <?php input_field($c, 'contacts_section.title', 'Заголовок блока'); ?>
        <?php input_field($c, 'contacts_section.title_accent', 'Акцент'); ?>
      </div>
    </fieldset>

    <button class="btn" type="submit">Сохранить тексты</button>
  </form>

<?php else: ?>
  <p class="photos-intro">Нажмите «Изменить фото» в нужном блоке, выберите файл — он сразу загрузится и встанет на сайт. JPG, PNG или WebP, до 12 МБ.</p>

  <?php foreach (PHOTO_GROUPS as $group): ?>
  <section class="pgroup">
    <h2><?= e($group['title']) ?></h2>
    <p class="ghint"><?= e($group['hint']) ?></p>
    <div class="pcards">
      <?php foreach ($group['slots'] as $slot):
          $current = (string)($slotPaths[$slot] ?? '');
      ?>
      <div class="pcard" data-slot="<?= e($slot) ?>">
        <div class="pic">
          <?php if ($current !== ''): ?>
          <img src="<?= e($current) ?>" alt="<?= e(PHOTO_SLOTS[$slot]) ?>" loading="lazy" decoding="async">
          <?php else: ?>
          <div class="empty">Фото не задано</div>
          <?php endif; ?>
          <span class="state"><?= $current !== '' ? 'Сейчас на сайте' : 'Пусто' ?></span>
        </div>
        <div class="pbody">
          <div class="ptitle"><?= e(PHOTO_SLOTS[$slot]) ?></div>
          <div class="pratio"><?= e($group['hint']) ?></div>
          <input type="file" accept="image/jpeg,image/png,image/webp,.jfif,.jpg" aria-label="Выбрать фото для блока «<?= e(PHOTO_SLOTS[$slot]) ?>»">
          <button class="btn" type="button" data-pick>Изменить фото</button>
          <div class="pnote" role="status" aria-live="polite"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endforeach; ?>

  <script>
  (function () {
    var csrf = <?= json_encode($csrf) ?>;

    document.querySelectorAll('.pcard').forEach(function (card) {
      var input = card.querySelector('input[type="file"]');
      var pick  = card.querySelector('[data-pick]');
      var note  = card.querySelector('.pnote');
      var pic   = card.querySelector('.pic');
      var state = card.querySelector('.state');

      var say = function (text, cls) { note.textContent = text; note.className = 'pnote' + (cls ? ' ' + cls : ''); };

      pick.addEventListener('click', function () { input.click(); });

      input.addEventListener('change', function () {
        var file = input.files && input.files[0];
        if (!file) { return; }

        var img = pic.querySelector('img');
        if (!img) {
          img = document.createElement('img');
          img.alt = '';
          var empty = pic.querySelector('.empty');
          if (empty) { empty.remove(); }
          pic.insertBefore(img, state);
        }
        var local = URL.createObjectURL(file);
        img.src = local;
        state.textContent = 'Загружаем…';
        pick.disabled = true;
        say('');

        var body = new FormData();
        body.append('action', 'replace_photo');
        body.append('csrf', csrf);
        body.append('tab', 'photos');
        body.append('slot', card.dataset.slot);
        body.append('photo', file);

        fetch('admin.php', {
          method: 'POST',
          body: body,
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
          credentials: 'same-origin'
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (data && data.ok) {
              img.src = data.path + '?v=' + Date.now();
              state.textContent = 'Сейчас на сайте';
              say(data.message || 'Фото обновлено.', 'ok');
            } else {
              state.textContent = 'Не загружено';
              say((data && data.message) || 'Не удалось загрузить фото.', 'err');
            }
          })
          .catch(function () {
            state.textContent = 'Не загружено';
            say('Проблема со связью, попробуйте ещё раз.', 'err');
          })
          .finally(function () {
            pick.disabled = false;
            input.value = '';
            URL.revokeObjectURL(local);
          });
      });
    });
  })();
  </script>
<?php endif; ?>
</div>
</body>
</html>
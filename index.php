<?php
require_once __DIR__ . '/config.php';

if (function_exists('start_session')) {
    start_session();
} elseif (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$photos = glob(__DIR__ . '/uploads/*.{jpg,jpeg,jfif,png,webp,JPG,JPEG,JFIF,PNG,WEBP}', GLOB_BRACE) ?: [];
$photoUrls = array_map(static fn(string $path): string => 'uploads/' . rawurlencode(basename($path)), $photos);
$photo = static fn(int $index): string => $photoUrls ? $photoUrls[$index % count($photoUrls)] : 'uploads/placeholder.jpg';

$content = [];
if (is_file(CONTENT_FILE)) {
    $decoded = json_decode((string)file_get_contents(CONTENT_FILE), true);
    $content = is_array($decoded) ? $decoded : [];
}

$t = static function (string $path, string $fallback) use ($content): string {
    $value = get_path($content, $path);
    return is_string($value) && trim($value) !== '' ? $value : $fallback;
};
$list = static function (string $path, array $fallback) use ($content): array {
    $value = get_path($content, $path);
    return is_array($value) && $value !== [] ? $value : $fallback;
};

$phoneText   = $t('contacts.phone_text', '+7 (918) 089-22-54');
$phoneDigits = preg_replace('/\D/', '', $t('contacts.phone_raw', $phoneText)) ?? '';
$telHref     = 'tel:+' . $phoneDigits;
$waText      = rawurlencode('Здравствуйте! Хочу рассчитать мебель на заказ.');
$waHref      = $t('contacts.whatsapp', 'https://wa.me/' . $phoneDigits) . '?text=' . $waText;
$telegram    = $t('contacts.telegram', 'https://t.me');
$instagram   = $t('contacts.instagram', 'https://instagram.com');
$maxLink     = $t('contacts.max', 'https://max.ru');
$pinterest   = $t('contacts.pinterest', 'https://pin.it');
$address     = $t('contacts.address', 'г. Краснодар, ул. Конечная 2 (район Энка), 2 этаж');
$hours       = $t('contacts.hours', 'Ежедневно 10:00–19:00');
$mapQuery    = rawurlencode($address);

$heroKicker = $t('hero.kicker', 'Кухни · Шкафы · Прихожие');
$heroTitle  = $t('hero.title', 'Мебель на заказ');
$heroAccent = $t('hero.title_accent', 'в Краснодаре');
$heroSub    = $t('hero.sub', 'Крупное мебельное производство. Сохраним вам нервы, время и деньги.');
$heroFacts  = $list('hero.facts', [
    ['value' => 'от 100 тыс.', 'label' => 'кухни, ₽'],
    ['value' => '6 дней', 'label' => 'в неделю на связи'],
    ['value' => 'под ключ', 'label' => 'проект и монтаж'],
]);

$aboutEyebrow = $t('about.eyebrow', 'О нас');
$aboutTitle   = $t('about.title', 'Мебель под ваши размеры');
$aboutAccent  = $t('about.title_accent', 'без нервов');
$aboutBody    = $list('about.body', [
    'Мебельное производство в Краснодаре. Делаем кухни, шкафы, прихожие и мебель для салонов красоты по индивидуальным размерам.',
    'Клиенты в отзывах отдельно отмечают менеджера Ксению: считает проект, подсказывает, на чём можно сэкономить, и держит связь до монтажа.',
]);
$aboutFacts   = $list('about.facts', [
    ['value' => '100 тыс. ₽', 'label' => 'кухни от'],
    ['value' => 'Пн–Сб', 'label' => '9:00–17:00'],
    ['value' => '593', 'label' => 'работы в Instagram'],
]);

$formData     = is_array($content['form'] ?? null) ? $content['form'] : [];
$formEyebrow  = $t('form.eyebrow', 'Заявка на просчёт');
$formTitle    = $t('form.title', 'Сохраним нервы,');
$formAccent   = $t('form.title_accent', 'время и деньги');
$formLead     = $t('form.lead', 'Отправьте размеры или идею. Менеджер Ксения посчитает проект.');
$formSubmit   = $t('form.submit', 'Отправить заявку');
$formNote     = $t('form.note', 'Нажимая кнопку, вы соглашаетесь на обработку персональных данных.');
$services     = $list('form.services', ['Кухня на заказ', 'Столешница', 'Шкаф или прихожая', 'Замена фасадов', 'Замер и проект', 'Другое']);
$csrf         = csrf_token();

$reviewsScore = $t('reviews.score_value', '5/5');
$reviewsText  = $t('reviews.score_text', 'Оценки в показанных отзывах с Яндекс Карт и 2ГИС. Фрагменты из текстов клиентов.');
$reviews      = $list('reviews.items', [
    ['text' => 'Соотношение цена/качество отличное, лучшее я бы сказала в Краснодаре. Всё было сделано в срок.', 'name' => 'Инна Л.', 'source' => '2ГИС'],
    ['text' => 'Быстрые сроки изготовления, монтаж идеальный. Знакомые заказали кухню по рекомендации.', 'name' => 'Татьяна С.', 'source' => '2ГИС'],
    ['text' => 'Мы остались очень довольны сервисом и качеством работы, рекомендую от всей души.', 'name' => 'Д. Пономарева', 'source' => 'Яндекс Карты'],
]);

$stars = '<svg width="96" height="18" viewBox="0 0 120 22" fill="currentColor" aria-label="5 из 5">'
    . str_repeat('<path transform="translate(%d 0)" d="m11 1 3 6.4 7 .8-5.2 4.8 1.5 7L11 16.4 4.7 20l1.5-7L1 8.2l7-.8Z"/>', 0)
    . '</svg>';
$starsSvg = '<svg width="96" height="18" viewBox="0 0 120 22" fill="currentColor" aria-label="5 из 5">';
foreach ([0, 24, 48, 72, 96] as $x) {
    $starsSvg .= '<path transform="translate(' . $x . ' 0)" d="m11 1 3 6.4 7 .8-5.2 4.8 1.5 7L11 16.4 4.7 20l1.5-7L1 8.2l7-.8Z"/>';
}
$starsSvg .= '</svg>';

$iconPhone = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg>';
$iconChat  = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20l1.2-4A8 8 0 1 1 8 18.8Z"/><path d="M9 9c0 3 3 6 6 6l1.2-1.6-2-1-1 .6c-.8-.4-1.6-1.2-2-2l.6-1-1-2Z"/></svg>';
$iconLink  = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/></svg>';
$iconCam   = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17" cy="7" r=".6" fill="currentColor"/></svg>';

$renderLeadForm = static function (string $id) use ($csrf, $services, $formSubmit, $formNote): string {
    $options = '';
    foreach ($services as $service) {
        $options .= '<option value="' . e((string)$service) . '">' . e((string)$service) . '</option>';
    }
    return '<form class="lead-form" id="' . e($id) . '" method="post" action="api/submit.php" novalidate>'
        . '<input type="hidden" name="csrf" value="' . e($csrf) . '">'
        . '<input type="text" name="company" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">'
        . '<input type="text" name="name" placeholder="Ваше имя" autocomplete="name" required minlength="2" maxlength="80">'
        . '<input type="tel" name="phone" placeholder="Телефон" autocomplete="tel" required maxlength="40">'
        . '<select name="service" aria-label="Что нужно сделать"><option value="">Что нужно сделать</option>' . $options . '</select>'
        . '<textarea name="comment" placeholder="Размеры или пожелания" maxlength="1000"></textarea>'
        . '<button class="btn btn-oak" type="submit">' . e($formSubmit) . '</button>'
        . '<p class="form-status" role="status" aria-live="polite"></p>'
        . '<p class="form-note">' . e($formNote) . '</p>'
        . '</form>';
};
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#111111">
<title><?= e($t('site.title', 'Mebels.krd — кухни, шкафы и мебель на заказ в Краснодаре')) ?></title>
<meta name="description" content="<?= e($t('site.description', 'Крупное мебельное производство в Краснодаре. Кухни от 100 тыс. рублей, шкафы, прихожие. Заявка на просчёт.')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Golos+Text:wght@400;500;600;700;800&subset=cyrillic&display=swap" rel="stylesheet">
<style>
:root{--ink:#101210;--forest:#1A211D;--forest2:#26302A;--paper:#F3EFEA;--stone:#F3EFEA;--line:#D9D0C2;--muted:#5C574E;
--oak:#CF9A62;--oak-hi:#EDC593;--oak-dark:#8A5A2B;
--disp:'Oswald','Arial Narrow',sans-serif;--sans:'Golos Text',system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;--pad:24px;--r:28px}
*{box-sizing:border-box;margin:0;padding:0}
html,body{overflow-x:hidden;max-width:100%}
html{scroll-behavior:smooth;-webkit-text-size-adjust:100%}
body{font-family:var(--sans);font-size:16px;line-height:1.55;color:var(--ink);background:#111111}
img{display:block;max-width:100%;height:auto}
a{color:inherit;text-decoration:none}
button{font:inherit}
h1,h2,h3,.section-title,.stat-num{font-family:var(--disp);text-transform:uppercase}
.container{width:100%;max-width:1200px;margin:0 auto;padding:0 var(--pad)}
.section{padding:88px 0;position:relative}
.eyebrow{display:flex;align-items:center;gap:14px;font-size:12.5px;font-weight:700;letter-spacing:.28em;text-transform:uppercase;color:var(--oak-dark)}
.eyebrow::before{content:"";width:28px;height:2px;background:currentColor}
.on-dark .eyebrow{color:var(--oak)}
.h2{font-family:var(--disp);font-weight:700;font-size:clamp(44px,12.5vw,84px);line-height:.98;text-transform:uppercase;margin-top:18px;letter-spacing:.005em}
.h2 em{display:block;font-style:normal;color:var(--oak-dark)}
.on-dark .h2 em{color:var(--oak)}
.lead{font-size:17px;line-height:1.6;color:var(--muted);margin-top:20px;max-width:58ch}
.on-dark .lead{color:rgba(245,241,234,.76)}
.btn{display:flex;align-items:center;justify-content:center;gap:12px;min-height:60px;padding:0 28px;border-radius:999px;font-weight:700;font-size:16px;white-space:nowrap;transition:transform .2s;border:0;cursor:pointer}
.btn:active{transform:scale(.98)}
.btn:disabled{opacity:.6;cursor:wait}
.btn svg{flex:none}
.btn-oak{color:#1B1209;background:linear-gradient(135deg,var(--oak-hi),var(--oak));box-shadow:0 14px 30px -16px rgba(207,154,98,.9)}
.btn-glass{color:#fff;background:rgba(255,255,255,.08);border:1.5px solid rgba(255,255,255,.4);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px)}
.btn-line{color:var(--ink);border:1.5px solid rgba(16,18,16,.35)}

.sheet{position:relative;border-radius:28px 28px 0 0;margin-top:-24px;box-shadow:0 -26px 50px -30px rgba(0,0,0,.6)}
.bg-dark{background:radial-gradient(ellipse 70% 40% at 90% 0%,rgba(207,154,98,.2),transparent 70%),radial-gradient(ellipse 60% 40% at 0% 100%,rgba(90,120,100,.18),transparent 70%),url("<?= e($photo(0)) ?>"),#111111;color:#fff;overflow:hidden}
.bg-dark.alt{background:radial-gradient(ellipse 70% 40% at 90% 0%,rgba(207,154,98,.16),transparent 70%),url("<?= e($photo(1)) ?>"),#141414}
.bg-dark>.container{position:relative;z-index:1}
.bg-paper{background:radial-gradient(ellipse 90% 50% at 50% 0%,#fff,transparent 70%),url("<?= e($photo(2)) ?>"),var(--paper)}
.bg-stone{background:radial-gradient(ellipse 80% 50% at 15% 0%,rgba(255,255,255,.55),transparent 70%),url("<?= e($photo(3)) ?>"),var(--stone)}

.reveal{opacity:1;transform:none}
html.js .reveal{opacity:0;transform:translateY(24px);transition:opacity .8s ease,transform .8s ease}
html.js .reveal.in{opacity:1;transform:none}
@media (prefers-reduced-motion:reduce){html.js .reveal{opacity:1;transform:none;transition:none}html{scroll-behavior:auto}}

.header{position:fixed;top:0;left:0;right:0;z-index:50;padding:12px 0;background:rgba(16,18,16,.55);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border-bottom:1px solid rgba(255,255,255,.12);color:#fff;transition:background .3s,color .3s}
.header .container{display:flex;align-items:center;gap:12px}
.brand{display:flex;align-items:center;gap:12px;min-width:0;flex:1 1 auto}
.mark{flex:none;width:44px;height:44px;border-radius:50%;display:grid;place-items:center;font-family:var(--disp);font-weight:700;font-size:15px;letter-spacing:.04em;color:var(--oak-hi);background:var(--forest2);border:1.5px solid rgba(237,197,147,.55)}
.brand b{display:block;font-family:var(--disp);font-size:19px;font-weight:700;letter-spacing:.12em;line-height:1.1;white-space:nowrap}
.brand small{display:block;font-size:11.5px;color:rgba(255,255,255,.64);margin-top:3px;white-space:nowrap}
.nav{display:none;gap:28px;font-size:14.5px;font-weight:600}
.nav a{opacity:.85}.nav a:hover{opacity:1;color:var(--oak)}
.header .tel{display:none}
.burger{width:46px;height:46px;border-radius:50%;display:grid;place-items:center;border:1.5px solid rgba(255,255,255,.4);flex:none}
.burger i,.burger i::before{display:block;width:18px;height:2px;background:currentColor;border-radius:2px;position:relative}
.burger i::before{content:"";position:absolute;top:6px;left:0}.burger i{top:-3px}
.header.scrolled{background:rgba(245,241,234,.92);color:var(--ink);border-bottom-color:var(--line)}
.header.scrolled .brand small{color:var(--muted)}
.header.scrolled .burger{border-color:rgba(16,18,16,.3)}

.menu{position:fixed;inset:0;z-index:100;background:url("<?= e($photo(4)) ?>"),#101210;background-size:cover;color:#fff;padding:14px 0 28px;display:none;overflow:auto}
.menu:target,.menu.open{display:block}
.menu .top{display:flex;justify-content:space-between;align-items:center}
.menu .x{width:46px;height:46px;border-radius:50%;display:grid;place-items:center;border:1.5px solid rgba(255,255,255,.4);font-size:22px;line-height:1}
.menu ol{list-style:none;margin:30px 0 24px}
.menu li a{display:flex;align-items:baseline;gap:16px;padding:14px 0;border-bottom:1px solid rgba(255,255,255,.14);font-family:var(--disp);font-weight:600;text-transform:uppercase;font-size:clamp(30px,9vw,44px);line-height:1.1}
.menu li a span{font-family:var(--sans);font-size:13px;font-weight
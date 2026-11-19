<?php
require_once __DIR__ . '/config.php';

if (function_exists('start_session')) {
    start_session();
} elseif (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('e')) {
    function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }
}

$photos = glob(__DIR__ . '/uploads/*.{jpg,jpeg,jfif,png,webp,JPG,JPEG,JFIF,PNG,WEBP}', GLOB_BRACE) ?: [];
$photoUrls = array_map(static fn(string $path): string => 'uploads/' . basename($path), $photos);
$photo = static fn(int $index): string => $photoUrls ? $photoUrls[$index % count($photoUrls)] : 'uploads/placeholder.jpg';

$content = [];
$contentFile = __DIR__ . '/data/content.json';
if (is_file($contentFile)) {
    $decoded = json_decode((string)file_get_contents($contentFile), true);
    $content = is_array($decoded) ? $decoded : [];
}

$text = static function (string $key, string $fallback) use ($content): string {
    $node = $content;
    foreach (explode('.', $key) as $part) {
        if (!is_array($node) || !array_key_exists($part, $node)) {
            return $fallback;
        }
        $node = $node[$part];
    }
    return is_string($node) && trim($node) !== '' ? $node : $fallback;
};

$phoneText = $text('contacts.phone', '+7 (918) 089-22-54');
$phoneTel = 'tel:+79180892254';
$waHref = 'https://wa.me/79180892254?text=' . rawurlencode('Здравствуйте! Хочу рассчитать мебель на заказ.');
$telegram = $text('contacts.telegram', 'https://t.me');
$instagram = $text('contacts.instagram', 'https://instagram.com');
$maxLink = $text('contacts.max', 'https://max.ru');
$pinterest = $text('contacts.pinterest', 'https://pin.it');
$address = $text('contacts.address', 'г. Краснодар, ул. Конечная 2 (район Энка), 2 этаж');
$mapQuery = rawurlencode($address);

$services = ['Кухня на заказ', 'Шкаф или купе', 'Прихожая', 'Мебель для бизнеса', 'Другое'];
$csrf = csrf_token();

$iconPhone = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg>';
$iconChat = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20l1.2-4A8 8 0 1 1 8 18.8Z"/><path d="M9 9c0 3 3 6 6 6l1.2-1.6-2-1-1 .6c-.8-.4-1.6-1.2-2-2l.6-1-1-2Z"/></svg>';
$iconLink = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/></svg>';
$iconCam = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17" cy="7" r=".6" fill="currentColor"/></svg>';
$starPath = '<path transform="translate(%d 0)" d="m11 1 3 6.4 7 .8-5.2 4.8 1.5 7L11 16.4 4.7 20l1.5-7L1 8.2l7-.8Z"/>';
$stars = '<svg width="96" height="18" viewBox="0 0 120 22" fill="currentColor" aria-label="5 из 5">';
foreach ([0, 24, 48, 72, 96] as $offset) {
    $stars .= sprintf($starPath, $offset);
}
$stars .= '</svg>';

$renderLeadForm = static function (string $idPrefix) use ($csrf, $services, $stars): string {
    $options = '';
    foreach ($services as $service) {
        $options .= '<option value="' . e($service) . '">' . e($service) . '</option>';
    }
    return '<form class="lead-form" id="' . $idPrefix . '" method="post" action="api/submit.php" novalidate>'
        . '<input type="hidden" name="csrf" value="' . e($csrf) . '">'
        . '<input class="hp" type="text" name="company" value="" tabindex="-1" autocomplete="off" aria-hidden="true">'
        . '<input type="text" name="name" placeholder="Ваше имя" autocomplete="name" required minlength="2" maxlength="80">'
        . '<input type="tel" name="phone" placeholder="Телефон" autocomplete="tel" required maxlength="40">'
        . '<select name="service" aria-label="Что нужно сделать"><option value="">Что нужно сделать</option>' . $options . '</select>'
        . '<textarea name="comment" placeholder="Размеры или пожелания" maxlength="1000"></textarea>'
        . '<button class="btn btn-oak" type="submit">' . e($GLOBALS['text']('form.submit', 'Отправить заявку')) . '</button>'
        . '</form>';
};
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#111111">
<title><?= e($text('site.title', 'Mebels.krd — кухни, шкафы и мебель на заказ в Краснодаре')) ?></title>
<meta name="description" content="<?= e($text('site.description', 'Крупное мебельное производство в Краснодаре. Кухни от 100 тыс. рублей, шкафы, прихожие. Заявка на просчёт.')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Golos+Text:wght@400;500;600&subset=cyrillic&display=swap" rel="stylesheet">
<style>
:root{--ink:#101210;--forest:#1A211D;--forest2:#26302A;--paper:#F3EFEA;--stone:#F3EFEA;--line:#D9D0C2;--muted:#5C574E;--oak:#CF9A62;--oak-hi:#EDC593;--oak-dark:#8A5A2B;--disp:'Oswald','Arial Narrow',sans-serif;--sans:'Golos Text',system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;--pad:24px;--r:28px}
*{box-sizing:border-box;margin:0;padding:0}
html,body{overflow-x:hidden;max-width:100%}
html{scroll-behavior:smooth;-webkit-text-size-adjust:100%}
body{font-family:var(--sans);font-size:16px;line-height:1.55;color:var(--ink);background:#111111}
img{display:block;max-width:100%;height:auto}
a{color:inherit;text-decoration:none}
button,input,select,textarea{font:inherit}
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
.sheet{position:relative;border-radius:28px 28px 0 0;margin-top:-24px;box-shadow:0 -26px 50px -30px rgba(0,0,0,.6)}
.bg-dark{background:radial-gradient(ellipse 70% 40% at 90% 0%,rgba(207,154,98,.2),transparent 70%),radial-gradient(ellipse 60% 40% at 0% 100%,rgba(90,120,100,.18),transparent 70%),#111111;color:#fff;overflow:hidden}
.bg-dark.alt{background:radial-gradient(ellipse 70% 40% at 90% 0%,rgba(207,154,98,.16),transparent 70%),#141414}
.bg-dark>.container{position:relative;z-index:1}
.bg-paper{background:radial-gradient(ellipse 90% 50% at 50% 0%,#fff,transparent 70%),var(--paper)}
.bg-stone{background:radial-gradient(ellipse 80% 50% at 15% 0%,rgba(255,255,255,.55),transparent 70%),var(--stone)}

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

.menu{position:fixed;inset:0;z-index:100;background:#101210;color:#fff;padding:14px 0 28px;display:none;overflow:auto}
.menu:target,.menu.open{display:block}
.menu .top{display:flex;justify-content:space-between;align-items:center}
.menu .x{width:46px;height:46px;border-radius:50%;display:grid;place-items:center;border:1.5px solid rgba(255,255,255,.4);font-size:22px;line-height:1}
.menu ol{list-style:none;margin:30px 0 24px}
.menu li a{display:flex;align-items:baseline;gap:16px;padding:14px 0;border-bottom:1px solid rgba(255,255,255,.14);font-family:var(--disp);font-weight:600;text-transform:uppercase;font-size:clamp(30px,9vw,44px);line-height:1.1}
.menu li a span{font-family:var(--sans);font-size:13px;font-weight:700;letter-spacing:.2em;color:var(--oak)}
.menu .btns{display:grid;gap:12px;margin-top:24px}

.hero{position:relative;background:#111411;color:#fff;overflow:hidden;padding-bottom:104px}
.hero-photo{position:relative;padding-top:66px}
.hero-photo img{width:100%;height:auto}
.hero-photo::after{content:"";position:absolute;left:0;right:0;bottom:0;height:55%;background:linear-gradient(to bottom,rgba(17,20,17,0),#111411 92%)}
.hero-content{position:relative;z-index:2;margin-top:-26vw}
.kick{font-size:13px;font-weight:700;letter-spacing:.3em;text-transform:uppercase;color:var(--oak-hi);margin-top:16px}
.hero h1{font-family:var(--disp);font-weight:700;text-transform:uppercase;font-size:clamp(44px,12.6vw,120px);line-height:.94;letter-spacing:.005em;margin-top:12px}
.hero h1 em{display:block;font-style:normal;color:var(--oak)}
.hero .sub{font-size:17px;color:rgba(255,255,255,.82);margin-top:18px;max-width:34ch}
.hero-btns{display:grid;gap:12px;margin-top:26px}
.hfacts{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));margin-top:32px;border-top:1px solid rgba(255,255,255,.18)}
.hfacts div{padding:18px 6px 0}
.hfacts div+div{border-left:1px solid rgba(255,255,255,.18);padding-left:14px}
.hfacts b{display:block;white-space:nowrap;font-family:var(--disp);font-weight:700;font-size:clamp(18px,5.2vw,36px);line-height:1.1;color:var(--oak-hi)}
.hfacts span{display:block;font-size:12.5px;line-height:1.35;color:rgba(255,255,255,.68);margin-top:5px}

.about-facts{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));margin-top:34px;border-top:1px solid var(--line)}
.about-facts div{padding:20px 6px 0}
.about-facts div+div{border-left:1px solid var(--line);padding-left:14px}
.about-facts b{display:block;font-family:var(--disp);font-weight:700;font-size:clamp(19px,5.6vw,40px);line-height:1;color:var(--oak-dark);white-space:nowrap}
.about-facts span{display:block;font-size:12.5px;line-height:1.35;color:var(--muted);margin-top:6px}
.about-p{margin-top:20px;font-size:17px;line-height:1.65;color:#2E2B26;max-width:60ch}
.about-p+.about-p{margin-top:14px}

.gal{display:flex;gap:14px;overflow-x:auto;scroll-snap-type:x mandatory;padding:0 var(--pad) 8px;margin-top:34px;scrollbar-width:none;-webkit-overflow-scrolling:touch}
.gal::-webkit-scrollbar{display:none}
.slide{position:relative;flex:0 0 84%;max-width:520px;aspect-ratio:4/5;border-radius:var(--r);overflow:hidden;scroll-snap-align:center;background:#222}
.slide img{width:100%;height:100%;object-fit:cover}
.slide::after{content:"";position:absolute;inset:0;background:linear-gradient(to bottom,transparent 55%,rgba(16,18,16,.85))}
.slide .cap{position:absolute;z-index:2;left:20px;right:20px;bottom:18px}
.slide .cap small{display:block;font-size:12px;font-weight:700;letter-spacing:.22em;color:var(--oak-hi)}
.slide .cap b{display:block;font-family:var(--disp);font-weight:600;text-transform:uppercase;font-size:23px;line-height:1.1;margin-top:6px;letter-spacing:.02em}
.hint{margin-top:14px;font-size:13.5px;color:rgba(255,255,255,.6)}

.rows{margin-top:32px;border-bottom:1px solid rgba(16,18,16,.2)}
.row{display:grid;grid-template-columns:58px minmax(0,1fr);gap:8px;align-items:start;padding:22px 0;border-top:1px solid rgba(16,18,16,.2)}
.row .n{font-family:var(--disp);font-weight:600;font-size:20px;color:var(--oak-dark);padding-top:4px}
.row h3{font-family:var(--disp);font-weight:700;text-transform:uppercase;font-size:30px;line-height:1.05;letter-spacing:.01em}
.row p{margin-top:8px;font-size:15.5px;color:var(--muted)}
.row .tag{display:inline-block;margin-top:12px;padding:6px 14px;border-radius:999px;background:var(--forest);color:var(--oak-hi);font-size:13.5px;font-weight:700}

.kgrid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:34px}
.kgrid figure{position:relative;border-radius:22px;overflow:hidden;aspect-ratio:3/2;background:#222}
.kgrid figure img{width:100%;height:100%;object-fit:cover}
.kgrid .wide{grid-column:1/-1;aspect-ratio:4/5}
.kgrid .wide::after{content:"";position:absolute;inset:0;background:linear-gradient(to bottom,transparent 50%,rgba(16,18,16,.88))}
.kprice{position:absolute;z-index:2;left:22px;right:22px;bottom:22px}
.kprice small{display:block;font-size:12px;font-weight:700;letter-spacing:.24em;text-transform:uppercase;color:var(--oak-hi)}
.kprice b{display:block;font-family:var(--disp);font-weight:700;font-size:clamp(40px,11vw,64px);line-height:1;margin-top:6px}
.kprice span{display:block;font-size:14.5px;color:rgba(255,255,255,.8);margin-top:6px}
.kbtn{margin-top:22px}

.score{display:flex;align-items:center;gap:18px;margin-top:30px;padding:22px;border-radius:var(--r);background:var(--forest);color:#fff}
.score b{white-space:nowrap;font-family:var(--disp);font-weight:700;font-size:46px;line-height:1;color:var(--oak-hi)}
.score span{font-size:14.5px;color:rgba(245,241,234,.78);line-height:1.4}
.revs{display:flex;gap:14px;overflow-x:auto;scroll-snap-type:x mandatory;padding:0 var(--pad) 8px;margin-top:18px;scrollbar-width:none}
.revs::-webkit-scrollbar{display:none}
.rev{flex:0 0 82%;max-width:380px;scroll-snap-align:center;padding:24px 22px;border-radius:var(--r);background:#fff;border:1px solid var(--line);display:flex;flex-direction:column}
.rev .stars{color:#DDA03A}
.rev p{margin-top:12px;font-size:16px;line-height:1.55;color:#2C2A26}
.rev footer{margin-top:auto;padding-top:18px;font-size:13.5px;color:var(--muted)}
.rev footer b{display:block;font-size:15px;color:var(--ink)}

.steps{display:grid;gap:14px;margin-top:34px}
.step{display:flex;gap:18px;padding:22px;border-radius:var(--r);background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.16)}
.step .n{flex:none;width:52px;height:52px;border-radius:50%;display:grid;place-items:center;font-family:var(--disp);font-weight:700;font-size:20px;color:#1B1209;background:linear-gradient(135deg,var(--oak-hi),var(--oak))}
.step h3{font-family:var(--disp);font-weight:600;text-transform:uppercase;font-size:24px;line-height:1.1}
.step p{margin-top:8px;font-size:15px;color:rgba(245,241,234,.74)}

.cta{position:relative;color:#fff;overflow:hidden;background:#101210}
.cta .container{position:relative;z-index:1}
.cta .btns{display:grid;gap:12px;margin-top:28px}
.lead-form{display:grid;gap:12px;margin-top:28px;padding:22px;border-radius:var(--r);background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.18)}
.lead-form input,.lead-form select,.lead-form textarea{width:100%;padding:14px 16px;border-radius:16px;border:1px solid rgba(255,255,255,.22);background:rgba(16,18,16,.5);color:#fff;font-size:16px}
.lead-form select option{color:#101210}
.lead-form textarea{min-height:96px;resize:vertical}
.lead-form ::placeholder{color:rgba(255,255,255,.55)}
.lead-form .btn{width:100%}
.hp{position:absolute!important;left:-9999px!important;width:1px!important;height:1px!important;opacity:0!important}
.form-note{font-size:12.5px;color:rgba(245,241,234,.6);text-align:center}

.cards{display:grid;gap:16px;margin-top:34px}
.ccard{border-radius:var(--r);padding:24px 22px;background:#fff;border:1px solid var(--line)}
.ccard.dark{background:var(--forest);color:#fff;border-color:transparent}
.ccard h3{font-family:var(--disp);font-weight:600;text-transform:uppercase;font-size:26px;line-height:1.1}
.ccard p{margin-top:10px;color:var(--muted);font-size:15.5px}
.ccard.dark p{color:rgba(245,241,234,.76)}
.mapbtns{display:flex;flex-wrap:wrap;gap:10px;margin-top:18px}
.mapbtns a{display:inline-flex;align-items:center;gap:8px;padding:11px 18px;border-radius:999px;font-weight:700;font-size:14.5px;background:var(--forest);color:#fff}
.mapbtns a+a{background:transparent;color:var(--ink);border:1.5px solid rgba(16,18,16,.3)}
.open{display:flex;align-items:center;gap:12px;font-family:var(--disp);font-weight:600;font-size:30px;margin-top:12px;color:var(--oak-hi);letter-spacing:.02em}
.open i{width:10px;height:10px;border-radius:50%;background:#7CE38B;box-shadow:0 0 0 5px rgba(124,227,139,.2)}
.clist a{display:flex;align-items:center;gap:16px;padding:14px 0;border-bottom:1px solid var(--line)}
.clist>a:last-child{border-bottom:0}
.clist .ic{flex:none;width:46px;height:46px;border-radius:50%;display:grid;place-items:center;background:var(--forest);color:var(--oak-hi)}
.clist small{display:block;font-size:11.5px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:var(--oak-dark)}
.clist b{display:block;font-size:16.5px;font-weight:700;overflow-wrap:anywhere}

.footer{padding:38px 0 120px;color:rgba(255,255,255,.6);font-size:13.5px}
.footer .container{display:grid;gap:10px}
.footer b{font-family:var(--disp);letter-spacing:.14em;color:#fff;font-size:18px}

.dock{position:fixed;left:12px;right:12px;bottom:calc(12px + env(safe-area-inset-bottom));z-index:40;display:flex;gap:10px;padding:10px;border-radius:999px;background:rgba(16,18,16,.86);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.24);box-shadow:0 18px 40px -16px rgba(0,0,0,.8);transition:transform .4s}
html.js .dock{transform:translateY(160%)}
html.js .dock.show{transform:none}
.dock .btn{min-height:52px;flex:1;padding:0 18px}
.dock .ico{flex:none;width:52px;min-height:52px;padding:0;color:var(--oak-hi);border:1.5px solid rgba(255,255,255,.38);border-radius:50%}

.modal{position:fixed;inset:0;z-index:200;display:none;align-items:center;justify-content:center;padding:16px;background:rgba(10,12,10,.7);backdrop-filter:blur(6px)}
.modal.open{display:flex}
.modal-box{position:relative;width:100%;max-width:520px;max-height:92vh;overflow:auto;border-radius:var(--r);background:#141414}
.modal-box .lead-form{margin-top:0;border-radius:var(--r)}
.modal-close{position:absolute;top:12px;right:12px;z-index:2;width:44px;height:44px;border-radius:50%;border:1.5px solid rgba(255,255,255,.4);background:rgba(16,18,16,.6);color:#fff;font-size:22px;cursor:pointer}
.modal-title{padding:28px 22px 0;font-family:var(--disp);font-weight:700;text-transform:uppercase;font-size:34px;line-height:1.05;color:var(--oak-hi)}
.modal-title+.lead-form{margin-top:12px}

.toast{position:fixed;left:50%;bottom:24px;z-index:300;transform:translate(-50%,160%);max-width:calc(100% - 32px);padding:16px 22px;border-radius:18px;background:var(--forest);color:#fff;font-weight:600;box-shadow:0 18px 40px -16px rgba(0,0,0,.8);transition:transform .4s;text-align:center}
.toast.show{transform:translate(-50%,0)}
.toast.error{background:#7A2A1C}

@media (min-width:640px){.hero-btns,.cta .btns{grid-template-columns:auto auto;justify-content:start}}
@media (min-width:960px){
 :root{--pad:40px}
 .section{padding:120px 0}
 .nav{display:flex}.burger{display:none}
 .brand{flex:0 0 auto;margin-right:auto}
 .header .tel{display:inline-flex;margin-left:26px;min-height:46px;padding:0 22px;font-size:14.5px}
 .dock{display:none}
 .hero{padding:0 0 124px;min-height:100vh;display:flex;align-items:center}
 .hero .wrap{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(0,.9fr);gap:56px;align-items:center;padding:120px 40px 0;width:100%;max-width:1200px;margin:0 auto}
 .hero-photo{order:2;padding-top:0;border-radius:var(--r);overflow:hidden;border:1px solid rgba(255,255,255,.22);box-shadow:0 40px 80px -30px #000}
 .hero-photo::after{display:none}
 .hero-content{order:1;margin-top:0;padding:0}
 .hero h1{font-size:clamp(52px,5.4vw,82px)}
 .about-facts b{font-size:30px}
 .h2{font-size:clamp(56px,6vw,84px)}
 .about-grid{display:grid;grid-template-columns:1fr 1fr;gap:64px;align-items:start}
 .rows{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));column-gap:48px}
 .kgrid{grid-template-columns:repeat(4,minmax(0,1fr))}
 .kgrid .wide{grid-column:span 2;grid-row:span 2;aspect-ratio:auto}
 .gal{padding-left:max(40px,calc((100vw - 1200px)/2 + 40px))}
 .slide{flex-basis:400px}
 .revs{padding-left:max(40px,calc((100vw - 1200px)/2 + 40px))}
 .rev{flex-basis:360px}
 .cards{grid-template-columns:repeat(3,minmax(0,1fr));gap:20px}
 .footer{padding-bottom:56px}
 .footer .container{grid-template-columns:1fr auto;align-items:center}
}

/* Принудительный показ блоков: снимает opacity:0, если скрипт появления не сработал */
section, .reveal, [data-reveal], .fade-up { opacity: 1 !important; transform: none !important; visibility: visible !important; }
</style>
</head>
<body>

<header class="header" id="hdr"><div class="container">
 <a class="brand" href="#top"><span class="mark">MK</span><span><b>MEBELS.KRD</b><small>Мебель на заказ · Краснодар</small></span></a>
 <nav class="nav"><a href="#works">Работы</a><a href="#kinds">Что делаем</a><a href="#kitchens">Кухни</a><a href="#reviews">Отзывы</a><a href="#contacts">Контакты</a></nav>
 <button class="btn btn-oak tel" type="button" data-open-lead>Заявка на просчёт</button>
 <a class="burger" href="#menu" aria-label="Меню"><i></i></a>
</div></header>

<div class="menu" id="menu"><div class="container">
 <div class="top"><span class="brand"><span class="mark">MK</span><span><b>MEBELS.KRD</b></span></span><a class="x" href="#top" aria-label="Закрыть">&times;</a></div>
 <ol>
  <li><a href="#works"><span>01</span>Работы</a></li>
  <li><a href="#kinds"><span>02</span>Что делаем</a></li>
  <li><a href="#kitchens"><span>03</span>Кухни</a></li>
  <li><a href="#reviews"><span>04</span>Отзывы</a></li>
  <li><a href="#contacts"><span>05</span>Контакты</a></li>
 </ol>
 <div class="btns">
  <button class="btn btn-oak" type="button" data-open-lead><?= $iconChat ?> Заявка на просчёт</button>
  <a class="btn btn-glass" href="<?= e($phoneTel) ?>"><?= $iconPhone ?> <?= e($phoneText) ?></a>
 </div>
 <p>Краснодар, ул. Конечная, 2 · пн–сб 9:00–17:00</p>
</div></div>

<main id="top">
<section class="hero"><div class="wrap">
 <div class="hero-photo"><img src="<?= e($photo(0)) ?>" alt="Шкаф-прихожая в матовом зелёном фасаде и дубе" width="960" height="1195"></div>
 <div class="hero-content container">
  <p class="kick"><?= e($text('hero.kicker', 'Кухни · Шкафы · Прихожие')) ?></p>
  <h1><?= e($text('hero.title', 'Мебель на заказ')) ?><em><?= e($text('hero.title_accent', 'в Краснодаре')) ?></em></h1>
  <p class="sub"><?= e($text('hero.sub', 'Крупное мебельное производство. Сохраним вам нервы, время и деньги.')) ?></p>
  <div class="hero-btns">
   <button class="btn btn-oak" type="button" data-open-lead><?= $iconChat ?> Оставить заявку на просчёт</button>
   <a class="btn btn-glass" href="<?= e($phoneTel) ?>"><?= $iconPhone ?> Позвонить</a>
  </div>
  <div class="hfacts">
   <div><b>от 100 тыс.</b><span>кухни, ₽</span></div>
   <div><b>6 дней</b><span>в неделю на связи</span></div>
   <div><b>под ключ</b><span>проект и монтаж</span></div>
  </div>
 </div>
</div></section>

<section class="section sheet bg-paper" id="about"><div class="container reveal">
 <div class="about-grid"><div>
  <div class="eyebrow"><?= e($text('about.eyebrow', 'О нас')) ?></div>
  <h2 class="h2"><?= e($text('about.title', 'Мебель под ваши размеры')) ?><em><?= e($text('about.title_accent', 'без нервов')) ?></em></h2>
 </div><div>
  <p class="about-p"><?= e($text('about.text_1', 'Мебельное производство в Краснодаре. Делаем кухни, шкафы, прихожие и мебель для салонов красоты по индивидуальным размерам.')) ?></p>
  <p class="about-p"><?= e($text('about.text_2', 'Клиенты в отзывах отдельно отмечают менеджера Ксению: считает проект, подсказывает, на чём можно сэкономить, и держит связь до монтажа.')) ?></p>
  <div class="about-facts">
   <div><b>100 тыс. ₽</b><span>кухни от</span></div>
   <div><b>Пн–Сб</b><span>9:00–17:00</span></div>
   <div><b>593</b><span>работы в Instagram</span></div>
  </div>
 </div></div>
</div></section>

<section class="section sheet bg-dark on-dark" id="works">
 <div class="container reveal">
  <div class="eyebrow">Наши работы</div>
  <h2 class="h2">Шкафы, которые<em>делаем сами</em></h2>
  <p class="lead">Реальные проекты из нашего Instagram. Листайте.</p>
 </div>
 <div class="gal reveal">
  <?php
  $slides = [
      ['Шкаф-прихожая, матовый фасад и дерево', 1],
      ['Большой проект, ЖК «Большой»', 2],
      ['Классика с фрезерованными фасадами', 3],
      ['Прихожая во всю стену', 4],
      ['Шкаф с зеркальными дверями', 5],
      ['Шкаф-купе со стеллажами', 6],
      ['Витрина со стеклом', 7],
      ['Угловой шкаф до потолка', 8],
      ['Прихожая с зеркалом и комодом', 9],
  ];
  foreach ($slides as $number => [$caption, $index]) : ?>
  <div class="slide"><img src="<?= e($photo($index)) ?>" alt="<?= e($caption) ?>" loading="<?= $number === 0 ? 'eager' : 'lazy' ?>"><div class="cap"><small><?= sprintf('%02d / %02d', $number + 1, count($slides)) ?></small><b><?= e($caption) ?></b></div></div>
  <?php endforeach; ?>
 </div>
 <div class="container"><p class="hint">Больше работ в Instagram @mebels.krd</p></div>
</section>

<section class="section sheet bg-stone" id="kinds"><div class="container reveal">
 <div class="eyebrow">Что делаем</div>
 <h2 class="h2">Четыре<em>направления</em></h2>
 <div class="rows">
  <div class="row"><span class="n">01</span><div><h3>Кухни</h3><p
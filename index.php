<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

$c      = content();
$photos = page_photos($c);

$site  = $c['site'];
$ct    = $c['contacts'];
$hero  = $c['hero'];
$about = $c['about'];
$gal   = $c['gallery'];
$works = $c['works'];
$kit   = $c['kitchens'];
$revs  = $c['reviews'];
$steps = $c['steps'];
$form  = $c['form'];
$cs    = $c['contacts_section'];

$tel     = 'tel:' . $ct['phone_raw'];
$wa      = (string)$ct['whatsapp'];
$mailto  = 'mailto:' . $ct['email'];
$manager = (string)($ct['manager'] ?? '');
$csrf    = csrf_token();
$sent    = (string)($_GET['sent'] ?? '');
$allFiles = upload_files();

/* Кухни: три слота, при пустом — подставляем свободный файл, чтобы не было дыр. */
$kitchenSlots  = ['k1', 'k2', 'k3'];
$kitchenPhotos = [];
foreach ($kitchenSlots as $index => $slot) {
    $path = (string)($photos[$slot] ?? '');
    $kitchenPhotos[$index] = $path !== '' ? $path : ($allFiles[$index] ?? '');
}

$heroPhoto = (string)($photos['hero'] ?? '');
if ($heroPhoto === '') {
    $heroPhoto = $allFiles[0] ?? '';
}

/* Галерея — все свободные фото без повторов с первым экраном и кухнями. */
$busyFile = array_filter(array_merge([$heroPhoto], $kitchenPhotos));
$freeFiles = array_values(array_diff($allFiles, $busyFile));
if (count($freeFiles) < 3) {
    $freeFiles = $allFiles;
}

$socials = array_values(array_filter([
    ['TE', 'Telegram',  (string)($ct['telegram'] ?? '')],
    ['IG', 'Instagram', (string)($ct['instagram'] ?? '')],
    ['MX', 'MAX',       (string)($ct['max'] ?? '')],
    ['PT', 'Pinterest', (string)($ct['pinterest'] ?? '')],
], static fn(array $item): bool => $item[2] !== ''));

function photo_box(string $path, string $alt, bool $lazy = true): string
{
    if ($path === '') {
        return '<div class="noimg"><span>Добавьте<br>фото<br>в админке</span></div>';
    }

    $attr = $lazy ? ' loading="lazy" decoding="async"' : '';

    return '<img src="' . e($path) . '" alt="' . e($alt) . '"' . $attr . '>';
}

function facts_html(array $facts): string
{
    $html = '';
    foreach ($facts as $fact) {
        $html .= '<div><b>' . e((string)($fact['value'] ?? '')) . '</b>'
            . '<span>' . e((string)($fact['label'] ?? '')) . '</span></div>';
    }

    return $html;
}

function socials_html(array $socials): string
{
    if ($socials === []) {
        return '';
    }

    $html = '<div class="socials">';
    foreach ($socials as [$code, $label, $url]) {
        $html .= '<a href="' . e($url) . '" target="_blank" rel="noopener">'
            . '<i>' . e($code) . '</i>' . e($label) . '</a>';
    }

    return $html . '</div>';
}

function lead_form(string $id, array $form, string $csrf, string $submit = ''): string
{
    $html = '<form id="' . e($id) . '" data-lead-form action="api/submit.php" method="post" novalidate>'
        . '<input type="hidden" name="csrf" value="' . e($csrf) . '">'
        . '<div class="lf-hp"><input type="text" name="company" tabindex="-1" autocomplete="off" aria-hidden="true"></div>'
        . '<label class="lf-field" data-field="name"><span>Ваше имя</span>'
        . '<input type="text" name="name" autocomplete="name" placeholder="Как к вам обращаться" required>'
        . '<i class="lf-err">Укажите имя — хотя бы 2 символа</i></label>'
        . '<label class="lf-field" data-field="phone"><span>Телефон</span>'
        . '<input type="tel" name="phone" autocomplete="tel" inputmode="tel" placeholder="+7 (___) ___-__-__" required>'
        . '<i class="lf-err">Проверьте номер: нужно 10–15 цифр</i></label>'
        . '<label class="lf-field" data-field="service"><span>Что нужно</span><select name="service">';

    foreach ($form['services'] as $service) {
        $html .= '<option value="' . e((string)$service) . '">' . e((string)$service) . '</option>';
    }

    return $html . '</select></label>'
        . '<label class="lf-field"><span>Комментарий</span>'
        . '<textarea name="comment" rows="3" placeholder="Размеры кухни, материалы, сроки"></textarea></label>'
        . '<button class="btn btn-oak" type="submit">' . e($submit !== '' ? $submit : (string)$form['submit']) . '</button>'
        . '</form>';
}

$navItems = [
    'about'    => $about['eyebrow'],
    'works'    => $gal['eyebrow'],
    'services' => $works['eyebrow'],
    'kitchens' => $kit['eyebrow'],
    'reviews'  => $revs['eyebrow'],
    'contacts' => $cs['eyebrow'],
];
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#111411">
<title><?= e($site['title']) ?></title>
<meta name="description" content="<?= e($site['description']) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Golos+Text:wght@400;500;600;700;800&family=Oswald:wght@600;700&display=swap" rel="stylesheet">
<style>
:root{--ink:#101210;--forest:#1A211D;--forest2:#26302A;--paper:#F5F1EA;--stone:#E4DCCF;--line:#D9D0C2;--muted:#5C574E;
--oak:#CF9A62;--oak-hi:#EDC593;--oak-dark:#8A5A2B;
--disp:'Oswald','Arial Narrow',sans-serif;--sans:'Golos Text',system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;--pad:24px;--r:28px}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth;-webkit-text-size-adjust:100%}
body{font-family:var(--sans);font-size:16px;line-height:1.55;color:var(--ink);background:var(--ink);overflow-x:hidden}
img{display:block;max-width:100%;height:auto}
a{color:inherit;text-decoration:none}
button{font-family:inherit}
section[id]{scroll-margin-top:84px}
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
.btn{display:flex;align-items:center;justify-content:center;gap:12px;min-height:60px;padding:0 28px;border:0;border-radius:999px;font-family:inherit;font-weight:700;font-size:16px;white-space:nowrap;cursor:pointer;transition:transform .2s}
.btn:active{transform:scale(.98)}
.btn svg{flex:none}
.btn-oak{color:#1B1209;background:linear-gradient(135deg,var(--oak-hi),var(--oak));box-shadow:0 14px 30px -16px rgba(207,154,98,.9)}
.btn-glass{color:#fff;background:rgba(255,255,255,.08);border:1.5px solid rgba(255,255,255,.4);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px)}
.btn-dark{color:#fff;background:var(--forest)}
.btn[disabled]{opacity:.65;pointer-events:none}
.bg-dark{background:radial-gradient(ellipse 70% 40% at 90% 0%,rgba(207,154,98,.2),transparent 70%),radial-gradient(ellipse 60% 40% at 0% 100%,rgba(90,120,100,.18),transparent 70%),repeating-linear-gradient(90deg,rgba(237,197,147,.05) 0 1.4px,transparent 1.4px 11px),#111411;color:#fff;overflow:hidden}
.bg-paper{background:radial-gradient(ellipse 90% 50% at 50% 0%,#fff,transparent 70%),repeating-linear-gradient(90deg,rgba(107,74,34,.07) 0 1.4px,transparent 1.4px 11px),var(--paper)}
.bg-stone{background:radial-gradient(ellipse 80% 50% at 15% 0%,rgba(255,255,255,.55),transparent 70%),repeating-linear-gradient(90deg,rgba(255,255,255,.5) 0 1.4px,transparent 1.4px 11px),var(--stone)}
.reveal{opacity:1;transform:none}

.header{position:fixed;top:0;left:0;right:0;z-index:50;padding:12px 0;background:rgba(16,18,16,.55);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border-bottom:1px solid rgba(255,255,255,.12);color:#fff;transition:background .3s,color .3s}
.header .container{display:flex;align-items:center;gap:12px}
.brand{display:flex;align-items:center;gap:12px;min-width:0;flex:1 1 auto}
.mark{flex:none;width:44px;height:44px;border-radius:50%;display:grid;place-items:center;font-family:var(--disp);font-weight:700;font-size:15px;letter-spacing:.04em;color:var(--oak-hi);background:var(--forest2);border:1.5px solid rgba(237,197,147,.55)}
.brand b{display:block;font-family:var(--disp);font-size:19px;font-weight:700;letter-spacing:.12em;line-height:1.1;white-space:nowrap}
.brand small{display:block;font-size:11.5px;color:rgba(255,255,255,.64);margin-top:3px;white-space:nowrap}
.nav{display:none;gap:26px;font-size:14.5px;font-weight:600}
.nav a{opacity:.85}
.nav a:hover{opacity:1;color:var(--oak)}
.header .tel{display:none}
.burger{width:46px;height:46px;border-radius:50%;display:grid;place-items:center;border:1.5px solid rgba(255,255,255,.4);flex:none}
.burger i,.burger i::before{display:block;width:18px;height:2px;background:currentColor;border-radius:2px;position:relative}
.burger i::before{content:"";position:absolute;top:6px;left:0}
.burger i{top:-3px}
.header.scrolled{background:rgba(245,241,234,.94);color:var(--ink);border-bottom-color:var(--line)}
.header.scrolled .brand small{color:var(--muted)}
.header.scrolled .burger{border-color:rgba(16,18,16,.3)}

.menu{position:fixed;inset:0;z-index:100;background:#101210;color:#fff;padding:14px 0 28px;display:none;overflow:auto}
.menu:target{display:block}
.menu .top{display:flex;justify-content:space-between;align-items:center;gap:12px}
.menu .x{width:46px;height:46px;border-radius:50%;display:grid;place-items:center;border:1.5px solid rgba(255,255,255,.4);font-size:22px;line-height:1;flex:none}
.menu ol{list-style:none;margin:30px 0 24px}
.menu li a{display:flex;align-items:baseline;gap:16px;padding:14px 0;border-bottom:1px solid rgba(255,255,255,.14);font-family:var(--disp);font-weight:600;text-transform:uppercase;font-size:clamp(28px,8.5vw,44px);line-height:1.1}
.menu li a span{font-family:var(--sans);font-size:13px;font-weight:700;letter-spacing:.2em;color:var(--oak)}
.menu .btns{display:grid;gap:12px;margin-top:24px}
.menu p{font-size:14px;color:rgba(255,255,255,.62);margin-top:18px;line-height:1.6}

.hero{position:relative;background:#111411;color:#fff;overflow:hidden;padding-bottom:88px}
.hero-photo{position:relative;padding-top:62px;overflow:hidden}
.hero-photo img{width:100%;max-height:78vh;min-height:320px;object-fit:cover}
.hero-photo .noimg{aspect-ratio:4/5}
.hero-photo::after{content:"";position:absolute;left:0;right:0;bottom:0;height:58%;background:linear-gradient(to bottom,rgba(17,20,17,0),#111411 94%)}
.hero-content{position:relative;z-index:2;margin-top:-16vh;padding:0 var(--pad)}
.kick{font-size:13px;font-weight:700;letter-spacing:.3em;text-transform:uppercase;color:var(--oak-hi);margin-top:16px}
.hero h1{font-family:var(--disp);font-weight:700;text-transform:uppercase;font-size:clamp(44px,12.6vw,120px);line-height:.94;letter-spacing:.005em;margin-top:12px}
.hero h1 em{display:block;font-style:normal;color:var(--oak)}
.hero .sub{font-size:17px;color:rgba(255,255,255,.82);margin-top:18px;max-width:36ch}
.hero-btns{display:grid;gap:12px;margin-top:26px}
.hfacts{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));margin-top:32px;border-top:1px solid rgba(255,255,255,.18)}
.hfacts div{padding:18px 6px 0}
.hfacts div+div{border-left:1px solid rgba(255,255,255,.18);padding-left:14px}
.hfacts b{display:block;white-space:nowrap;font-family:var(--disp);font-weight:700;font-size:clamp(18px,5.2vw,36px);line-height:1.1;color:var(--oak-hi)}
.hfacts span{display:block;font-size:12.5px;line-height:1.35;color:rgba(255,255,255,.68);margin-top:5px}

.about-facts{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));margin:0 0 24px;border-top:1px solid var(--line)}
.about-facts div{padding:20px 6px 0}
.about-facts div+div{border-left:1px solid var(--line);padding-left:14px}
.about-facts b{display:block;font-family:var(--disp);font-weight:700;font-size:clamp(19px,5.6vw,40px);line-height:1;color:var(--oak-dark);white-space:nowrap}
.about-facts span{display:block;font-size:12.5px;line-height:1.35;color:var(--muted);margin-top:6px}
.about-p{margin-top:16px;font-size:17px;line-height:1.65;color:#2E2B26;max-width:60ch}

.gal{display:flex;gap:14px;overflow-x:auto;scroll-snap-type:x mandatory;padding:0 var(--pad) 8px;margin-top:34px;scrollbar-width:none;-webkit-overflow-scrolling:touch}
.gal::-webkit-scrollbar{display:none}
.slide{position:relative;flex:0 0 84%;max-width:520px;aspect-ratio:4/5;border-radius:var(--r);overflow:hidden;scroll-snap-align:center;background:#222;margin:0}
.slide img{width:100%;height:100%;object-fit:cover}
.slide .noimg{height:100%}
.slide::after{content:"";position:absolute;inset:0;background:linear-gradient(to bottom,transparent 58%,rgba(16,18,16,.8))}
.slide .num{position:absolute;z-index:2;top:16px;left:16px;padding:7px 13px;border-radius:999px;background:rgba(16,18,16,.62);border:1px solid rgba(255,255,255,.32);font-size:12px;font-weight:700;letter-spacing:.18em;color:#fff}
.hint{margin-top:14px;font-size:13.5px;color:rgba(255,255,255,.6)}

.rows{margin-top:32px;border-bottom:1px solid rgba(16,18,16,.2)}
.row{display:grid;grid-template-columns:56px minmax(0,1fr);gap:8px;align-items:start;padding:22px 0;border-top:1px solid rgba(16,18,16,.2)}
.row .n{font-family:var(--disp);font-weight:600;font-size:20px;color:var(--oak-dark);padding-top:4px}
.row h3{font-family:var(--disp);font-weight:700;text-transform:uppercase;font-size:29px;line-height:1.05}
.row p{margin-top:8px;font-size:15.5px;color:var(--muted)}
.row .tag{display:inline-block;margin-top:12px;padding:6px 14px;border-radius:999px;background:var(--forest);color:var(--oak-hi);font-size:13.5px;font-weight:700}

.kgrid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:34px}
.kgrid figure{position:relative;border-radius:22px;overflow:hidden;aspect-ratio:3/2;background:#222;margin:0}
.kgrid figure img{width:100%;height:100%;object-fit:cover}
.kgrid figure .noimg{height:100%}
.kgrid figure::after{content:"";position:absolute;inset:0;background:linear-gradient(to bottom,transparent 45%,rgba(16,18,16,.86))}
.kgrid .wide{grid-column:1/-1;aspect-ratio:4/5}
.kprice{position:absolute;z-index:2;left:16px;right:16px;bottom:14px}
.kprice small{display:block;font-size:10.5px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:var(--oak-hi)}
.kprice b{display:block;font-family:var(--disp);font-weight:700;font-size:clamp(20px,5.6vw,28px);line-height:1.05;margin-top:5px}
.kprice span{display:none}
.kgrid .wide .kprice{left:22px;right:22px;bottom:22px}
.kgrid .wide .kprice small{font-size:12px;letter-spacing:.24em}
.kgrid .wide .kprice b{font-size:clamp(40px,11vw,64px);margin-top:6px}
.kgrid .wide .kprice span{display:block;font-size:14.5px;color:rgba(255,255,255,.82);margin-top:6px}
.kbtn{margin-top:24px}

.score{display:flex;align-items:center;gap:18px;margin-top:30px;padding:22px;border-radius:var(--r);background:var(--forest);color:#fff}
.score b{white-space:nowrap;font-family:var(--disp);font-weight:700;font-size:46px;line-height:1;color:var(--oak-hi)}
.score span{font-size:14.5px;color:rgba(245,241,234,.78);line-height:1.4}
.revs{display:flex;gap:14px;overflow-x:auto;scroll-snap-type:x mandatory;padding:0 var(--pad) 8px;margin-top:18px;scrollbar-width:none}
.revs::-webkit-scrollbar{display:none}
.rev{flex:0 0 82%;max-width:380px;scroll-snap-align:center;padding:24px 22px;border-radius:var(--r);background:#fff;border:1px solid var(--line);display:flex;flex-direction:column;margin:0}
.rev .stars{color:#DDA03A;letter-spacing:2px;font-size:16px}
.rev p{margin-top:12px;font-size:16px;line-height:1.55;color:#2C2A26}
.rev footer{margin-top:auto;padding-top:18px;font-size:13.5px;color:var(--muted)}
.rev footer b{display:block;font-size:15px;color:var(--ink)}

.steps{display:grid;gap:14px;margin-top:34px}
.step{display:flex;gap:18px;padding:22px;border-radius:var(--r);background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.16)}
.step .n{flex:none;width:52px;height:52px;border-radius:50%;display:grid;place-items:center;font-family:var(--disp);font-weight:700;font-size:20px;color:#1B1209;background:linear-gradient(135deg,var(--oak-hi),var(--oak))}
.step h3{font-family:var(--disp);font-weight:600;text-transform:uppercase;font-size:23px;line-height:1.1}
.step p{margin-top:8px;font-size:15px;color:rgba(245,241,234,.74)}

.lead-grid{display:grid;gap:26px;margin-top:34px}
.leadcard{background:var(--forest);color:#fff;border-radius:var(--r);padding:26px 22px;box-shadow:0 30px 60px -34px rgba(16,18,16,.8)}
.leadcard h3{font-family:var(--disp);font-weight:600;text-transform:uppercase;font-size:26px;line-height:1.1}
.leadcard .intro{margin-top:8px;font-size:14.5px;color:rgba(245,241,234,.7)}
.lf-field{display:block;margin-top:16px}
.lf-field>span{display:block;font-size:11.5px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:rgba(237,197,147,.92);margin-bottom:8px}
.lf-field input,.lf-field select,.lf-field textarea{width:100%;min-height:54px;padding:14px 16px;border-radius:14px;border:1.5px solid rgba(255,255,255,.22);background:rgba(255,255,255,.06);color:#fff;font-family:inherit;font-size:16px;line-height:1.4;appearance:none;-webkit-appearance:none}
.lf-field textarea{min-height:104px;resize:vertical}
.lf-field select{background-image:linear-gradient(45deg,transparent 49%,rgba(255,255,255,.7) 50%),linear-gradient(-45deg,transparent 49%,rgba(255,255,255,.7) 50%);background-size:6px 6px,6px 6px;background-position:calc(100% - 22px) 24px,calc(100% - 16px) 24px;background-repeat:no-repeat}
.lf-field select option{color:#101210}
.lf-field input::placeholder,.lf-field textarea::placeholder{color:rgba(255,255,255,.38)}
.lf-field input:focus,.lf-field select:focus,.lf-field textarea:focus{outline:none;border-color:var(--oak);background:rgba(255,255,255,.11)}
.lf-field.invalid input,.lf-field.invalid textarea{border-color:#E2715A}
.lf-err{display:none;margin-top:7px;font-size:13px;font-style:normal;color:#F0A593}
.lf-field.invalid .lf-err{display:block}
.lf-hp{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden}
.leadcard .btn{width:100%;margin-top:20px}
.lf-note{margin-top:14px;font-size:12.5px;line-height:1.5;color:rgba(245,241,234,.6)}

.cards{display:grid;gap:16px;margin-top:34px}
.ccard{border-radius:var(--r);padding:24px 22px;background:#fff;border:1px solid var(--line)}
.ccard.dark{background:var(--forest);color:#fff;border-color:transparent}
.ccard h3{font-family:var(--disp);font-weight:600;text-transform:uppercase;font-size:25px;line-height:1.1}
.ccard p{margin-top:10px;color:var(--muted);font-size:15.5px}
.ccard.dark p{color:rgba(245,241,234,.76)}
.open{display:flex;align-items:center;gap:12px;font-family:var(--disp);font-weight:600;font-size:26px;margin-top:14px;color:var(--oak-hi)}
.open i{width:10px;height:10px;border-radius:50%;background:#7CE38B;box-shadow:0 0 0 5px rgba(124,227,139,.2)}
.mapbtns{display:flex;flex-wrap:wrap;gap:10px;margin-top:18px}
.mapbtns a{display:inline-flex;align-items:center;gap:8px;padding:11px 18px;border-radius:999px;font-weight:700;font-size:14.5px;background:var(--forest);color:#fff}
.mapbtns a+a{background:transparent;color:var(--ink);border:1.5px solid rgba(16,18,16,.3)}
.clist a{display:flex;align-items:center;gap:16px;padding:14px 0;border-bottom:1px solid var(--line)}
.clist a:last-child{border-bottom:0;padding-bottom:0}
.clist a:first-child{padding-top:0}
.clist .ic{flex:none;width:44px;height:44px;border-radius:50%;display:grid;place-items:center;background:var(--forest);color:var(--oak-hi);font-weight:700;font-size:14px}
.clist small{display:block;font-size:11.5px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:var(--oak-dark)}
.clist b{display:block;font-size:16.5px;font-weight:700;overflow-wrap:anywhere}

.socials{display:flex;flex-wrap:wrap;gap:8px;margin-top:18px}
.socials a{display:inline-flex;align-items:center;gap:8px;padding:9px 14px 9px 9px;border-radius:999px;background:rgba(16,18,16,.06);border:1px solid rgba(16,18,16,.14);font-size:13.5px;font-weight:700;color:var(--ink)}
.socials a i{width:26px;height:26px;border-radius:50%;display:grid;place-items:center;font-style:normal;font-size:10.5px;letter-spacing:.04em;background:var(--forest);color:var(--oak-hi)}
.ccard.dark .socials a,.menu .socials a{background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.2);color:#fff}

.footer{padding:38px 0 130px;background:#101210;color:rgba(255,255,255,.6);font-size:13.5px}
.footer .container{display:grid;gap:10px}
.footer b{font-family:var(--disp);letter-spacing:.14em;color:#fff;font-size:18px}

.dock{position:fixed;left:12px;right:12px;bottom:calc(12px + env(safe-area-inset-bottom));z-index:40;display:flex;gap:10px;padding:10px;border-radius:999px;background:rgba(16,18,16,.88);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.24);box-shadow:0 18px 40px -16px rgba(0,0,0,.8)}
.dock .btn{min-height:52px;flex:1;padding:0 18px;font-size:15px}
.dock .ico{flex:none;width:52px;min-height:52px;display:grid;place-items:center;color:var(--oak-hi);border:1.5px solid rgba(255,255,255,.38);border-radius:50%;background:transparent;cursor:pointer}
.dock .ico svg{width:20px;height:20px}

.modal{position:fixed;inset:0;z-index:150;display:none;align-items:center;justify-content:center;padding:16px;background:rgba(8,10,9,.72);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);overflow:auto}
.modal.open{display:flex}
.modal .sheet{width:100%;max-width:430px;background:var(--forest);color:#fff;border-radius:24px;padding:24px 20px;border:1px solid rgba(237,197,147,.3);box-shadow:0 40px 90px -30px #000;position:relative;margin:auto}
.modal h2{font-family:var(--disp);font-weight:600;text-transform:uppercase;font-size:26px;line-height:1.1}
.modal .sub{font-size:14px;color:rgba(245,241,234,.7);margin-top:8px}
.modal .x{position:absolute;top:14px;right:14px;width:38px;height:38px;border-radius:50%;border:1.5px solid rgba(255,255,255,.34);background:transparent;color:#fff;font-size:18px;line-height:1;cursor:pointer}
.modal .x:hover{border-color:var(--oak)}
.modal .leadcard{padding:0;box-shadow:none;background:transparent}

.toast{position:fixed;left:50%;bottom:calc(88px + env(safe-area-inset-bottom));z-index:200;display:flex;align-items:center;gap:14px;max-width:calc(100% - 32px);width:max-content;padding:14px 20px;border-radius:20px;background:#16211B;color:#fff;border:1px solid rgba(237,197,147,.5);box-shadow:0 24px 60px -20px #000;opacity:0;pointer-events:none;transform:translate(-50%,18px);transition:opacity .35s,transform .35s}
.toast.show{opacity:1;transform:translate(-50%,0)}
.toast .ti{flex:none;width:40px;height:40px;border-radius:50%;display:grid;place-items:center;background:linear-gradient(135deg,var(--oak-hi),var(--oak));color:#1B1209;font-weight:700}
.toast b{display:block;font-size:15.5px}
.toast .tp{display:block;font-size:13.5px;line-height:1.4;color:rgba(255,255,255,.72);margin-top:2px;max-width:32ch}
.toast.bad{border-color:rgba(226,113,90,.6)}
.toast.bad .ti{background:linear-gradient(135deg,#F0A593,#E2715A)}

.noimg{display:grid;place-items:center;width:100%;height:100%;min-height:190px;background:repeating-linear-gradient(45deg,#26302A,#26302A 12px,#1F2723 12px,#1F2723 24px);color:rgba(237,197,147,.72);font-size:11px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;line-height:1.6;text-align:center}

@media (min-width:640px){
 .hero-btns{grid-template-columns:auto auto;justify-content:start}
 .leadcard{padding:30px}
 .modal{padding:24px}
}
@media (min-width:960px){
 :root{--pad:40px}
 .section{padding:120px 0}
 .nav{display:flex}
 .burger{display:none}
 .brand{flex:0 0 auto;margin-right:auto}
 .header .tel{display:inline-flex;margin-left:26px;min-height:46px;padding:0 22px;font-size:14.5px}
 .dock{display:none}
 .hero{padding:0 0 120px;min-height:100vh;display:flex;align-items:center}
 .hero .wrap{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(0,.95fr);gap:56px;align-items:center;padding:130px 40px 0;width:100%;max-width:1200px;margin:0 auto}
 .hero-photo{order:2;padding-top:0;border-radius:var(--r);border:1px solid rgba(255,255,255,.22);box-shadow:0 40px 80px -30px #000;height:min(72vh,640px)}
 .hero-photo img{height:100%;max-height:none;min-height:0;object-fit:cover}
 .hero-photo .noimg{height:100%}
 .hero-photo::after{display:none}
 .hero-content{order:1;margin-top:0;padding:0}
 .hero h1{font-size:clamp(52px,5.4vw,84px)}
 .h2{font-size:clamp(52px,6vw,84px)}
 .about-grid{display:grid;grid-template-columns:1fr 1fr;gap:64px;align-items:start}
 .rows{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));column-gap:56px}
 .kgrid{grid-template-columns:repeat(4,minmax(0,1fr))}
 .kgrid .wide{grid-column:span 2;grid-row:span 2;aspect-ratio:auto}
 .gal{padding-left:max(40px,calc((100vw - 1200px)/2 + 40px))}
 .slide{flex:0 0 400px}
 .steps{grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
 .step{flex-direction:column}
 .revs{padding-left:max(40px,calc((100vw - 1200px)/2 + 40px))}
 .rev{flex-basis:360px}
 .cards{grid-template-columns:repeat(3,minmax(0,1fr));gap:20px}
 .lead-grid{grid-template-columns:1fr 1.05fr;gap:56px;align-items:start;margin-top:44px}
 .toast{bottom:32px}
}
</style>
</head>
<body>

<header class="header" id="site-header">
  <div class="container">
    <a class="brand" href="#top">
      <span class="mark">MK</span>
      <span>
        <b>MEBELS.KRD</b>
        <small>кухни на заказ · Краснодар</small>
      </span>
    </a>
    <nav class="nav">
      <?php foreach ($navItems as $anchor => $label): ?>
      <a href="#<?= e($anchor) ?>"><?= e((string)$label) ?></a>
      <?php endforeach; ?>
    </nav>
    <a class="btn btn-oak tel" href="<?= e($tel) ?>"><?= e($ct['phone_text']) ?></a>
    <a class="burger" href="#menu" aria-label="Открыть меню"><i></i></a>
  </div>
</header>

<div class="menu" id="menu">
  <div class="container">
    <div class="top">
      <a class="brand" href="#top"><span class="mark">MK</span><span><b>MEBELS.KRD</b><small>кухни на заказ</small></span></a>
      <a class="x" href="#top" aria-label="Закрыть меню">✕</a>
    </div>
    <ol>
      <?php $i = 0; foreach ($navItems as $anchor => $label): $i++; ?>
      <li><a href="#<?= e($anchor) ?>"><span><?= sprintf('%02d', $i) ?></span><?= e((string)$label) ?></a></li>
      <?php endforeach; ?>
    </ol>
    <div class="btns">
      <a class="btn btn-oak" href="<?= e($tel) ?>">Позвонить <?= e($ct['phone_text']) ?></a>
      <a class="btn btn-glass" href="<?= e($wa) ?>" target="_blank" rel="noopener">Написать в WhatsApp</a>
    </div>
    <?= socials_html($socials) ?>
    <p><?= e($ct['address']) ?><br><?= e($ct['hours']) ?><?= $manager !== '' ? '<br>Менеджер — ' . e($manager) : '' ?></p>
  </div>
</div>

<section class="hero" id="top">
  <div class="wrap">
    <div class="hero-photo"><?= photo_box($heroPhoto, 'Кухня на заказ в Краснодаре', false) ?></div>
    <div class="hero-content">
      <p class="kick"><?= e($hero['kicker']) ?></p>
      <h1><?= e($hero['title']) ?><em><?= e($hero['title_accent']) ?></em></h1>
      <p class="sub"><?= e($hero['sub']) ?></p>
      <div class="hero-btns">
        <button class="btn btn-oak" type="button" data-open-lead>Рассчитать стоимость</button>
        <a class="btn btn-glass" href="<?= e($wa) ?>" target="_blank" rel="noopener">WhatsApp</a>
      </div>
      <div class="hfacts"><?= facts_html($hero['facts']) ?></div>
    </div>
  </div>
</section>

<section class="section bg-paper" id="about">
  <div class="container">
    <div class="about-grid">
      <div class="reveal">
        <p class="eyebrow"><?= e($about['eyebrow']) ?></p>
        <h2 class="h2"><?= e($about['title']) ?><em><?= e($about['title_accent']) ?></em></h2>
      </div>
      <div class="reveal">
        <div class="about-facts"><?= facts_html($about['facts']) ?></div>
        <?php foreach ($about['body'] as $paragraph): ?>
        <p class="about-p"><?= e((string)$paragraph) ?></p>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<section class="section bg-dark on-dark" id="works">
  <div class="container">
    <div class="reveal">
      <p class="eyebrow"><?= e($gal['eyebrow']) ?></p>
      <h2 class="h2"><?= e($gal['title']) ?><em><?= e($gal['title_accent']) ?></em></h2>
      <p class="lead"><?= e($gal['lead']) ?></p>
    </div>
  </div>
  <div class="gal">
    <?php foreach ($freeFiles as $index => $file): ?>
    <figure class="slide">
      <?= photo_box($file, 'Наши работы, фото ' . ($index + 1)) ?>
      <span class="num"><?= sprintf('%02d', $index + 1) ?></span>
    </figure>
    <?php endforeach; ?>
  </div>
  <div class="container"><p class="hint"><?= e($gal['hint']) ?></p></div>
</section>

<section class="section bg-stone" id="services">
  <div class="container">
    <div class="reveal">
      <p class="eyebrow"><?= e($works['eyebrow']) ?></p>
      <h2 class="h2"><?= e($works['title']) ?><em><?= e($works['title_accent']) ?></em></h2>
      <p class="lead"><?= e($works['lead']) ?></p>
    </div>
    <div class="rows">
      <?php foreach ($works['items'] as $index => $item): ?>
      <div class="row">
        <div class="n"><?= sprintf('%02d', $index + 1) ?></div>
        <div>
          <h3><?= e((string)($item['title'] ?? '')) ?></h3>
          <p><?= e((string)($item['desc'] ?? '')) ?></p>
          <?php if (($item['price'] ?? '') !== ''): ?>
          <span class="tag"><?= e((string)$item['price']) ?></span>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section bg-dark on-dark" id="kitchens">
  <div class="container">
    <div class="reveal">
      <p class="eyebrow"><?= e($kit['eyebrow']) ?></p>
      <h2 class="h2"><?= e($kit['title']) ?><em><?= e($kit['title_accent']) ?></em></h2>
      <p class="lead"><?= e($kit['lead']) ?></p>
    </div>
    <div class="kgrid">
      <?php foreach ($kit['items'] as $index => $item):
          $photo = $kitchenPhotos[$index] ?? '';
          $classes = $index === 0 ? ' class="wide"' : '';
      ?>
      <figure<?= $classes ?>>
        <?= photo_box($photo, 'Кухня: ' . (string)($item['label'] ?? '')) ?>
        <div class="kprice">
          <small><?= e((string)($item['label'] ?? '')) ?></small>
          <b><?= e((string)($item['price'] ?? '')) ?></b>
          <span><?= e((string)($item['note'] ?? '')) ?></span>
        </div>
      </figure>
      <?php endforeach; ?>
    </div>
    <button class="btn btn-oak kbtn" type="button" data-open-lead><?= e($kit['button']) ?></button>
  </div>
</section>

<section class="section bg-paper" id="reviews">
  <div class="container">
    <div class="reveal">
      <p class="eyebrow"><?= e($revs['eyebrow']) ?></p>
      <h2 class="h2"><?= e($revs['title']) ?></h2>
    </div>
    <div class="score reveal">
      <b><?= e($revs['score_value']) ?></b>
      <span><?= e($revs['score_text']) ?></span>
    </div>
  </div>
  <div class="revs">
    <?php foreach ($revs['items'] as $item): ?>
    <figure class="rev">
      <div class="stars">★★★★★</div>
      <p><?= e((string)($item['text'] ?? '')) ?></p>
      <footer>
        <b><?= e((string)($item['name'] ?? '')) ?></b>
        <?= e((string)($item['source'] ?? '')) ?>
      </footer>
    </figure>
    <?php endforeach; ?>
  </div>
</section>

<section class="section bg-dark on-dark" id="steps">
  <div class="container">
    <div class="reveal">
      <p class="eyebrow"><?= e($steps['eyebrow']) ?></p>
      <h2 class="h2"><?= e($steps['title']) ?><em><?= e($steps['title_accent']) ?></em></h2>
    </div>
    <div class="steps">
      <?php foreach ($steps['items'] as $index => $item): ?>
      <div class="step">
        <div class="n"><?= $index + 1 ?></div>
        <div>
          <h3><?= e((string)($item['title'] ?? '')) ?></h3>
          <p><?= e((string)($item['desc'] ?? '')) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section bg-paper" id="lead">
  <div class="container">
    <div class="reveal">
      <p class="eyebrow"><?= e($form['eyebrow']) ?></p>
      <h2 class="h2"><?= e($form['title']) ?><em><?= e($form['title_accent']) ?></em></h2>
      <p class="lead"><?= e($form['lead']) ?></p>
    </div>
    <div class="lead-grid">
      <div class="reveal">
        <div class="clist">
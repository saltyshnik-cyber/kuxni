<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

start_session();

/* ---------- Помощники вывода ---------- */

function socials_html(array $socials): string
{
    if ($socials === []) {
        return '';
    }

    $html = '<div class="socials">';
    foreach ($socials as $label => $url) {
        $html .= '<a href="' . e((string)$url) . '" target="_blank" rel="noopener">' . e((string)$label) . '</a>';
    }

    return $html . '</div>';
}

function lead_form(string $id, array $form, string $csrf): string
{
    $services = array_values(array_filter(array_map(
        static fn(mixed $item): string => trim((string)$item),
        (array)($form['services'] ?? [])
    )));
    $submit = (string)($form['submit'] ?? 'Отправить заявку');

    ob_start(); ?>
<form id="<?= e($id) ?>" class="lf" method="post" action="api/submit.php" data-lead-form novalidate>
  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
  <div class="lf-trap" aria-hidden="true">
    <label>Компания<input type="text" name="company" tabindex="-1" autocomplete="off"></label>
  </div>
  <label class="lf-field" data-field="name">
    <span>Имя</span>
    <input type="text" name="name" autocomplete="name" placeholder="Как к вам обращаться" required>
    <i class="lf-err"></i>
  </label>
  <label class="lf-field" data-field="phone">
    <span>Телефон</span>
    <input type="tel" name="phone" inputmode="tel" autocomplete="tel" placeholder="+7 (___) ___-__-__" required>
    <i class="lf-err"></i>
  </label>
  <label class="lf-field lf-wide" data-field="service">
    <span>Что нужно</span>
    <select name="service">
      <?php foreach ($services as $service): ?>
      <option value="<?= e($service) ?>"><?= e($service) ?></option>
      <?php endforeach; ?>
    </select>
    <i class="lf-err"></i>
  </label>
  <label class="lf-field lf-wide" data-field="comment">
    <span>Комментарий <em>необязательно</em></span>
    <textarea name="comment" rows="3" placeholder="Размеры кухни, материалы, сроки"></textarea>
    <i class="lf-err"></i>
  </label>
  <button class="btn btn-oak lf-submit" type="submit"><?= e($submit) ?></button>
</form>
    <?php return (string)ob_get_clean();
}

/* ---------- Данные страницы ---------- */

$c       = content();
$ct      = (array)($c['contacts'] ?? []);
$hero    = (array)($c['hero'] ?? []);
$about   = (array)($c['about'] ?? []);
$gallery = (array)($c['gallery'] ?? []);
$works   = (array)($c['works'] ?? []);
$kitchens = (array)($c['kitchens'] ?? []);
$reviews = (array)($c['reviews'] ?? []);
$steps   = (array)($c['steps'] ?? []);
$form    = (array)($c['form'] ?? []);
$cs      = (array)($c['contacts_section'] ?? []);
$photos  = page_photos($c);

$manager = trim((string)($ct['manager'] ?? ''));
$phone   = (string)($ct['phone_text'] ?? '');
$rawPhone = (string)($ct['phone_raw'] ?? '');
$tel     = 'tel:' . (preg_replace('/[^\d+]/', '', $rawPhone) ?? '');
$mailto  = 'mailto:' . (string)($ct['email'] ?? '');
$wa      = (string)($ct['whatsapp'] ?? '');
$csrf    = csrf_token();

$socials = array_filter([
    'WhatsApp'  => $wa,
    'Telegram'  => (string)($ct['telegram'] ?? ''),
    'Instagram' => (string)($ct['instagram'] ?? ''),
    'MAX'       => (string)($ct['max'] ?? ''),
    'Pinterest' => (string)($ct['pinterest'] ?? ''),
]);

$gallerySlots = ['g1', 'g2', 'g3', 'g4', 'g5'];
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e((string)($c['site']['title'] ?? SITE_NAME)) ?></title>
<meta name="description" content="<?= e((string)($c['site']['description'] ?? '')) ?>">
<meta property="og:title" content="<?= e((string)($c['site']['title'] ?? SITE_NAME)) ?>">
<meta property="og:description" content="<?= e((string)($c['site']['description'] ?? '')) ?>">
<meta property="og:type" content="website">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Golos+Text:wght@400;500;600;700&family=Prata&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#0F1210;--stone:#F3EFE8;--stone2:#E8E2D8;--ink:#141A16;--ink2:#3C4740;
  --muted:#6C776F;--line:#DCD4C8;--oak:#CF9A62;--oak-hi:#EDC593;--oak-dark:#8A5E2E;
  --r:18px;--r-lg:26px;--pad:clamp(18px,4vw,40px);
  --shadow:0 18px 50px rgba(20,26,22,.14);
}
*{box-sizing:border-box;margin:0;padding:0}
html, body { overflow-x: hidden; max-width: 100%; }
html{scroll-behavior:smooth;scroll-padding-top:84px;-webkit-text-size-adjust:100%}
body{background:var(--stone);color:var(--ink);font:16px/1.6 'Golos Text',system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;padding-bottom:78px}
img{max-width:100%;display:block}
a{color:inherit;text-decoration:none}
button{font:inherit;color:inherit;cursor:pointer;background:none;border:0}
h1,h2,h3{line-height:1.1;font-weight:600;letter-spacing:-.02em}
.container{width:100%;max-width:1160px;margin:0 auto;padding:0 var(--pad)}
.section{padding:clamp(54px,7vw,96px) 0}
.bg-stone{background:var(--stone2)}
.stack{position:relative}
.eyebrow{font-size:12px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:var(--oak-dark);margin-bottom:14px}
.h2{font-family:'Prata',Georgia,serif;font-weight:400;font-size:clamp(28px,4.4vw,46px);letter-spacing:-.01em;max-width:20ch}
.h2 em{display:block;font-style:normal;color:var(--oak-dark)}
.lead{color:var(--ink2);max-width:60ch;margin-top:16px;font-size:17px}

/* Кнопки */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:50px;padding:0 26px;border-radius:999px;font-weight:700;font-size:15px;transition:transform .18s ease,box-shadow .18s ease,background .18s ease}
.btn-oak{background:linear-gradient(135deg,var(--oak-hi),var(--oak));color:#1B1209;box-shadow:0 10px 24px rgba(138,94,46,.28)}
.btn-oak:hover{transform:translateY(-1px);box-shadow:0 14px 30px rgba(138,94,46,.34)}
.btn-o{background:transparent;border:1px solid var(--ink);color:var(--ink)}
.btn-o:hover{background:var(--ink);color:var(--stone)}

/* Шапка */
.header{position:sticky;top:0;z-index:50;background:rgba(243,239,232,.82);backdrop-filter:blur(12px);border-bottom:1px solid transparent;transition:border-color .2s ease,background .2s ease}
.header.scrolled{border-color:var(--line);background:rgba(243,239,232,.95)}
.header .container{display:flex;align-items:center;gap:22px;min-height:66px}
.logo{font-weight:700;letter-spacing:.1em;font-size:15px}
.logo small{display:block;font-size:9.5px;letter-spacing:.22em;color:var(--muted);font-weight:600}
.nav{display:none;gap:22px;margin-left:auto;font-size:14.5px;font-weight:500;color:var(--ink2)}
.nav a:hover{color:var(--oak-dark)}
.header .cta{display:none;margin-left:auto}
.header .call{display:inline-flex;align-items:center;margin-left:auto;font-weight:700;font-size:14.5px}
@media (min-width:900px){
  .nav{display:flex}
  .header .cta{display:inline-flex;margin-left:0}
  .header .call{display:none}
}

/* Первый экран */
.hero{padding:clamp(28px,5vw,60px) 0 clamp(34px,5vw,70px)}
.hero .grid{display:grid;gap:clamp(24px,4vw,46px);align-items:center}
@media (min-width:900px){.hero .grid{grid-template-columns:1.05fr .95fr}}
.kicker{display:inline-flex;align-items:center;gap:9px;font-size:12.5px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--oak-dark);background:rgba(207,154,98,.16);padding:8px 14px;border-radius:999px;margin-bottom:20px}
.kicker i{width:7px;height:7px;border-radius:50%;background:var(--oak);display:block}
.hero h1{font-family:'Prata',Georgia,serif;font-weight:400;font-size:clamp(40px,7.6vw,80px);line-height:.98;letter-spacing:-.025em}
.hero h1 em{font-style:normal;color:var(--oak-dark)}
.hero .sub{color:var(--ink2);font-size:clamp(16px,2vw,18.5px);max-width:44ch;margin-top:18px}
.hero .actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:26px}
.facts{display:grid;gap:10px;grid-template-columns:repeat(3,minmax(0,1fr));margin-top:30px;padding-top:24px;border-top:1px solid var(--line)}
.facts div b{display:block;font-size:clamp(17px,2.4vw,24px);font-weight:700;letter-spacing:-.02em}
.facts div span{font-size:12.5px;color:var(--muted);line-height:1.35;display:block;margin-top:4px}
.hero-photo{position:relative;border-radius:var(--r-lg);overflow:hidden;background:linear-gradient(140deg,var(--stone2),#D6CCBC);box-shadow:var(--shadow);aspect-ratio:4/3.2}
.hero-photo img{width:100%;height:100%;object-fit:cover}
.hero-photo .tag{position:absolute;left:16px;bottom:16px;background:rgba(15,18,16,.78);color:#F3EFE8;backdrop-filter:blur(8px);padding:10px 16px;border-radius:999px;font-size:13px;font-weight:600}

/* О нас */
.about .grid{display:grid;gap:clamp(22px,4vw,48px)}
@media (min-width:900px){.about .grid{grid-template-columns:1fr .85fr;align-items:start}}
.about p{color:var(--ink2);font-size:17px;margin-top:16px;max-width:62ch}
.about-facts{display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(140px,1fr))}
.about-facts div{background:var(--stone);border:1px solid var(--line);border-radius:var(--r);padding:18px}
.about-facts b{display:block;font-size:clamp(20px,2.6vw,26px);letter-spacing:-.02em}
.about-facts span{font-size:13px;color:var(--muted);display:block;margin-top:4px}

/* Галерея */
.rail{display:flex;gap:14px;overflow-x:auto;scroll-snap-type:x mandatory;padding:26px var(--pad) 8px;margin:0 calc(var(--pad) * -1)}
.rail::-webkit-scrollbar{height:6px}
.rail::-webkit-scrollbar-thumb{background:var(--line);border-radius:99px}
.rail figure{flex:0 0 min(84vw,420px);scroll-snap-align:start;border-radius:var(--r-lg);overflow:hidden;background:linear-gradient(140deg,var(--stone2),#D6CCBC);aspect-ratio:4/3}
.rail img{width:100%;height:100%;object-fit:cover}
.hint{font-size:13px;color:var(--muted);margin-top:14px}

/* Услуги и цены */
.cards-grid{display:grid;gap:14px;margin-top:32px}
@media (min-width:680px){.cards-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (min-width:1000px){.cards-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
.card{background:#fff;border:1px solid var(--line);border-radius:var(--r);padding:22px;display:flex;flex-direction:column;gap:10px}
.card h3{font-size:18px;font-weight:700;letter-spacing:-.01em}
.card p{color:var(--ink2);font-size:14.5px;line-height:1.55}
.card .price{margin-top:auto;padding-top:12px;font-weight:700;color:var(--oak-dark);font-size:15px}
.price-list{display:grid;gap:14px;margin-top:32px}
@media (min-width:760px){.price-list{grid-template-columns:repeat(2,minmax(0,1fr))}}
.prow{display:flex;flex-wrap:wrap;gap:10px 18px;align-items:baseline;justify-content:space-between;border-bottom:1px solid var(--line);padding-bottom:14px}
.prow b{font-size:18px;font-weight:700}
.prow span{font-size:13.5px;color:var(--muted);flex:0 0 100%}
.prow .p{font-weight:700;color:var(--oak-dark);font-size:16px}
.kitchen-photos{display:grid;gap:12px;margin-top:30px;grid-template-columns:repeat(auto-fit,minmax(180px,1fr))}
.kitchen-photos figure{border-radius:var(--r);overflow:hidden;aspect-ratio:1/1;background:linear-gradient(140deg,var(--stone2),#D6CCBC)}
.kitchen-photos figure:first-child{grid-column:span 2;aspect-ratio:2/1}
@media (max-width:560px){.kitchen-photos figure:first-child{grid-column:span 1;aspect-ratio:4/3}}
.kitchen-photos img{width:100%;height:100%;object-fit:cover}

/* Отзывы */
.score{display:flex;flex-wrap:wrap;align-items:center;gap:14px;margin-top:22px}
.score b{font-family:'Prata',Georgia,serif;font-size:clamp(34px,5vw,52px);font-weight:400;line-height:1;color:var(--oak-dark)}
.score span{font-size:13.5px;color:var(--muted);max-width:34ch}
.reviews{display:grid;gap:14px;margin-top:28px}
@media (min-width:820px){.reviews{grid-template-columns:repeat(3,minmax(0,1fr))}}
.review{background:#fff;border:1px solid var(--line);border-radius:var(--r);padding:22px;display:flex;flex-direction:column;gap:14px}
.review p{color:var(--ink2);font-size:14.5px;line-height:1.6}
.review .who{margin-top:auto;font-size:13px;color:var(--muted)}
.review .who b{display:block;color:var(--ink);font-size:14.5px}

/* Шаги */
.steps{display:grid;gap:12px;margin-top:30px;counter-reset:step}
@media (min-width:760px){.steps{grid-template-columns:repeat(auto-fit,minmax(190px,1fr))}}
.step{background:#fff;border:1px solid var(--line);border-radius:var(--r);padding:20px;position:relative}
.step::before{counter-increment:step;content:counter(step,decimal-leading-zero);display:block;font-size:12px;font-weight:700;letter-spacing:.14em;color:var(--oak);margin-bottom:10px}
.step b{display:block;font-size:16.5px;margin-bottom:6px}
.step p{font-size:14px;color:var(--ink2);line-height:1.5}

/* Заявка */
.lead-grid{display:grid;gap:clamp(20px,3.5vw,40px);margin-top:34px}
@media (min-width:920px){.lead-grid{grid-template-columns:.95fr 1.05fr;align-items:start}}
.clist{display:grid;gap:10px}
.clist a,.clist .row{display:flex;align-items:center;gap:14px;background:#fff;border:1px solid var(--line);border-radius:var(--r);padding:14px 16px;transition:border-color .18s ease,transform .18s ease}
.clist a:hover{border-color:var(--oak);transform:translateY(-1px)}
.clist .ic{width:40px;height:40px;flex:0 0 40px;display:grid;place-items:center;border-radius:12px;background:rgba(207,154,98,.18);color:var(--oak-dark);font-weight:700;font-size:13px}
.clist small{display:block;font-size:12px;color:var(--muted);letter-spacing:.02em}
.clist b{display:block;font-size:15.5px;font-weight:700;word-break:break-word}
.socials{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px}
.socials a{font-size:13px;font-weight:600;padding:8px 14px;border-radius:999px;border:1px solid var(--line);color:var(--ink2)}
.socials a:hover{border-color:var(--oak);color:var(--oak-dark)}
.manager-note{font-size:14px;color:var(--muted);margin-top:16px}
.leadcard{background:#fff;border:1px solid var(--line);border-radius:var(--r-lg);padding:clamp(20px,3vw,30px);box-shadow:var(--shadow)}
.leadcard h3{font-size:20px;margin-bottom:18px}

/* Форма */
.lf{display:grid;gap:12px}
@media (min-width:560px){.lf{grid-template-columns:repeat(2,minmax(0,1fr))}.lf .lf-wide,.lf .lf-submit{grid-column:1/-1}}
.lf-trap{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden}
.lf-field{display:block}
.lf-field>span{display:block;font-size:12px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:6px}
.lf-field>span em{font-style:normal;font-weight:500;letter-spacing:0;text-transform:none;color:var(--muted);font-size:11.5px}
.lf-field input,.lf-field select,.lf-field textarea{width:100%;padding:13px 15px;border-radius:12px;border:1px solid var(--line);background:var(--stone);font:inherit;font-size:15px;color:var(--ink);transition:border-color .18s ease,background .18s ease}
.lf-field textarea{resize:vertical;min-height:86px}
.lf-field input:focus,.lf-field select:focus,.lf-field textarea:focus{outline:none;border-color:var(--oak);background:#fff}
.lf-field.invalid input,.lf-field.invalid select,.lf-field.invalid textarea{border-color:#C9583F;background:#FCF1EE}
.lf-err{display:block;font-size:12.5px;color:#C9583F;margin-top:5px;min-height:0}
.lf-err:empty{display:none}
.lf-submit{width:100%;margin-top:4px}
.lf-note{font-size:12px;color:var(--muted);line-height:1.5;margin-top:14px}

/* Контакты */
.cards{display:grid;gap:14px;margin-top:30px}
@media (min-width:860px){.cards{grid-template-columns:repeat(3,minmax(0,1fr))}}
.ccard{background:#fff;border:1px solid var(--line);border-radius:var(--r);padding:22px;display:flex;flex-direction:column;gap:10px}
.ccard.dark{background:var(--ink);color:var(--stone);border-color:var(--ink)}
.ccard.dark .socials a{border-color:rgba(243,239,232,.28);color:var(--stone)}
.ccard h3{font-size:17px}
.ccard p{font-size:14.5px;color:var(--muted);line-height:1.55}
.ccard.dark p{color:rgba(243,239,232,.72)}
.open{display:inline-flex;align-items:center;gap:8px;font-size:14px;font-weight:600}
.open i{width:8px;height:8px;border-radius:50%;background:#7CE38B;box-shadow:0 0 0 4px rgba(124,227,139,.2)}
.mapbtns{display:flex;flex-wrap:wrap;gap:8px;margin-top:auto;padding-top:8px}
.mapbtns a{font-size:13.5px;font-weight:600;padding:10px 16px;border-radius:999px;border:1px solid var(--line)}
.mapbtns a:hover{border-color:var(--oak);color:var(--oak-dark)}

/* Футер */
.footer{background:var(--ink);color:rgba(243,239,232,.7);padding:34px 0;font-size:13.5px}
.footer .container{display:flex;flex-wrap:wrap;gap:10px 24px;align-items:center}
.footer b{color:var(--stone);letter-spacing:.1em;font-size:14px}

/* Нижняя панель */
.dock{position:fixed;left:0;right:0;bottom:0;z-index:60;display:flex;align-items:center;gap:10px;padding:10px calc(12px + env(safe-area-inset-right)) calc(10px + env(safe-area-inset-bottom)) calc(12px + env(safe-area-inset-left));background:rgba(243,239,232,.95);border-top:1px solid var(--line);backdrop-filter:blur(12px)}
.dock .btn{flex:1;min-height:46px;padding:0 18px}
.dock .ico{width:46px;height:46px;flex:0 0 46px;display:grid;place-items:center;border-radius:14px;border:1px solid var(--line);background:#fff;color:var(--ink)}
.dock .ico svg{width:20px;height:20px}
@media (min-width:900px){.dock .btn{flex:0 0 auto;min-width:240px}.dock{justify-content:center}body{padding-bottom:78px}}

/* Модалка */
.modal{position:fixed;inset:0;z-index:100;display:none;align-items:flex-end;justify-content:center;background:rgba(15,18,16,.55);backdrop-filter:blur(4px);padding:0}
.modal.open{display:flex}
@media (min-width:640px){.modal{align-items:center;padding:24px}}
.sheet{position:relative;width:100%;max-width:520px;max-height:92vh;overflow-y:auto;background:var(--stone);border-radius:24px 24px 0 0;padding:clamp(22px,4vw,32px)}
@media (min-width:640px){.sheet{border-radius:var(--r-lg)}}
.sheet h2{font-family:'Prata',Georgia,serif;font-weight:400;font-size:24px;margin-bottom:8px}
.sheet .sub{font-size:14px;color:var(--muted);margin-bottom:18px}
.sheet .x{position:absolute;top:14px;right:14px;width:38px;height:38px;border-radius:50%;border:1px solid var(--line);background:#fff;display:grid;place-items:center;font-size:15px}

/* Тост */
.toast{position:fixed;left:50%;bottom:calc(84px + env(safe-area-inset-bottom));transform:translate(-50%,20px);z-index:120;display:flex;gap:12px;align-items:flex-start;max-width:min(92vw,420px);padding:14px 18px;border-radius:16px;background:var(--ink);color:var(--stone);box-shadow:0 18px 50px rgba(15,18,16,.32);opacity:0;pointer-events:none;transition:opacity .22s ease,transform .22s ease}
.toast.show{opacity:1;transform:translate(-50%,0)}
.toast .ti{width:26px;height:26px;flex:0 0 26px;display:grid;place-items:center;border-radius:50%;background:#7CE38B;color:#0F1210;font-weight:700;font-size:14px}
.toast.bad .ti{background:#E2715A}
.toast b{display:block;font-size:14.5px}
.toast .tp{display:block;font-size:13px;color:rgba(243,239,232,.72);line-height:1.45}

.reveal{animation:riseIn .5s ease both}
@keyframes riseIn{from{opacity:.001;transform:translateY(12px)}to{opacity:1;transform:none}}
@media (prefers-reduced-motion:reduce){.reveal{animation:none}}
</style>
</head>
<body>

<header class="header" id="site-header">
  <div class="container">
    <a class="logo" href="#top">MEBELS.KRD<small>кухни на заказ</small></a>
    <nav class="nav">
      <a href="#works">Услуги</a>
      <a href="#gallery">Работы</a>
      <a href="#kitchens">Цены</a>
      <a href="#steps">Как работаем</a>
      <a href="#contacts">Контакты</a>
    </nav>
    <a class="call" href="<?= e($tel) ?>"><?= e($phone) ?></a>
    <button class="btn btn-oak cta" type="button" data-open-lead>Заявка на просчёт</button>
  </div>
</header>

<section class="hero" id="top">
  <div class="container">
    <div class="grid">
      <div class="reveal">
        <p class="kicker"><i></i><?= e((string)($hero['kicker'] ?? '')) ?></p>
        <h1><?= e((string)($hero['title'] ?? '')) ?> <em><?= e((string)($hero['title_accent'] ?? '')) ?></em></h1>
        <p class="sub"><?= e((string)($hero['sub'] ?? '')) ?></p>
        <div class="actions">
          <button class="btn btn-oak" type="button" data-open-lead>Бесплатный замер</button>
          <a class="btn btn-o" href="#gallery">Смотреть работы</a>
        </div>
        <div class="facts">
          <?php foreach ((array)($hero['facts'] ?? []) as $fact): ?>
          <div>
            <b><?= e((string)($fact['value'] ?? '')) ?></b>
            <span><?= e((string)($fact['label'] ?? '')) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="hero-photo reveal">
        <?php if (($photos['hero'] ?? '') !== ''): ?>
        <img src="<?= e($photos['hero']) ?>" alt="Кухня на заказ" fetchpriority="high" decoding="async">
        <?php endif; ?>
        <span class="tag">Шоурум · <?= e((string)($ct['hours'] ?? '')) ?></span>
      </div>
    </div>
  </div>
</section>

<section class="section about stack reveal" id="about">
  <div class="container">
    <p class="eyebrow"><?= e((string)($about['eyebrow'] ?? '')) ?></p>
    <h2 class="h2"><?= e((string)($about['title'] ?? '')) ?><em><?= e((string)($about['title_accent'] ?? '')) ?></em></h2>
    <div class="grid" style="margin-top:22px">
      <div>
        <?php foreach ((array)($about['body'] ?? []) as $paragraph): ?>
        <p><?= e((string)$paragraph) ?></p>
        <?php endforeach; ?>
      </div>
      <div class="about-facts">
        <?php foreach ((array)($about['facts'] ?? []) as $fact): ?>
        <div>
          <b><?= e((string)($fact['value'] ?? '')) ?></b>
          <span><?= e((string)($fact['label'] ?? '')) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<section class="section bg-stone stack reveal" id="gallery">
  <div class="container">
    <p class="eyebrow"><?= e((string)($gallery['eyebrow'] ?? '')) ?></p>
    <h2 class="h2"><?= e((string)($gallery['title'] ?? '')) ?><em><?= e((string)($gallery['title_accent'] ?? '')) ?></em></h2>
    <p class="lead"><?= e((string)($gallery['lead'] ?? '')) ?></p>
  </div>
  <div class="container" style="padding-right:0">
    <div class="rail">
      <?php foreach ($gallerySlots as $slot): ?>
        <?php if (($photos[$slot] ?? '') !== ''): ?>
        <figure><img src="<?= e($photos[$slot]) ?>" alt="Кухня на заказ в Краснодаре" loading="lazy" decoding="async"></figure>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="container">
    <p class="hint"><?= e((string)($gallery['hint'] ?? '')) ?></p>
  </div>
</section>

<section class="section stack reveal" id="works">
  <div class="container">
    <p class="eyebrow"><?= e((string)($works['eyebrow'] ?? '')) ?></p>
    <h2 class="h2"><?= e((string)($works['title'] ?? '')) ?><em><?= e((string)($works['title_accent'] ?? '')) ?></em></h2>
    <p class="lead"><?= e((string)($works['lead'] ?? '')) ?></p>
    <div class="cards-grid">
      <?php foreach ((array)($works['items'] ?? []) as $item): ?>
      <article class="card">
        <h3><?= e((string)($item['title'] ?? '')) ?></h3>
        <p><?= e((string)($item['desc'] ?? '')) ?></p>
        <span class="price"><?= e((string)($item['price'] ?? '')) ?></span>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section bg-stone stack reveal" id="kitchens">
  <div class="container">
    <p class="eyebrow"><?= e((string)($kitchens['eyebrow'] ?? '')) ?></p>
    <h2 class="h2"><?= e((string)($kitchens['title'] ?? '')) ?><em><?= e((string)($kitchens['title_accent'] ?? '')) ?></em></h2>
    <p class="lead"><?= e((string)($kitchens['lead'] ?? '')) ?></p>
    <div class="price-list">
      <?php foreach ((array)($kitchens['items'] ?? []) as $item): ?>
      <div class="prow">
        <b><?= e((string)($item['label'] ?? '')) ?></b>
        <span class="p"><?= e((string)($item['price'] ?? '')) ?></span>
        <span><?= e((string)($item['note'] ?? '')) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="actions" style="margin-top:26px">
      <button class="btn btn-oak" type="button" data-open-lead><?= e((string)($kitchens['button'] ?? 'Подобрать формат и цену')) ?></button>
    </div>
    <div class="kitchen-photos">
      <?php foreach (['k1', 'k2', 'k3'] as $slot): ?>
        <?php if (($photos[$slot] ?? '') !== ''): ?>
        <figure><img src="<?= e($photos[$slot]) ?>" alt="Кухня <?= e($slot) ?>" loading="lazy" decoding="async"></figure>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section stack reveal" id="reviews">
  <div class="container">
    <p class="eyebrow"><?= e((string)($reviews['eyebrow'] ?? '')) ?></p>
    <h2 class="h2"><?= e((string)($reviews['title'] ?? '')) ?></h2>
    <div class="score">
      <b><?= e((string)($reviews['score_value'] ?? '')) ?></b>
      <span><?= e((string)($reviews['score_text'] ?? '')) ?></span>
    </div>
    <div class="reviews">
      <?php foreach ((array)($reviews['items'] ?? []) as $review): ?>
      <article class="review">
        <p><?= e((string)($review['text'] ?? '')) ?></p>
        <div class="who"><b><?= e((string)($review['name'] ?? '')) ?></b><?= e((string)($review['source'] ?? '')) ?></div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section bg-stone stack reveal" id="steps">
  <div class="container">
    <p class="eyebrow"><?= e((string)($steps['eyebrow'] ?? '')) ?></p>
    <h2 class="h2"><?= e((string)($steps['title'] ?? '')) ?><em><?= e((string)($steps['title_accent'] ?? '')) ?></em></h2>
    <div class="steps">
      <?php foreach ((array)($steps['items'] ?? []) as $step): ?>
      <div class="step">
        <b><?= e((string)($step['title'] ?? '')) ?></b>
        <p><?= e((string)($step['desc'] ?? '')) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section stack reveal" id="lead">
  <div class="container">
    <p class="eyebrow"><?= e((string)($form['eyebrow'] ?? '')) ?></p>
    <h2 class="h2"><?= e((string)($form['title'] ?? '')) ?><em><?= e((string)($form['title_accent'] ?? '')) ?></em></h2>
    <p class="lead"><?= e((string)($form['lead'] ?? '')) ?></p>
    <div class="lead-grid">
      <div>
        <div class="clist">
          <a href="<?= e($tel) ?>"><span class="ic">☎</span><span><small>Позвонить<?= $manager !== '' ? ' · ' . e($manager) : '' ?></small><b><?= e($phone) ?></b></span></a>
          <a href="<?= e($wa) ?>" target="_blank" rel="noopener"><span class="ic">WA</span><span><small>WhatsApp</small><b>Написать в мессенджер</b></span></a>
          <a href="<?= e((string)($ct['telegram'] ?? '')) ?>" target="_blank" rel="noopener"><span class="ic">TG</span><span><small>Telegram</small><b>Задать вопрос</b></span></a>
        </div>
        <p class="manager-note">Менеджер<?= $manager !== '' ? ' ' . e($manager) : '' ?> на связи <?= e((string)($ct['hours'] ?? '')) ?>. Замер и выезд по Краснодару — бесплатно.</p>
        <?= socials_html($socials) ?>
      </div>
      <div class="leadcard">
        <h3><?= e((string)($form['intro'] ?? '')) ?></h3>
        <?= lead_form('lead-form', $form, $csrf) ?>
        <p class="lf-note"><?= e((string)($form['note'] ?? '')) ?></p>
      </div>
    </div>
  </div>
</section>

<section class="section bg-stone stack reveal" id="contacts" style="--z:9">
  <div class="container">
    <p class="eyebrow"><?= e((string)($cs['eyebrow'] ?? '')) ?></p>
    <h2 class="h2"><?= e((string)($cs['title'] ?? '')) ?><em><?= e((string)($cs['title_accent'] ?? '')) ?></em></h2>
    <div class="cards">
      <div class="ccard dark">
        <h3>Адрес и график</h3>
        <p><?= e((string)($ct['address'] ?? '')) ?></p>
        <div class="open"><i></i><?= e((string)($ct['hours'] ?? '')) ?></div>
        <?= socials_html($socials) ?>
      </div>
      <div class="ccard">
        <h3>Связаться<?= $manager !== '' ? ' с ' . e($manager) : '' ?></h3>
        <div class="clist" style="margin-top:16px">
          <a href="<?= e($tel) ?>"><span class="ic">☎</span><span><small>Телефон</small><b><?= e((string)($ct['phone_text'] ?? '')) ?></b></span></a>
          <a href="<?= e($mailto) ?>"><span class="ic">@</span><span><small>Почта</small><b><?= e((string)($ct['email'] ?? '')) ?></b></span></a>
        </div>
        <?= socials_html($socials) ?>
      </div>
      <div class="ccard">
        <h3>Как добраться</h3>
        <p>Шоурум с образцами фасадов и столешниц — приезжайте выбрать материалы.</p>
        <div class="mapbtns">
          <a href="<?= e((string)($ct['maps_yandex'] ?? '')) ?>" target="_blank" rel="noopener">Яндекс Карты</a>
          <a href="<?= e((string)($ct['maps_2gis'] ?? '')) ?>" target="_blank" rel="noopener">2ГИС</a>
        </div>
      </div>
    </div>
  </div>
</section>

<footer class="footer">
  <div class="container">
    <b>MEBELS.KRD</b>
    <span><?= e((string)($ct['address'] ?? '')) ?> · <?= e($phone) ?></span>
    <span>© <?= date('Y') ?> <?= e(SITE_NAME) ?>. Кухни на заказ в Краснодаре.</span>
  </div>
</footer>

<div class="dock" id="dock">
  <button class="btn btn-oak" type="button" data-open-lead>Заявка на просчёт</button>
  <a class="ico" href="<?= e($tel) ?>" aria-label="Позвонить">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/></svg>
  </a>
  <a class="ico" href="<?= e($wa) ?>" target="_blank" rel="noopener" aria-label="WhatsApp">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.5 8.5 0 0 1-12.6 7.4L3 20l1.2-5A8.5 8.5 0 1 1 21 11.5z"/></svg>
  </a>
</div>

<div class="modal" id="lead-modal" role="dialog" aria-modal="true" aria-labelledby="lead-modal-title">
  <div class="sheet">
    <button class="x" type="button" data-close-lead aria-label="Закрыть">✕</button>
    <h2 id="lead-modal-title">Заявка на просчёт</h2>
    <p class="sub"><?= e((string)($form['intro'] ?? '')) ?> — перезвоним в течение рабочего дня.</p>
    <?= lead_form('modal-form', $form, $csrf) ?>
  </div>
</div>

<div class="toast" id="toast" role="status" aria-live="polite">
  <span class="ti" id="toast-icon">✓</span>
  <span><b id="toast-title">Заявка отправлена</b><span class="tp" id="toast-text"></span></span>
</div>

<script>
(() => {
  const header = document.getElementById('site-header');
  const onScroll = () => header.classList.toggle('scrolled', window.scrollY > 40);
  window.addEventListener('scroll', onScroll, {passive: true});
  onScroll();

  const modal = document.getElementById('lead-modal');
  const openModal = () => { modal.classList.add('open'); document.body.style.overflow = 'hidden'; };
  const closeModal = () => { modal.classList.remove('open'); document.body.style.overflow = ''; };
  document.querySelectorAll('[data-open-lead]').forEach(btn => btn.addEventListener('click', openModal));
  document.querySelectorAll('[data-close-lead]').forEach(btn => btn.addEventListener('click', closeModal));
  modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

  const toast = document.getElementById('toast');
  let toastTimer;
  const showToast = (ok, title, text) => {
    toast.classList.toggle('bad', !ok);
    document.getElementById('toast-icon').textContent = ok ? '✓' : '!';
    document.getElementById('toast-title').textContent = title;
    document.getElementById('toast-text').textContent = text;
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 5000);
  };

  const setFieldError = (form, field, message) => {
    const wrap = form.querySelector(`[data-field="${field}"]`);
    if (!wrap) return;
    wrap.classList.toggle('invalid', Boolean(message));
    const err = wrap.querySelector('.lf-err');
    if (err && message) err.textContent = message;
  };

  document.querySelectorAll('form[data-lead-form]').forEach(form => {
    form.addEventListener('submit', async e => {
      e.preventDefault();
      const button = form.querySelector('button[type="submit"]');
      const label = button.textContent;
      button.disabled = true;
      button.textContent = 'Отправляем…';
      form.querySelectorAll('.lf-field').forEach(f => f.classList.remove('invalid'));

      try {
        const response = await fetch(form.action, {
          method: 'POST',
          body: new FormData(form),
          headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'},
          credentials: 'same-origin'
        });
        const data = await response.json();
        if (data.ok) {
          form.reset();
          closeModal();
          showToast(true, 'Заявка отправлена', data.message);
        } else {
          Object.entries(data.errors || {})
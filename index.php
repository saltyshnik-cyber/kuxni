<?php
require __DIR__ . '/config.php';
start_session();

$photos = glob(__DIR__ . '/uploads/*.{jpg,jpeg,jfif,png,webp,JPG,JPEG,JFIF,PNG,WEBP}', GLOB_BRACE) ?: [];
$photoUrls = array_map(static fn($path) => 'uploads/' . rawurlencode(basename($path)), $photos);

$photo = static function (int $index) use ($photoUrls): string {
    return $photoUrls ? $photoUrls[$index % count($photoUrls)] : 'uploads/placeholder.jpg';
};

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$defaults = [
    'phone'     => '+7 (918) 089-22-54',
    'telegram'  => 'https://t.me',
    'instagram' => 'https://instagram.com',
    'max'       => 'https://max.ru',
    'pinterest' => 'https://pin.it',
    'address'   => 'г. Краснодар, ул. Конечная 2 (район Энка), 2 этаж',
];

$contentFile = __DIR__ . '/data/content.json';
$content = is_file($contentFile) ? json_decode((string)file_get_contents($contentFile), true) : [];
$contacts = array_merge($defaults, is_array($content['contacts'] ?? null) ? $content['contacts'] : []);

$phoneDigits = preg_replace('/\D/', '', $contacts['phone']);
$telHref = '+' . $phoneDigits;
$waText = rawurlencode('Здравствуйте! Хочу рассчитать мебель на заказ.');
$waHref = 'https://wa.me/' . $phoneDigits . '?text=' . $waText;
$addressQuery = rawurlencode($contacts['address']);
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#111411">
<title>Mebels.krd — кухни, шкафы и мебель на заказ в Краснодаре</title>
<meta name="description" content="Крупное мебельное производство в Краснодаре. Кухни от 100 тыс. рублей, шкафы, прихожие. Заявка на просчёт.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Golos+Text:wght@400;500;600;700;800&family=Prata&display=swap" rel="stylesheet">
<style>:root{--ink:#101210;--forest:#1A211D;--forest2:#26302A;--paper:#F5F1EA;--stone:#E4DCCF;--line:#D9D0C2;--muted:#5C574E;
--oak:#CF9A62;--oak-hi:#EDC593;--oak-dark:#8A5A2B;
--disp:'Prata',Georgia,serif;--sans:'Golos Text',system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;--pad:24px;--r:28px}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth;-webkit-text-size-adjust:100%}
body{font-family:var(--sans);font-size:16px;line-height:1.55;color:var(--ink);background:var(--ink);overflow-x:hidden}
img{display:block;max-width:100%;height:auto}
a{color:inherit;text-decoration:none}
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
.btn{display:flex;align-items:center;justify-content:center;gap:12px;min-height:60px;padding:0 28px;border-radius:999px;font-weight:700;font-size:16px;white-space:nowrap;transition:transform .2s}
.btn:active{transform:scale(.98)}
.btn svg{flex:none}
.btn-oak{color:#1B1209;background:linear-gradient(135deg,var(--oak-hi),var(--oak));box-shadow:0 14px 30px -16px rgba(207,154,98,.9)}
.btn-glass{color:#fff;background:rgba(255,255,255,.08);border:1.5px solid rgba(255,255,255,.4);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px)}
.btn-dark{color:#fff;background:var(--forest)}
.btn-line{color:var(--ink);border:1.5px solid rgba(16,18,16,.35)}

.sheet{position:relative;border-radius:36px 36px 0 0;margin-top:-36px;box-shadow:0 -26px 50px -30px rgba(0,0,0,.6)}
.bg-dark{background:radial-gradient(ellipse 70% 40% at 90% 0%,rgba(207,154,98,.2),transparent 70%),radial-gradient(ellipse 60% 40% at 0% 100%,rgba(90,120,100,.18),transparent 70%),url("<?= e($photo(0)) ?>"),url("<?= e($photo(1)) ?>"),#111411;color:#fff;overflow:hidden}
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

.menu{position:fixed;inset:0;z-index:100;background:url("<?= e($photo(4)) ?>"),#101210;color:#fff;padding:14px 0 28px;display:none;overflow:auto}
.menu:target,.menu.open{display:block}
.menu .top{display:flex;justify-content:space-between;align-items:center}
.menu .x{width:46px;height:46px;border-radius:50%;display:grid;place-items:center;border:1.5px solid rgba(255,255,255,.4);font-size:22px;line-height:1}
.menu ol{list-style:none;margin:30px 0 24px}
.menu li a{display:flex;align-items:baseline;gap:16px;padding:14px 0;border-bottom:1px solid rgba(255,255,255,.14);font-family:var(--disp);font-weight:600;text-transform:uppercase;font-size:clamp(30px,9vw,44px);line-height:1.1}
.menu li a span{font-family:var(--sans);font-size:13px;font-weight:700;letter-spacing:.2em;color:var(--oak)}
.menu .btns{display:grid;gap:12px;margin-top:24px}
.menu p{font-size:14px;color:rgba(255,255,255,.62);margin-top:18px}

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
.cta .bgimg{position:absolute;inset:0}
.cta .bgimg img{width:100%;height:100%;object-fit:cover}
.cta .bgimg::after{content:"";position:absolute;inset:0;background:linear-gradient(to bottom,rgba(16,18,16,.88),rgba(16,18,16,.74) 50%,rgba(16,18,16,.94))}
.cta .container{position:relative;z-index:1}
.cta .btns{display:grid;gap:12px;margin-top:28px}

.lead-form{display:grid;gap:12px;margin-top:28px;max-width:520px}
.lead-form input,.lead-form textarea{width:100%;font:inherit;font-size:16px;color:var(--ink);background:#fff;border:1.5px solid var(--line);border-radius:18px;padding:14px 18px;outline:none}
.lead-form input:focus,.lead-form textarea:focus{border-color:var(--oak)}
.lead-form textarea{resize:vertical;min-height:88px}
.lead-form .btn{width:100%}
.form-status{min-height:22px;font-size:14.5px;color:var(--oak-hi)}
.form-status.err{color:#B0432B}

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
.clist a,.clist .ph{display:flex;align-items:center;gap:16px;padding:14px 0;border-bottom:1px solid var(--line)}
.clist>*:last-child{border-bottom:0;padding-bottom:0}
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

.modal{position:fixed;inset:0;z-index:200;display:none;align-items:center;justify-content:center;padding:20px;background:rgba(10,12,10,.7);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px)}
.modal.open{display:flex}
.modal-box{position:relative;width:100%;max-width:480px;max-height:100%;overflow:auto;padding:30px 24px 26px;border-radius:var(--r);background:var(--paper);color:var(--ink)}
.modal-box .h2{font-size:clamp(40px,10vw,56px)}
.modal-close{position:absolute;top:14px;right:14px;width:42px;height:42px;border-radius:50%;display:grid;place-items:center;border:1.5px solid rgba(16,18,16,.3);background:transparent;color:var(--ink);font-size:22px;line-height:1;cursor:pointer}
button.btn{border:0;cursor:pointer;font-family:inherit}

@media (min-width:640px){
 .hero-btns,.cta .btns{grid-template-columns:auto auto;justify-content:start}
}
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
 .steps{grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
 .step{flex-direction:column}
 .revs{padding-left:max(40px,calc((100vw - 1200px)/2 + 40px))}
 .rev{flex-basis:360px}
 .cards{grid-template-columns:repeat(3,minmax(0,1fr));gap:20px}
 .footer{padding-bottom:56px}
 .footer .container{grid-template-columns:1fr auto;align-items:center}
}
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
 <div class="btns"><button class="btn btn-oak" type="button" data-open-lead><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20l1.2-4A8 8 0 1 1 8 18.8Z"/><path d="M9 9c0 3 3 6 6 6l1.2-1.6-2-1-1 .6c-.8-.4-1.6-1.2-2-2l.6-1-1-2Z"/></svg> Заявка на просчёт
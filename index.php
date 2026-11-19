<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

$csrf = csrf_token();

$realPhotos = array_map(
    fn($p) => 'uploads/' . basename($p),
    glob(__DIR__ . '/uploads/*.{jpg,jpeg,jfif,png,webp,JPG,JPEG,JFIF,PNG,WEBP}', GLOB_BRACE) ?: []
);
$photo = static fn(int $i): string => $realPhotos === [] ? '' : $realPhotos[$i % count($realPhotos)];

$phoneHref = 'tel:+79180892254';
$address   = 'г. Краснодар, ул. Конечная 2, 2 этаж';
$mapQuery  = rawurlencode('Краснодар, ул. Конечная, 2');

$services = ['Кухня', 'Шкаф или купе', 'Прихожая', 'Мебель для бизнеса', 'Другое'];

$starPath = '<path d="m11 1 3 6.4 7 .8-5.2 4.8 1.5 7L11 16.4 4.7 20l1.5-7L1 8.2l7-.8Z"/>';
$stars = '<svg width="96" height="18" viewBox="0 0 120 22" fill="currentColor" aria-label="5 из 5">';
foreach ([0, 24, 48, 72, 96] as $x) {
    $stars .= '<g transform="translate(' . $x . ' 0)">' . $starPath . '</g>';
}
$stars .= '</svg>';

$icPhone = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg>';
$icTg    = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 3 2 10.5l6 2.5L10 19l3-4 5 4Z"/></svg>';
$icInsta = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17" cy="7" r=".6" fill="currentColor"/></svg>';
$icMax   = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/></svg>';
$icPin   = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M10 21l2-8"/></svg>';
$icMap   = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11 22 2l-9 19-2-8Z"/></svg>';

$reviews = [
    ['text' => '«Соотношение цена/качество отличное, лучшее я бы сказала в Краснодаре». Всё было сделано в срок.', 'name' => 'Инна Л.', 'src' => '2ГИС · 22 января 2023'],
    ['text' => '«Быстрые сроки изготовления, монтаж идеальный». Знакомые заказали кухню по рекомендации.', 'name' => 'Татьяна С.', 'src' => '2ГИС · 4 августа 2023'],
    ['text' => '«Всё исполнили в лучшем виде! Очень довольна результатом». Заказывала мебель в салон красоты.', 'name' => 'Олеся А.', 'src' => '2ГИС · 6 октября 2023'],
    ['text' => '«Спасибо всем сотрудникам за тонкость в подходе, умение учесть все нюансы и пожелания».', 'name' => 'Виктория', 'src' => 'Яндекс Карты · 30 июля'],
    ['text' => '«Мы остались очень довольны сервисом и качеством работы, рекомендую от всей души».', 'name' => 'Д. Пономарева', 'src' => 'Яндекс Карты · 6 октября'],
];

$slides = [
    'Шкаф-прихожая, матовый фасад и дерево',
    'Большой проект, ЖК «Большой»',
    'Классика с фрезерованными фасадами',
    'Прихожая во всю стену',
    'Шкаф с зеркальными дверями',
    'Шкаф-купе со стеллажами',
    'Витрина со стеклом',
    'Угловой шкаф до потолка',
    'Прихожая с зеркалом и комодом',
];

$kitchenAlts = [
    'Белая кухня с деревянной столешницей',
    'Угловая кухня с фрезерованными фасадами',
    'Кухня с встроенной техникой',
    'Кухня с духовым шкафом',
];
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
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@600;700&family=Golos+Text:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--ink:#101210;--forest:#1A211D;--forest2:#26302A;--paper:#F5F1EA;--stone:#E4DCCF;--line:#D9D0C2;--muted:#5C574E;
--oak:#CF9A62;--oak-hi:#EDC593;--oak-dark:#8A5A2B;
--disp:'Oswald','Arial Narrow',sans-serif;--sans:'Golos Text',system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;--pad:24px;--r:28px}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth;-webkit-text-size-adjust:100%}
body{font-family:var(--sans);font-size:16px;line-height:1.55;color:var(--ink);background:var(--ink);overflow-x:hidden}
img{display:block;max-width:100%;height:auto}
a{color:inherit;text-decoration:none}
button{font:inherit}
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
.btn{display:flex;align-items:center;justify-content:center;gap:12px;min-height:60px;padding:0 28px;border-radius:999px;font-weight:700;font-size:16px;white-space:nowrap;transition:transform .2s;cursor:pointer;border:0}
.btn:active{transform:scale(.98)}
.btn:disabled{opacity:.6;cursor:default}
.btn svg{flex:none}
.btn-oak{color:#1B1209;background:linear-gradient(135deg,var(--oak-hi),var(--oak));box-shadow:0 14px 30px -16px rgba(207,154,98,.9)}
.btn-glass{color:#fff;background:rgba(255,255,255,.08);border:1.5px solid rgba(255,255,255,.4);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px)}
.btn-dark{color:#fff;background:var(--forest)}
.btn-line{color:var(--ink);border:1.5px solid rgba(16,18,16,.35)}

.sheet{position:relative;border-radius:36px 36px 0 0;margin-top:-36px;box-shadow:0 -26px 50px -30px rgba(0,0,0,.6)}
.bg-dark{background:radial-gradient(ellipse 70% 40% at 90% 0%,rgba(207,154,98,.2),transparent 70%),radial-gradient(ellipse 60% 40% at 0% 100%,rgba(90,120,100,.18),transparent 70%),#111411;color:#fff;overflow:hidden}
.bg-dark::before{content:"";position:absolute;inset:0;pointer-events:none;background:repeating-linear-gradient(90deg,rgba(255,255,255,.035) 0 1px,transparent 1px 120px)}
.bg-dark>.container{position:relative;z-index:1}
.bg-paper{background:radial-gradient(ellipse 90% 50% at 50% 0%,#fff,transparent 70%),var(--paper)}
.bg-stone{background:radial-gradient(ellipse 80% 50% at 15% 0%,rgba(255,255,255,.55),transparent 70%),var(--stone)}

.reveal{opacity:1;transform:none}
html.js .reveal{opacity:0;transform:translateY(24px);transition:opacity .8s ease,transform .8s ease}
html.js .reveal.in{opacity:1;transform:none}
@media (prefers-reduced-motion:reduce){html.js .reveal{opacity:1;transform:none;transition:none}html{scroll-behavior:auto}}
section, .reveal, [data-reveal]{opacity:1 !important;transform:none !important;visibility:visible !important}

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
.row h3{font-family:var
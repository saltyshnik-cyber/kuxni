<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

$csrf = csrf_token();

$realPhotos = array_map(
    fn($p) => 'uploads/' . basename($p),
    glob(__DIR__ . '/uploads/*.{jpg,jpeg,jfif,png,webp,JPG,JPEG,JFIF,PNG,WEBP}', GLOB_BRACE) ?: []
);

$fallbackPhoto = 'data:image/svg+xml;charset=utf-8,' . rawurlencode(
    '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="1000"><rect width="800" height="1000" fill="#26302A"/></svg>'
);

$photo = static fn(int $i): string => $realPhotos === [] ? $fallbackPhoto : $realPhotos[$i % count($realPhotos)];

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

$slides = array_slice($slides, 0, -3);

$kinds = [
    ['01', 'Кухни', 'Проект с менеджером, подбор фурнитуры, подсветка рабочей зоны.', 'от 100 тыс. ₽'],
    ['02', 'Шкафы и купе', 'Распашные и купе, зеркала, стеллажи, фасады с фрезеровкой.', ''],
    ['03', 'Прихожие', 'Шкафы до потолка, комоды, банкетки и зеркала.', ''],
    ['04', 'Мебель для бизнеса', 'Стойки, столы и шкафы для салонов красоты, офисов и студий.', ''],
];

$reviews = [
    ['«Соотношение цена/качество отличное, лучшее я бы сказала в Краснодаре». Всё сделали в срок.', 'Инна Л.', '2ГИС · 22 января 2023'],
    ['«Быстрые сроки изготовления, монтаж идеальный». Знакомые заказали кухню по рекомендации.', 'Татьяна С.', '2ГИС · 4 августа 2023'],
    ['«Всё исполнили в лучшем виде! Очень довольна результатом». Заказывала мебель в салон красоты.', 'Олеся А.', '2ГИС · 6 октября 2023'],
    ['«Спасибо сотрудникам за тонкость в подходе, умение учесть все нюансы и пожелания».', 'Виктория', 'Яндекс Карты · 30 июля'],
    ['«Остались очень довольны сервисом и качеством работы, рекомендую от всей души».', 'Д. Пономарева', 'Яндекс Карты · 6 октября'],
];

$steps = [
    ['Заявка', 'Звонок или сообщение. Уточняем задачу, размеры и сроки.'],
    ['Замер', 'Бесплатный замер по Краснодару и краю, эскиз и точный расчёт.'],
    ['Производство', 'Изготавливаем на своём производстве и монтируем под ключ.'],
];

$contacts = [
    ['Ксения · менеджер', '+7 (918) 089-22-54', 'tel:+79180892254', 'phone'],
    ['Telegram', '@mebelksenia', 'https://t.me/mebelksenia', 'tg'],
    ['Instagram', '@mebels.krd', 'https://www.instagram.com/mebels.krd', 'ig'],
    ['MAX', 'мессенджер', 'https://max.ru/', 'max'],
    ['Pinterest', 'наши проекты', 'https://www.pinterest.com/', 'pin'],
    ['Адрес', 'г. Краснодар, ул. Конечная 2, 2 этаж', 'https://yandex.ru/maps/?text=' . rawurlencode('Краснодар, улица Конечная, 2'), 'map'],
];

$icon = static function (string $key): string {
    $paths = [
        'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
        'tg'    => '<path d="M22 3 2 10.5l6 2.5L10 19l3-4 5 4Z"/>',
        'ig'    => '<rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17" cy="7" r=".6" fill="currentColor"/>',
        'max'   => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
        'pin'   => '<circle cx="12" cy="12" r="9"/><path d="M10 21l2-8"/>',
        'map'   => '<path d="M3 11 22 2l-9 19-2-8Z"/>',
    ];
    return '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths[$key] . '</svg>';
};

$phoneBtn = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg>';
$waBtn    = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20l1.2-4A8 8 0 1 1 8 18.8Z"/><path d="M9 9c0 3 3 6 6 6l1.2-1.6-2-1-1 .6c-.8-.4-1.6-1.2-2-2l.6-1-1-2Z"/></svg>';
$waLink   = 'https://wa.me/79180892254?text=' . rawurlencode('Здравствуйте! Хочу рассчитать мебель на заказ.');

$formFields = static function () use ($csrf): void { ?>
  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
  <input class="hp" type="text" name="company" tabindex="-1" autocomplete="off" aria-hidden="true">
  <label class="fld">Имя<input type="text" name="name" required minlength="2" maxlength="80" placeholder="Как к вам обращаться" autocomplete="name"></label>
  <label class="fld">Телефон<input type="tel" name="phone" required placeholder="+7 (___) ___-__-__" autocomplete="tel" inputmode="tel"></label>
  <label class="fld">Что нужно
    <select name="service">
      <option value="Кухня">Кухня</option>
      <option value="Шкаф или купе">Шкаф или купе</option>
      <option value="Прихожая">Прихожая</option>
      <option value="Мебель для бизнеса">Мебель для бизнеса</option>
      <option value="Другое">Другое</option>
    </select>
  </label>
  <label class="fld">Комментарий<textarea name="comment" maxlength="1000" rows="3" placeholder="Размеры, сроки, пожелания"></textarea></label>
  <button class="btn btn-oak" type="submit">Отправить заявку</button>
  <p class="form-note" role="status" aria-live="polite"></p>
<?php };
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#111411">
<title>Mebels.krd — кухни, шкафы и мебель на заказ в Краснодаре</title>
<meta name="description" content="Крупное мебельное производство в Краснодаре. Кухни от 100 тыс. рублей, шкафы, прихожие, мебель для бизнеса. Заявка на просчёт.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@600;700&family=Golos+Text:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--ink:#101210;--forest:#1A211D;--forest2:#26302A;--paper:#F5F1EA;--stone:#E4DCCF;--line:#D9D0C2;--muted:#5C574E;--oak:#CF9A62;--oak-hi:#EDC593;--oak-dark:#8A5A2B;--disp:'Oswald','Arial Narrow',sans-serif;--sans:'Golos Text',system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;--pad:24px;--r:28px}
*{box-sizing:border-box;margin:0;padding:0}html{scroll-behavior:smooth;-webkit-text-size-adjust:100%}body{font-family:var(--sans);font-size:16px;line-height:1.55;color:var(--ink);background:var(--ink);overflow-x:hidden}img{display:block;max-width:100%;height:auto}a{color:inherit;text-decoration:none}button{font:inherit}.hp{position:absolute;left:-9999px;width:1px;height:1px;opacity:0;pointer-events:none}.container{width:100%;max-width:1200px;margin:0 auto;padding:0 var(--pad)}.section{padding:88px 0;position:relative}.eyebrow{display:flex;align-items:center;gap:14px;font-size:12.5px;font-weight:700;letter-spacing:.28em;text-transform:uppercase;color:var(--oak-dark)}.eyebrow::before{content:"";width:28px;height:2px;background:currentColor}.on-dark .eyebrow{color:var(--oak)}.h2{font-family:var(--disp);font-weight:700;font-size:clamp(44px,12.5vw,84px);line-height:.98;text-transform:uppercase;margin-top:18px;letter-spacing:.005em}.h2 em{display:block;font-style:normal;color:var(--oak-dark)}.on-dark .h2 em{color:var(--oak)}.lead{font-size:17px;line-height:1.6;color:var(--muted);margin-top:20px;max-width:58ch}.on-dark .lead{color:rgba(245,241,234,.76)}.btn{display:flex;align-items:center;justify-content:center;gap:12px;min-height:60px;padding:0 28px;border:0;border-radius:999px;font-weight:700;font-size:16px;white-space:nowrap;transition:transform .2s;cursor:pointer;text-align:center}.btn:active{transform:scale(.98)}.btn:disabled{opacity:.6;cursor:default}.btn svg{flex:none}.btn-oak{color:#1B1209;background:linear-gradient(135deg,var(--oak-hi),var(--oak));box-shadow:0 14px 30px -16px rgba(207,154,98,.9)}.btn-glass{color:#fff;background:rgba(255,255,255,.08);border:1.5px solid rgba(255,255,255,.4);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px)}.btn-dark{color:#fff;background:var(--forest)}.btn-line{color:var(--ink);border:1.5px solid rgba(16,18,16,.35);background:transparent}
.sheet{position:relative;border-radius:36px 36px 0 0;margin-top:-36px;box-shadow:0 -26px 50px -30px rgba(0,0,0,.6)}.bg-dark{background-color:#111411;background-image:repeating-linear-gradient(90deg,rgba(255,255,255,.04) 0 1px,transparent 1px 25%),radial-gradient(ellipse 70% 40% at 90% 0%,rgba(207,154,98,.2),transparent 70%),radial-gradient(ellipse 60% 40% at 0% 100%,rgba(90,120,100,.18),transparent 70%);color:#fff;overflow:hidden}.bg-dark>.container{position:relative;z-index:1}.bg-paper{background:radial-gradient(ellipse 90% 50% at 50% 0%,#fff,transparent 70%),var(--paper)}.bg-stone{background:radial-gradient(ellipse 80% 50% at 15% 0%,rgba(255,255,255,.55),transparent 70%),var(--stone)}.reveal{opacity:1;transform:none}section,.reveal,[data-reveal]{opacity:1!important;transform:none!important;visibility:visible!important}
.header{position:fixed;top:0;left:0;right:0;z-index:50;padding:12px 0;background:rgba(16,18,16,.62);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border-bottom:1px solid rgba(255,255,255,.12);color:#fff;transition:background .3s,color .3s}.header .container{display:flex;align-items:center;gap:12px}.brand{display:flex;align-items:center;gap:12px;min-width:0;flex:1 1 auto}.mark{flex:none;width:44px;height:44px;border-radius:50%;display:grid;place-items:center;font-family:var(--disp);font-weight:700;font-size:15px;color:var(--oak-hi);background:var(--forest2);border:1.5px solid rgba(237,197,147,.55)}.brand b{display:block;font-family:var(--disp);font-size:19px;font-weight:700;letter-spacing:.12em;line-height:1.1;white-space:nowrap}.brand small{display:block;font-size:11.5px;color:rgba(255,255,255,.64);margin-top:3px;white-space:nowrap}.nav{display:none;gap:26px;font-size:14.5px;font-weight:600}.nav a{opacity:.85}.nav a:hover{opacity:1;color:var(--oak)}.header .tel{display:none}.burger{width:46px;height:46px;border-radius:50%;display:grid;place-items:center;border:1.5px solid rgba(255,255,255,.4);flex:none;background:transparent;color:inherit}.burger i,.burger i::before{display:block;width:18px;height:2px;background:currentColor;border-radius:2px;position:relative}.burger i::before{content:"";position:absolute;top:6px;left:0}.burger i{top:-3px}.header.scrolled{background:rgba(245,241,234,.94);color:var(--ink);border-bottom-color:var(--line)}.header.scrolled .brand small{color:var(--muted)}.menu{position:fixed;inset:0;z-index:100;background:#101210;color:#fff;padding:14px 0 28px;display:none;overflow:auto}.menu.open{display:block}.menu .top{display:flex;justify-content:space-between;align-items:center}.menu .x{width:46px;height:46px;border-radius:50%;display:grid;place-items:center;border:1.5px solid rgba(255,255,255,.4);font-size:22px;line-height:1;background:transparent;color:#fff}.menu ol{list-style:none;margin:30px 0 24px}.menu li a{display:flex;align-items:baseline;gap:16px;padding:14px 0;border-bottom:1px solid rgba(255,255,255,.14);font-family:var(--disp);font-weight:600;text-transform:uppercase;font-size:clamp(30px,9vw,44px);line-height:1.1}.menu li a span{font-family:var(--sans);font-size:13px;font-weight:700;letter-spacing:.2em;color:var(--oak)}.menu .btns{display:grid;gap:12px;margin-top:24px}.menu p{font-size:14px;color:rgba(255,255,255,.62);margin-top:18px}
.hero{position:relative;background:#111411;color:#fff;overflow:hidden;padding-bottom:104px}.hero-photo{position:relative;padding-top:66px}.hero-photo img{width:100%;height:auto}.hero-photo::after{content:"";position:absolute;left:0;right:0;bottom:0;height:55%;background:linear-gradient(to bottom,rgba(17,20,17,0),#111411 92%)}.hero-content{position:relative;z-index:2;margin-top:-26vw}.kick{font-size:13px;font-weight:700;letter-spacing:.3em;text-transform:uppercase;color:var(--oak-hi);margin-top:16px}.hero h1{font-family:var(--disp);font-weight:700;text-transform:uppercase;font-size:clamp(44px,12.6vw,120px);line-height:.94;margin-top:12px}.hero h1 em{display:block;font-style:normal;color:var(--oak)}.hero .sub{font-size:17px;color:rgba(255,255,255,.82);margin-top:18px;max-width:34ch}.hero-btns{display:grid;gap:12px;margin-top:26px}.hfacts{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));margin-top:32px;border-top:1px solid rgba(255,255,255,.18)}.hfacts div{padding:18px 6px 0}.hfacts div+div{border-left:1px solid rgba(255,255,255,.18);padding-left:14px}.hfacts b{display:block;white-space:nowrap;font-family:var(--disp);font-weight:700;font-size:clamp(18px,5.2vw,36px);line-height:1.1;color:var(--oak-hi)}.hfacts span{display:block;font-size:12.5px;line-height:1.35;color:rgba(255,255,255,.68);margin-top:5px}.about-facts{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));margin-top:34px;border-top:1px solid var(--line)}.about-facts div{padding:20px 6px 0}.about-facts div+div{border-left:1px solid var(--line);padding-left:14px}.about-facts b{display:block;font-family:var(--disp);font-weight:700;font-size:clamp(19px,5.6vw,40px);line-height:1;color:var(--oak-dark);white-space:nowrap}.about-facts span{display:block;font-size:12.5px;line-height:1.35;color:var(--muted);margin-top:6px}.about-p{margin-top:20px;font-size:17px;line-height:1.65;color:#2E2B26;max-width:60ch}.about-p+.about-p{margin-top:14px}
.gal{display:flex;gap:14px;overflow-x:auto;scroll-snap-type:x mandatory;padding:0 var(--pad) 8px;margin-top:34px;scrollbar-width:none;-webkit-overflow-scrolling:touch}.gal::-webkit-scrollbar{display:none}.slide{position:relative;flex:0 0 84%;max-width:520px;aspect-ratio:4/5;border-radius:var(--r);overflow:hidden;scroll-snap-align:center;background:#222}.slide img{width:100%;height:100%;object-fit:cover}.hint{margin-top:14px;font-size:13.5px;color:rgba(255,255,255,.6)}.hint a{color:var(--oak-hi);font-weight:700}
.rows{margin-top:32px;border-bottom:1px solid rgba(16,18,16,.2)}.row{display:grid;grid-template-columns:58px minmax(0,1fr);gap:8px;align-items:start;padding:22px 0;border-top:1px solid rgba(16,18,16,.2)}.row .n{font-family:var(--disp);font-weight:600;font-size:20px;color:var(--oak-dark);padding-top:4px}.row h3{font-family:var(--disp);font-weight:700;text-transform:uppercase;font-size:30px;line-height:1.05}.row p{margin-top:8px;font-size:15.5px;color:var(--muted)}.row .tag{display:inline-block;margin-top:12px;padding:6px 14px;border-radius:999px;background:var(--forest);color:var(--oak-hi);font-size:13.5px;font-weight:700}.kgrid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:34px}.kgrid figure{position:relative;border-radius:22px;overflow:hidden;aspect-ratio:3/2;background:#222;margin:0}.kgrid figure img{width:100%;height:100%;object-fit:cover}.kgrid .wide{grid-column:1/-1;aspect-ratio:4/5}.kgrid .wide::after{content:"";position:absolute;inset:0;background:linear-gradient(to bottom,transparent 50%,rgba(16,18,16,.88))}.kprice{position:absolute;z-index:2;left:22px;right:22px;bottom:22px}.kprice small{display:block;font-size:12px;font-weight:700;letter-spacing:.24em;text-transform:uppercase;color:var(--oak-hi)}.kprice b{display:block;font-family:var(--disp);font-weight:700;font-size:clamp(40px,11vw,64px);line-height:1;margin-top:6px}.kprice span{display:block;font-size:14.5px;color:rgba(255,255,255,.8);margin-top:6px}.score{display:flex;align-items:center;gap:18px;margin-top:30px;padding:22px;border-radius:var(--r);background:var(--forest);color:#fff}.score b{white-space:nowrap;font-family:var(--disp);font-weight:700;font-size:46px;line-height:1;color:var(--oak-hi)}.score span{font-size:14.5px;color:rgba(245,241,234,.78);line-height:1.4}
.revs{display:flex;gap:14px;overflow-x:auto;scroll-snap-type:x mandatory;padding:0 var(--pad) 8px;margin-top:18px;scrollbar-width:none}.revs::-webkit-scrollbar{display:none}.rev{flex:0 0 82%;max-width:380px;scroll-snap-align:center;padding:24px 22px;border-radius:var(--r);background:#fff;border:1px solid var(--line);display:flex;flex-direction:column}.rev .stars{color:#DDA03A;letter-spacing:.1em;font-size:15px}.rev p{margin-top:12px;font-size:16px;line-height:1.55;color:#2C2A26}.rev footer{margin-top:auto;padding-top:18px;font-size:13.5px;color:var(--muted)}.rev footer b{display:block;font-size:15px;color:var(--ink)}.steps{display:grid;gap:14px;margin-top:34px}.step{display:flex;gap:18px;padding:22px;border-radius:var(--r);background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.16)}.step .n{flex:none;width:52px;height:52px;border-radius:50%;display:grid;place-items:center;font-family:var(--disp);font-weight:700;font-size:20px;color:#1B1209;background:linear-gradient(135deg,var(--oak-hi),var(--oak))}.step h3{font-family:var(--disp);font-weight:600;text-transform:uppercase;font-size:24px;line-height:1.1}.step p{margin-top:8px;font-size:15px;color:rgba(245,241,234,.74)}
.form{display:grid;gap:14px;margin-top:30px;max-width:520px}.fld{display:grid;gap:7px;font-size:12px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--muted)}.form input,.form select,.form textarea{width:100%;min-height:56px;padding:14px 18px;border-radius:16px;border:1.5px solid var(--line);background:#fff;font-family:var(--sans);font-size:16px;font-weight:500;letter-spacing:0;text-transform:none;color:var(--ink)}.form textarea{min-height:100px;resize:vertical}.form input:focus,.form select:focus,.form textarea:focus{outline:2px solid var(--oak);outline-offset:2px;border-color:var(--oak)}.form .hp{position:absolute;left:-9999px;width:1px;min-height:0;height:1px;opacity:0;pointer-events:none}.form-note{font-size:14px;min-height:20px;color:var(--muted)}.form-note.err{color:#B0432B}.form-note.ok{color:#2E7D4F}.clist{list-style:none;margin-top:26px;border-top:1px solid var(--line)}.clist li{border-bottom:1px solid var(--line)}.clist a{display:flex;align-items:center;gap:16px;padding:15px 0}.clist .ic{flex:none;width:46px;height:46px;border-radius:50%;display:grid;place-items:center;background:var(--forest);color:var(--oak-hi)}.clist small{display:block;font-size:11.5px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:var(--oak-dark)}.clist b{display:block;font-size:16.5px;overflow-wrap:anywhere}.mapbtns{display:flex;flex-wrap:wrap;gap:10px;margin-top:20px}.mapbtns a{display:inline-flex;align-items:center;gap:8px;padding:11px 18px;border-radius:999px;font-weight:700;font-size:14.5px;background:var(--forest);color:#fff}.mapbtns a+a{background:transparent;color:var(--ink);border:1.5px solid rgba(16,18,16,.3)}.footer{padding:38px 0 120px;color:rgba(255,255,255,.6);font-size:13.5px;background:#0C0E0C}.footer .container{display:grid;gap:10px}.footer b{font-family:var(--disp);letter-spacing:.14em;color:#fff;font-size:18px}.footer a{color:rgba(255,255,255,.82)}.dock{position:fixed;left:12px;right:12px;bottom:calc(12px + env(safe-area-inset-bottom));z-index:40;display:flex;gap:10px;padding:10px;border-radius:999px;background:rgba(16,18,16,.88);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.24);box-shadow:0 18px 40px -16px rgba(0,0,0,.8)}.dock .btn{min-height:52px;flex:1;padding:0 18px;font-size:15px}.dock .ico{flex:none;width:52px;min-height:52px;padding:0;color:var(--oak-hi);border:1.5px solid rgba(255,255,255,.38);border-radius:50%;background:transparent;display:grid;place-items:center}.modal{position:fixed;inset:0;z-index:120;display:none;align-items:center;justify-content:center;padding:18px;background:rgba(9,11,9,.72);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);overflow:auto}.modal.open{display:flex}.modal .box{width:100%;max-width:480px;background:var(--paper);border-radius:26px;padding:26px 22px;position:relative;box-shadow:0 40px 80px -30px #000;margin:auto}.modal h3{font-family:var(--disp);font-weight:700;text-transform:uppercase;font-size:30px;line-height:1.05}.modal .sub{font-size:14.5px;color:var(--muted);margin-top:8px}.modal .x{position:absolute;top:12px;right:12px;width:42px;height:42px;border-radius:50%;border:1.5px solid var(--line);background:transparent;font-size:22px;line-height:1;color:var(--ink)}
@media (min-width:640px){.hero-btns{grid-template-columns:auto auto;justify-content:start}}
@media (min-width:960px){:root{--pad:40px}.section{padding:120px 0}.nav{display:flex}.burger{display:none}.brand{flex:0 0 auto;margin-right:auto}.header .tel{display:inline-flex;margin-left:26px;min-height:46px;padding:0 22px;font-size:14.5px}.dock{display:none}.hero{padding:0 0 124px;min-height:100vh;display:flex;align-items:center}.hero .wrap{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(0,.9fr);gap:56px;align-items:center;padding:120px 40px 0;width:100%;max-width:1200px;margin:0 auto}.hero-photo{order:2;padding-top:0;border-radius:var(--r);overflow:hidden;border:1px solid rgba(255,255,255,.22);box-shadow:0 40px 80px -30px #000}.hero-photo::after{display:none}.hero-content{order:1;margin-top:0;padding:0}.hero h1{font-size:clamp(52px,5.4vw,82px)}.h2{font-size:clamp(56px,6vw,84px)}.about-grid{display:grid;grid-template-columns:1fr 1fr;gap:64px;align-items:start}.rows{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));column-gap:48px}.kgrid{grid-template-columns:repeat(4,minmax(0,1fr))}.kgrid .wide{grid-column:span 2;grid-row:span 2;aspect-ratio:auto}.gal{padding-left:max(40px,calc((100vw - 1200px)/2 + 40px))}.slide{flex-basis:400px}.steps{grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}.step{flex-direction:column}.revs{padding-left:max(40px,calc((100vw - 1200px)/2 + 40px))}.rev{flex-basis:360px}.footer{padding-bottom:56px}.footer .container{grid-template-columns:1fr auto;align-items:center}}
body{background-image:repeating-linear-gradient(90deg,rgba(255,255,255,.04) 0 1px,transparent 1px 25%)!important}
.bg-dark,.hero{background-image:repeating-linear-gradient(90deg,rgba(255,255,255,.04) 0 1px,transparent 1px 25%),radial-gradient(ellipse 70% 40% at 90% 0%,rgba(207,154,98,.2),transparent 70%),radial-gradient(ellipse 60% 40% at 0% 100%,rgba(90,120,100,.18),transparent 70%)!important}
</style>
</head>
<body>
<header class="header" id="hdr"><div class="container">
  <a class="brand" href="#top"><span class="mark">MK</span><span><b>MEBELS.KRD</b><small>Мебель на заказ · Краснодар</small></span></a>
  <nav class="nav"><a href="#works">Работы</a><a href="#kinds">Что делаем</a><a href="#kitchens">Кухни</a><a href="#reviews">Отзывы</a><a href="#contacts">Контакты</a></nav>
  <button class="btn btn-oak tel" type="button" data-modal-open>Заявка на просчёт</button>
  <button class="burger" type="button" data-menu-open aria-label="Меню"><i></i></button>
</div></header>

<div class="menu" id="menu"><div class="container">
  <div class="top"><span class="brand"><span class="mark">MK</span><span><b>MEBELS.KRD</b></span></span><button class="x" type="button" data-menu-close aria-label="Закрыть">&times;</button></div>
  <ol>
    <li><a href="#about"><span>01</span>О нас</a></li>
    <li><a href="#works"><span>02</span>Работы</a></li>
    <li><a href="#kinds"><span>03</span>Что делаем</a></li>
    <li><a href="#kitchens"><span>04</span>Кухни и цены</a></li>
    <li><a href="#reviews"><span>05</span>Отзывы</a></li>
    <li><a href="#lead"><span>06</span>Заявка</a></li>
    <li><a href="#contacts"><span>07</span>Контакты</a></li>
  </ol>
  <div class="btns">
    <button class="btn btn-oak" type="button" data-modal-open>Заявка на просчёт</button>
    <a class="btn btn-glass" href="tel:+79180892254"><?= $phoneBtn ?> +7 (918) 089-22-54</a>
  </div>
  <p>г. Краснодар, ул. Конечная 2, 2 этаж · пн–сб 9:00–17:00</p>
</div></div>

<main id="top">
<section class="hero"><div class="wrap">
  <div class="hero-photo"><img src="<?= e($photo(0)) ?>" alt="Шкаф-прихожая в матовом фасаде и дубе" width="960" height="1195"></div>
  <div class="hero-content container">
    <p class="kick">Кухни · Шкафы · Прихожие</p>
    <h1>Мебель на заказ<em>в Краснодаре</em></h1>
    <p class="sub">Крупное мебельное производство. Сохраним вам нервы, время и деньги.</p>
    <div class="hero-btns">
      <button class="btn btn-oak" type="button" data-modal-open>Оставить заявку на просчёт</button>
      <a class="btn btn-glass" href="tel:+79180892254"><?= $phoneBtn ?> Позвонить</a>
    </div>
    <div class="hfacts">
      <div><b>от 100 тыс.</b><span>кухни, ₽</span></div>
      <div><b>6 дней</b><span>в неделю на связи</span></div>
      <div><b>под ключ</b><span>проект и монтаж</span></div>
    </div>
  </div>
</div></section>

<section class="section sheet bg-paper" id="about"><div class="container reveal">
  <div class="about-grid">
    <div>
      <div class="eyebrow">О нас</div>
      <h2 class="h2">Делаем сами<em>без нервов</em></h2>
    </div>
    <div>
      <p class="about-p">Мебельное производство в Краснодаре. Кухни, шкафы, прихожие и мебель для салонов красоты — по индивидуальным размерам.</p>
      <p class="about-p">Клиенты в отзывах отдельно отмечают менеджера Ксению: считает проект, подсказывает, на чём можно сэкономить, и держит связь до монтажа.</p>
      <div class="about-facts">
        <div><b>100 тыс. ₽</b><span>кухни от</span></div>
        <div><b>Пн–Сб</b><span>9:00–17:00</span></div>
        <div><b>593</b><span>работы в Instagram</span></div>
      </div>
    </div>
  </div>
</div></section>

<section class="section sheet bg-dark on-dark" id="works"><div class="container reveal">
  <div class="eyebrow">Наши работы</div>
  <h2 class="h2">Шкафы, которые<em>делаем сами</em></h2>
  <p class="lead">Реальные проекты нашего производства. Листайте вбок.</p>
</div>
<div class="gal">
  <?php foreach ($slides as $i => $title): ?>
  <div class="slide">
    <img src="<?= e($photo($i + 1)) ?>" alt="<?= e($title) ?>" loading="<?= $i === 0 ? 'eager' : 'lazy' ?>">
  </div>
  <?php endforeach; ?>
</div>
<div class="container"><p class="hint">Больше работ в Instagram <a href="https://www.instagram.com/mebels.krd" target="_blank" rel="noopener">@mebels.krd</a></p></div>
</section>

<section class="section sheet bg-stone" id="kinds"><div class="container reveal">
  <div class="eyebrow">Что делаем</div>
  <h2 class="h2">Четыре<em>направления</em></h2>
  <div class="rows">
    <?php foreach ($kinds as [$num, $title, $desc, $tag]): ?>
    <div class="row">
      <span class="n"><?= e($num) ?></span>
      <div>
        <h3><?= e($title) ?></h3>
        <p><?= e($desc) ?></p>
        <?php if ($tag !== ''): ?><span class="tag"><?= e($tag) ?></span><?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div></section>

<section class="section sheet bg-dark on-dark" id="kitchens"><div class="container reveal">
  <div class="eyebrow">Кухни и цены</div>
  <h2 class="h2">Кухни<em>на заказ</em></h2>
  <div class="kgrid">
    <figure class="wide">
      <img src="<?= e($photo(10)) ?>" alt="Угловая кухня в белом и графитовом" loading="lazy">
      <div class="kprice"><small>Под ключ</small><b>от 100 тыс. ₽</b><span>Проект, материалы, фурнитура и монтаж</span></div>
    </figure>
    <figure><img src="<?= e($photo(11)) ?>" alt="Кухня с деревянной столешницей" loading="lazy"></figure>
    <figure><img src="<?= e($photo(12)) ?>" alt="Кухня с фрезерованными фасадами" loading="lazy"></figure>
    <figure><img src="<?= e($photo(13)) ?>" alt="Кухня с встроенной техникой" loading="lazy"></figure>
    <figure><img src="<?= e($photo(14)) ?>" alt="Кухня с подсветкой рабочей зоны" loading="lazy"></figure>
  </div>
  <div class="score"><b>4.9</b><span>Средняя оценка в 2ГИС и Яндекс Картах. Более 590 проектов в портфолио.</span></div>
</div></section>

<section class="section sheet bg-paper" id="reviews"><div class="container reveal">
  <div class="eyebrow">Отзывы</div>
  <h2 class="h2">Что говорят<em>клиенты</em></h2>
</div>
<div class="revs">
  <?php foreach ($reviews as [$text, $name, $src]): ?>
  <article class="rev">
    <div class="stars" aria-label="5 из 5">★★★★★</div>
    <p><?= e($text) ?></p>
    <footer><b><?= e($name) ?></b><?= e($src) ?></footer>
  </article>
  <?php endforeach; ?>
</div>
<div class="container"><p class="hint" style="color:var(--muted)">Отзывы клиентов из 2ГИС и Яндекс Карт.</p></div>
</section>

<section class="section sheet bg-dark on-dark" id="how"><div class="container reveal">
  <div class="eyebrow">Как мы работаем</div>
  <h2 class="h2">Три шага<em>до кухни</em></h2>
  <div class="steps">
    <?php foreach ($steps as $i => [$title, $desc]): ?>
    <div class="step">
      <span class="n"><?= $i + 1 ?></span>
      <div><h3><?= e($title) ?></h3><p><?= e($desc) ?></p></div>
    </div>
    <?php endforeach; ?>
  </div>
</div></section>

<section class="section sheet bg-paper" id="lead"><div class="container reveal">
  <div class="eyebrow">Заявка</div>
  <h2 class="h2">Рассчитать<em>стоимость</em></h2>
  <p class="lead">Оставьте имя и телефон — Ксения перезвонит, задаст пару вопросов и пришлёт расчёт. Без навязчивых звонков.</p>
  <form class="form js-lead" method="post" action="api/submit.php" novalidate>
    <?php $formFields(); ?>
  </form>
</div></section>

<section class="section sheet bg-stone" id="contacts"><div class="container reveal">
  <div class="eyebrow">Контакты</div>
  <h2 class="h2">Ксения<em>на связи</em></h2>
  <p class="lead">Менеджер производства. Пишите в любой мессенджер — ответим в рабочее время.</p>
  <ul class="clist">
    <?php foreach ($contacts as [$label, $value, $href, $key]): ?>
    <li><a href="<?= e($href) ?>"<?= str_starts_with($href, 'http') ? ' target="_blank" rel="noopener"' : '' ?>><span class="ic"><?= $icon($key) ?></span><span><small><?= e($label) ?></small><b><?= e($value) ?></b></span></a></li>
    <?php endforeach; ?>
  </ul>
  <div class="mapbtns">
    <a href="https://yandex.ru/maps/?text=<?= rawurlencode('Краснодар, улица Конечная, 2') ?>" target="_blank" rel="noopener">Яндекс Карты</a>
    <a href="https://2gis.ru/krasnodar/search/<?= rawurlencode('Конечная 2') ?>" target="_blank" rel="noopener">2ГИС</a>
  </div>
</div></section>
</main>

<footer class="footer"><div class="container">
  <div><b>MEBELS.KRD</b><div>Кухни, шкафы и мебель на заказ · Краснодар, ул. Конечная 2, 2 этаж</div></div>
  <div><a href="tel:+79180892254">+7 (918) 089-22-54</a> · пн–сб 9:00–17:00 · © <?= date('Y') ?></div>
</div></footer>

<div class="dock">
  <button class="btn btn-oak" type="button" data-modal-open>Рассчитать стоимость</button>
  <a class="ico btn" href="tel:+79180892254" aria-label="Позвонить"><?= $phoneBtn ?></a>
  <a class="ico btn" href="<?= e($waLink) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><?= $waBtn ?></a>
</div>

<div class="modal" id="leadModal" role="dialog" aria-
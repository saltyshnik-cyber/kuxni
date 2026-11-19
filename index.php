<span><small>Позвонить</small><b><?= e($ct['phone_text']) ?></b></span>
          </a>
          <a href="<?= e($wa) ?>" target="_blank" rel="noopener">
            <span class="ic">WA</span>
            <span><small>WhatsApp</small><b>Написать в мессенджер</b></span>
          </a>
          <a href="<?= e($ct['telegram']) ?>" target="_blank" rel="noopener">
            <span class="ic">TG</span>
            <span><small>Telegram</small><b>Задать вопрос</b></span>
          </a>
        </div>
      </div>
      <div class="leadcard reveal">
        <h3><?= e($form['intro']) ?></h3>
        <?= lead_form('lead-form', $form, $csrf) ?>
        <p class="lf-note"><?= e($form['note']) ?></p>
      </div>
    </div>
  </div>
</section>

<section class="section bg-stone stack reveal" id="contacts" style="--z:9">
  <div class="container">
    <p class="eyebrow"><?= e($cs['eyebrow']) ?></p>
    <h2 class="h2"><?= e($cs['title']) ?><em><?= e($cs['title_accent']) ?></em></h2>
    <div class="cards">
      <div class="ccard dark">
        <h3>Адрес и график</h3>
        <p><?= e($ct['address']) ?></p>
        <div class="open"><i></i><?= e($ct['hours']) ?></div>
        <?= socials_html($socials) ?>
      </div>
      <div class="ccard">
        <h3>Связаться<?= $manager !== '' ? ' с ' . e($manager) : '' ?></h3>
        <div class="clist" style="margin-top:16px">
          <a href="<?= e($tel) ?>"><span class="ic">☎</span><span><small>Телефон</small><b><?= e($ct['phone_text']) ?></b></span></a>
          <a href="<?= e($mailto) ?>"><span class="ic">@</span><span><small>Почта</small><b><?= e($ct['email']) ?></b></span></a>
        </div>
        <?= socials_html($socials) ?>
      </div>
      <div class="ccard">
        <h3>Как добраться</h3>
        <p>Шоурум с образцами фасадов и столешниц — приезжайте выбрать материалы.</p>
        <div class="mapbtns">
          <a href="<?= e($ct['maps_yandex']) ?>" target="_blank" rel="noopener">Яндекс Карты</a>
          <a href="<?= e($ct['maps_2gis']) ?>" target="_blank" rel="noopener">2ГИС</a>
        </div>
      </div>
    </div>
  </div>
</section>

<footer class="footer">
  <div class="container">
    <b>MEBELS.KRD</b>
    <span><?= e($ct['address']) ?> · <?= e($ct['phone_text']) ?></span>
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
    <p class="sub"><?= e($form['intro']) ?> — перезвоним в течение рабочего дня.</p>
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
          Object.entries(data.errors || {}).forEach(([field, message]) => setFieldError(form, field, message));
          showToast(false, 'Проверьте форму', data.message);
        }
      } catch (error) {
        showToast(false, 'Нет связи', 'Попробуйте ещё раз или позвоните нам.');
      } finally {
        button.disabled = false;
        button.textContent = label;
      }
    });
  });
})();
</script>
</body>
</html>
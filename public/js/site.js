/* =========================================================
   Magic Frames — dynamic behaviour (forms, transitions, text reveal)
   ========================================================= */
(function () {
  'use strict';
  const $  = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- Enquiry form → POST /api/v1/enquiries ---------- */
  $$('form[data-enquiry]').forEach(form => {
    const status = $('[data-status]', form);
    const btn = $('button[type=submit]', form);
    const label = btn.textContent;

    const clearErrors = () => {
      $$('[data-error]', form).forEach(e => (e.textContent = ''));
      $$('.field.has-error', form).forEach(f => f.classList.remove('has-error'));
      status.textContent = ''; status.className = 'form-status';
    };
    const showErrors = errors => {
      Object.entries(errors).forEach(([name, msgs]) => {
        const holder = $(`[data-error="${name}"]`, form);
        if (holder) { holder.textContent = msgs[0]; holder.closest('.field').classList.add('has-error'); }
      });
      const first = $('.has-error input, .has-error select, .has-error textarea', form);
      if (first) first.focus();
    };

    form.addEventListener('submit', async e => {
      e.preventDefault();
      clearErrors();
      if (!form.checkValidity()) {
        // browser-native hints first (required, email, min length)
        const bad = [...form.elements].find(el => el.willValidate && !el.checkValidity());
        const holder = bad && $(`[data-error="${bad.name}"]`, form);
        if (holder) { holder.textContent = bad.validationMessage; holder.closest('.field').classList.add('has-error'); bad.focus(); }
        return;
      }

      const payload = Object.fromEntries(new FormData(form).entries());
      payload.source_page = location.pathname;
      Object.keys(payload).forEach(k => payload[k] === '' && delete payload[k]);

      btn.disabled = true; btn.textContent = 'Sending…';
      try {
        const res = await fetch('/api/v1/enquiries', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify(payload),
        });
        const data = await res.json().catch(() => ({}));
        if (res.status === 201) {
          form.reset();
          status.textContent = data.message || 'Thank you! We will get back to you soon.';
          status.classList.add('ok');
        } else if (res.status === 422 && data.errors) {
          showErrors(data.errors);
          status.textContent = 'Please check the highlighted fields.'; status.classList.add('err');
        } else if (res.status === 429) {
          status.textContent = 'You have sent several requests in a short time. Please wait a minute and try again.'; status.classList.add('err');
        } else {
          throw new Error('Unexpected response ' + res.status);
        }
      } catch (err) {
        status.textContent = 'Sorry, something went wrong. Please try again or contact us directly.'; status.classList.add('err');
      } finally {
        btn.disabled = false; btn.textContent = label;
      }
    });
  });

  /* ---------- Section title word reveal ---------- */
  if (!reduceMotion && 'IntersectionObserver' in window) {
    const titles = $$('.section-title, .quote__title').filter(t => !t.closest('.hero') && !t.querySelector('*'));
    const io = new IntersectionObserver((entries, obs) => entries.forEach(en => {
      if (en.isIntersecting) { en.target.classList.add('in'); obs.unobserve(en.target); }
    }), { threshold: 0.4 });
    titles.forEach(t => {
      const text = t.textContent.trim();
      t.setAttribute('aria-label', text);
      t.classList.add('words');
      t.innerHTML = text.split(/\s+/).map((w, i) =>
        `<span class="w" aria-hidden="true"><span style="--i:${i}">${w.replace(/[&<>]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c]))}</span></span>`
      ).join(' ');
      io.observe(t);
    });
  }

  /* ---------- Smooth page transitions (internal links only) ---------- */
  if (!reduceMotion) {
    document.addEventListener('click', e => {
      const a = e.target.closest('a[href]');
      if (!a || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
      if (a.target && a.target !== '_self') return;
      const url = new URL(a.href, location.href);
      if (url.origin !== location.origin || url.pathname.startsWith('/admin')) return;
      if (url.pathname === location.pathname && url.search === location.search) return; // same page / anchors
      if (a.hasAttribute('download')) return;
      e.preventDefault();
      document.body.classList.add('is-leaving');
      setTimeout(() => (location.href = a.href), 280);
    });
    // restore page when coming back via the browser's back button
    window.addEventListener('pageshow', e => { if (e.persisted) document.body.classList.remove('is-leaving'); });
  }
})();

/* =========================================================
   Bisou — interactions
   ========================================================= */
(function () {
  'use strict';

  const $  = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];

  /* ---------- Preloader ---------- */
  window.addEventListener('load', () => {
    const pre = $('#preloader');
    setTimeout(() => pre && pre.classList.add('hide'), 900);
  });

  /* ---------- Year ---------- */
  const yr = $('#year');
  if (yr) yr.textContent = new Date().getFullYear();

  /* ---------- Sticky header ---------- */
  const header = $('#header');
  // Pages with a hero (home) get a header that changes on scroll; inner pages stay solid
  // unless the admin chose the "transparent over banner" header style.
  const transparentStyle = header && header.dataset.style === 'transparent_on_top';
  const hasHero = !!$('.hero') || (transparentStyle && !!$('.page-banner'));
  const onScrollHeader = () => {
    if (!header) return;
    if (!hasHero) { header.classList.add('scrolled'); return; }
    header.classList.toggle('scrolled', window.scrollY > 80);
  };
  onScrollHeader();
  window.addEventListener('scroll', onScrollHeader, { passive: true });

  /* ---------- Mobile menu ---------- */
  const burger = $('#burger');
  const mobileMenu = $('#mobileMenu');
  const mobileClose = $('#mobileClose');
  const openMenu  = () => { mobileMenu.classList.add('open'); document.body.classList.add('menu-open'); burger && burger.setAttribute('aria-expanded', 'true'); const c = $('#mobileClose'); c && setTimeout(() => c.focus(), 50); };
  const closeMenu = () => { mobileMenu.classList.remove('open'); document.body.classList.remove('menu-open'); burger && burger.setAttribute('aria-expanded', 'false'); };
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMenu(); });
  burger && burger.addEventListener('click', openMenu);
  mobileClose && mobileClose.addEventListener('click', closeMenu);
  $$('#mobileMenu a').forEach(a => a.addEventListener('click', closeMenu));

  /* ---------- Smooth anchor scroll (respects fixed header) ---------- */
  $$('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
      const id = a.getAttribute('href');
      if (id.length < 2) return;
      const target = $(id);
      if (!target) return;
      e.preventDefault();
      const top = target.getBoundingClientRect().top + window.scrollY - 70;
      window.scrollTo({ top, behavior: 'smooth' });
    });
  });

  /* ---------- Custom cursor ---------- */
  const cursor = $('#cursor');
  const follow = $('#cursorFollow');
  if (cursor && follow && window.matchMedia('(hover:hover)').matches) {
    let mx = 0, my = 0, fx = 0, fy = 0;
    window.addEventListener('mousemove', e => {
      mx = e.clientX; my = e.clientY;
      cursor.style.transform = `translate(${mx}px,${my}px) translate(-50%,-50%)`;
    });
    const loop = () => {
      fx += (mx - fx) * 0.15;
      fy += (my - fy) * 0.15;
      follow.style.transform = `translate(${fx}px,${fy}px) translate(-50%,-50%)`;
      requestAnimationFrame(loop);
    };
    loop();
    $$('a, button, .gallery__item, .service, .vcard, .pf-card').forEach(el => {
      el.addEventListener('mouseenter', () => follow.classList.add('grow'));
      el.addEventListener('mouseleave', () => follow.classList.remove('grow'));
    });
  }

  /* ---------- Scroll reveal (IntersectionObserver) ---------- */
  const revealEls = $$('[data-reveal], .reveal-scroll');
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries, obs) => {
      entries.forEach(en => {
        if (en.isIntersecting) {
          en.target.classList.add('in');
          obs.unobserve(en.target);
        }
      });
    }, { threshold: 0.15 });
    revealEls.forEach(el => io.observe(el));
  } else {
    revealEls.forEach(el => el.classList.add('in'));
  }

  /* ---------- Parallax (non-fixed backgrounds) ---------- */
  const parallaxEls = $$('[data-parallax]');
  const onParallax = () => {
    const vh = window.innerHeight;
    parallaxEls.forEach(el => {
      const speed = parseFloat(el.dataset.parallax) || 0.2;
      const rect = el.parentElement.getBoundingClientRect();
      if (rect.bottom < 0 || rect.top > vh) return;
      const offset = (rect.top - vh / 2) * -speed;
      el.style.transform = `translateY(${offset}px) scale(1.05)`;
    });
  };
  onParallax();
  window.addEventListener('scroll', onParallax, { passive: true });

  /* ---------- Animated counters ---------- */
  const counters = $$('.counter__num');
  if (counters.length && 'IntersectionObserver' in window) {
    const runCount = el => {
      const target = +el.dataset.count;
      const suffix = el.dataset.suffix || '';
      const dur = 1800;
      const start = performance.now();
      const tick = now => {
        const p = Math.min((now - start) / dur, 1);
        const eased = 1 - Math.pow(1 - p, 3);
        el.textContent = Math.floor(eased * target).toLocaleString() + suffix;
        if (p < 1) requestAnimationFrame(tick);
        else el.textContent = target.toLocaleString() + suffix;
      };
      requestAnimationFrame(tick);
    };
    const cio = new IntersectionObserver((entries, obs) => {
      entries.forEach(en => {
        if (en.isIntersecting) { runCount(en.target); obs.unobserve(en.target); }
      });
    }, { threshold: 0.5 });
    counters.forEach(c => cio.observe(c));
  }

  /* ---------- Hero slider (images and/or background videos with sound controls) ---------- */
  const heroSlides = $$('#heroSlides .hero__slide');
  const heroDots = $('#heroDots');
  if (heroSlides.length && heroDots) {
    let hi = 0, htimer;
    const multi = heroSlides.length > 1;
    const soundBox = $('#heroSound'), muteBtn = $('#heroMute'), volBar = $('#heroVol');
    // Shared sound state. Browsers only allow autoplay WITH sound in some cases, so we start
    // muted when needed and switch the sound on at the visitor's first click / tap / key press.
    const snd = { muted: true, volume: 0.7, chosen: false };

    heroSlides.forEach((_, i) => {
      const b = document.createElement('button');
      b.setAttribute('aria-label', `Slide ${i + 1}`);
      if (i === 0) b.classList.add('active');
      b.addEventListener('click', () => { heroGo(i); heroReset(); });
      heroDots.appendChild(b);
    });
    const hDots = $$('button', heroDots);

    const mediaOf = k => {
      const sl = heroSlides[k];
      return { video: sl.querySelector('.hero__video'), frame: sl.querySelector('.hero__embed') };
    };
    const post = (f, msg) => { try { f.contentWindow && f.contentWindow.postMessage(typeof msg === 'string' ? msg : JSON.stringify(msg), '*'); } catch (e) {} };
    // Send the current sound state to the player on the visible slide
    function applySound() {
      const { video, frame } = mediaOf(hi);
      if (video) { video.volume = snd.volume; video.muted = snd.muted; }
      if (frame && frame.getAttribute('src')) {
        if (frame.classList.contains('hero__embed--youtube')) {
          post(frame, { event: 'command', func: snd.muted ? 'mute' : 'unMute', args: [] });
          post(frame, { event: 'command', func: 'setVolume', args: [Math.round(snd.volume * 100)] });
        } else if (frame.classList.contains('hero__embed--vimeo')) {
          post(frame, { method: 'setMuted', value: snd.muted });
          post(frame, { method: 'setVolume', value: snd.volume });
        }
      }
      if (muteBtn) {
        muteBtn.setAttribute('aria-pressed', snd.muted ? 'true' : 'false');
        muteBtn.setAttribute('aria-label', snd.muted ? 'Turn sound on' : 'Turn sound off');
        muteBtn.innerHTML = `<i class="fa-solid ${snd.muted || snd.volume === 0 ? 'fa-volume-xmark' : (snd.volume < 0.4 ? 'fa-volume-low' : 'fa-volume-high')}"></i>`;
      }
      if (volBar) volBar.value = Math.round(snd.volume * 100);
    }
    // Show the controls only when the visible slide has a video whose sound we can control (not Drive)
    function updateControls() {
      if (!soundBox) return;
      const { video, frame } = mediaOf(hi);
      const ok = !!video || (!!frame && !frame.classList.contains('hero__embed--drive'));
      soundBox.hidden = !ok;
      if (ok) applySound();
    }
    function userSound(muted) { snd.muted = muted; snd.chosen = true; applySound(); }
    if (muteBtn) muteBtn.addEventListener('click', () => {
      if (snd.muted && snd.volume === 0) snd.volume = 0.7;
      userSound(!snd.muted);
    });
    if (volBar) volBar.addEventListener('input', () => {
      snd.volume = volBar.value / 100;
      snd.muted = snd.volume === 0;
      snd.chosen = true;
      applySound();
    });

    // Play the visible slide's video, pause / unload the others
    function heroVideos() {
      heroSlides.forEach((sl, k) => {
        const { video: v, frame: f } = mediaOf(k);
        if (f) {                                   // YouTube / Vimeo / Drive: load when shown, unload to stop
          if (k === hi) {
            if (f.getAttribute('src') !== f.dataset.src) f.setAttribute('src', f.dataset.src);
          } else if (f.getAttribute('src')) f.removeAttribute('src');
        }
        if (!v) return;
        if (k !== hi) { v.pause(); return; }
        v.loop = !multi;                           // with several slides, move on when the video ends
        try { v.currentTime = 0; } catch (e) {}
        // Try with sound first when the browser clearly allows it, otherwise start muted
        const canSound = !snd.chosen ? (navigator.getAutoplayPolicy && navigator.getAutoplayPolicy(v) === 'allowed') : !snd.muted;
        if (canSound) { snd.muted = false; }
        v.volume = snd.volume; v.muted = snd.muted;
        const pr = v.play();
        if (pr && pr.catch) pr.catch(() => { snd.muted = true; applySound(); const p2 = v.play(); if (p2 && p2.catch) p2.catch(() => {}); });
      });
      updateControls();
    }
    heroSlides.forEach((sl) => {
      const f = sl.querySelector('.hero__embed');
      if (f) f.addEventListener('load', () => { if (sl === heroSlides[hi]) setTimeout(applySound, 600); });
      const v = sl.querySelector('.hero__video');
      if (v) v.addEventListener('ended', () => { if (multi && sl === heroSlides[hi]) { heroNext(); heroReset(); } });
    });
    // First click / tap / key press anywhere: turn the sound on (unless the visitor already chose)
    const wake = (e) => {
      if (e && e.target && e.target.closest && e.target.closest('#heroSound')) return;   // the sound buttons handle themselves
      ['pointerdown', 'keydown', 'touchend'].forEach(ev => window.removeEventListener(ev, wake, true));
      if (!snd.chosen && snd.muted && !soundBox.hidden) { snd.muted = false; applySound(); }
    };
    if (soundBox) ['pointerdown', 'keydown', 'touchend'].forEach(ev => window.addEventListener(ev, wake, true));

    const heroGo = i => {
      heroSlides[hi].classList.remove('is-active');
      hDots[hi].classList.remove('active');
      hi = (i + heroSlides.length) % heroSlides.length;
      heroSlides[hi].classList.add('is-active');
      hDots[hi].classList.add('active');
      heroVideos();
    };
    const heroNext = () => heroGo(hi + 1);
    // How long a slide stays: pictures 6s; uploaded video until it ends; YouTube/Vimeo/Drive 30s
    const heroStart = () => {
      clearTimeout(htimer);
      if (!multi) return;
      const { video, frame } = mediaOf(hi);
      htimer = setTimeout(() => { heroNext(); heroStart(); }, video ? 120000 : (frame ? 30000 : 6000));
    };
    const heroReset = () => { heroStart(); };
    heroVideos();
    heroStart();
  }

  /* ---------- Testimonials slider ---------- */
  const track = $('#sliderTrack');
  const dotsWrap = $('#sliderDots');
  if (track && dotsWrap) {
    const slides = $$('.slide', track);
    let index = 0, timer;

    slides.forEach((_, i) => {
      const b = document.createElement('button');
      b.setAttribute('aria-label', `Go to slide ${i + 1}`);
      if (i === 0) b.classList.add('active');
      b.addEventListener('click', () => { goTo(i); reset(); });
      dotsWrap.appendChild(b);
    });
    const dots = $$('button', dotsWrap);

    const goTo = i => {
      index = (i + slides.length) % slides.length;
      track.style.transform = `translateX(-${index * 100}%)`;
      dots.forEach((d, k) => d.classList.toggle('active', k === index));
    };
    const next = () => goTo(index + 1);
    const start = () => (timer = setInterval(next, 5500));
    const reset = () => { clearInterval(timer); start(); };
    start();

    const slider = $('#slider');
    slider.addEventListener('mouseenter', () => clearInterval(timer));
    slider.addEventListener('mouseleave', start);
  }

  /* ---------- Back to top ---------- */
  const toTop = $('#toTop');
  if (toTop) {
    window.addEventListener('scroll', () => {
      toTop.classList.toggle('show', window.scrollY > 500);
    }, { passive: true });
    toTop.addEventListener('click', () =>
      window.scrollTo({ top: 0, behavior: 'smooth' }));
  }

})();

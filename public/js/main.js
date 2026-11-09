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
  const hasHero = !!$('.hero'); // only the home page has a full-screen hero
  const onScrollHeader = () => {
    if (!hasHero) { header.classList.add('scrolled'); return; } // inner pages stay solid
    header.classList.toggle('scrolled', window.scrollY > 80);
  };
  onScrollHeader();
  window.addEventListener('scroll', onScrollHeader, { passive: true });

  /* ---------- Mobile menu ---------- */
  const burger = $('#burger');
  const mobileMenu = $('#mobileMenu');
  const mobileClose = $('#mobileClose');
  const openMenu  = () => mobileMenu.classList.add('open');
  const closeMenu = () => mobileMenu.classList.remove('open');
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
    $$('a, button, .gallery__item, .service').forEach(el => {
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
      const dur = 1800;
      const start = performance.now();
      const tick = now => {
        const p = Math.min((now - start) / dur, 1);
        const eased = 1 - Math.pow(1 - p, 3);
        el.textContent = Math.floor(eased * target).toLocaleString();
        if (p < 1) requestAnimationFrame(tick);
        else el.textContent = target.toLocaleString();
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

  /* ---------- Hero slider ---------- */
  const heroSlides = $$('#heroSlides .hero__slide');
  const heroDots = $('#heroDots');
  if (heroSlides.length && heroDots) {
    let hi = 0, htimer;
    heroSlides.forEach((_, i) => {
      const b = document.createElement('button');
      b.setAttribute('aria-label', `Slide ${i + 1}`);
      if (i === 0) b.classList.add('active');
      b.addEventListener('click', () => { heroGo(i); heroReset(); });
      heroDots.appendChild(b);
    });
    const hDots = $$('button', heroDots);
    const heroGo = i => {
      heroSlides[hi].classList.remove('is-active');
      hDots[hi].classList.remove('active');
      hi = (i + heroSlides.length) % heroSlides.length;
      heroSlides[hi].classList.add('is-active');
      hDots[hi].classList.add('active');
    };
    const heroNext = () => heroGo(hi + 1);
    const heroStart = () => (htimer = setInterval(heroNext, 6000));
    const heroReset = () => { clearInterval(htimer); heroStart(); };
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

  /* ---------- Countdown (to a future date) ---------- */
  const cd = $('#countdown');
  if (cd) {
    // Target: ~90 days from load, fixed at midnight
    const target = new Date();
    target.setDate(target.getDate() + 90);
    target.setHours(0, 0, 0, 0);

    const pad = n => String(n).padStart(2, '0');
    const tick = () => {
      let diff = Math.max(0, target - new Date());
      const d = Math.floor(diff / 86400000); diff -= d * 86400000;
      const h = Math.floor(diff / 3600000);  diff -= h * 3600000;
      const m = Math.floor(diff / 60000);    diff -= m * 60000;
      const s = Math.floor(diff / 1000);
      cd.querySelector('[data-cd="days"]').textContent  = pad(d);
      cd.querySelector('[data-cd="hours"]').textContent = pad(h);
      cd.querySelector('[data-cd="mins"]').textContent  = pad(m);
      cd.querySelector('[data-cd="secs"]').textContent  = pad(s);
    };
    tick();
    setInterval(tick, 1000);
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

  /* ---------- Form feedback ---------- */
  const form = $('.rsvp__form');
  if (form) {
    form.addEventListener('submit', () => {
      const btn = form.querySelector('.btn');
      const txt = btn.textContent;
      btn.textContent = 'Thank You!';
      btn.style.background = '#8a9a7b';
      setTimeout(() => {
        btn.textContent = txt;
        btn.style.background = '';
        form.reset();
      }, 2500);
    });
  }
})();

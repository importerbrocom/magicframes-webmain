/* =========================================================
   Magic Frames — motion & graphic effects
   Plain JavaScript, no libraries. Loaded only when "Decorative motion effects" is On.
   Respectful by design:
     • does nothing at all when the visitor prefers reduced motion
     • pointer effects (tilt, magnetic buttons, cursor label) only on mouse/trackpad devices
     • animation loops pause when off-screen or when the tab is hidden
     • fewer particles on phones, low-memory devices and Data Saver
   ========================================================= */
(function () {
  'use strict';

  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  var root = document.documentElement;
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var fine = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  var conn = navigator.connection || {};
  var weak = conn.saveData || (navigator.hardwareConcurrency || 8) <= 2 || (navigator.deviceMemory || 8) <= 2;
  var phone = window.innerWidth < 700;

  root.classList.add('fx-js');

  /* ---------------------------------------------------------
     Helpers
     --------------------------------------------------------- */
  function onceVisible(els, cb, opts) {
    if (!('IntersectionObserver' in window)) { els.forEach(function (e) { cb(e, 0); }); return null; }
    var batch = [], timer = null;
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { io.unobserve(en.target); batch.push(en.target); }
      });
      if (batch.length && !timer) {
        timer = setTimeout(function () {            // stagger everything that appeared together
          batch.forEach(function (el, i) { cb(el, i); });
          batch = []; timer = null;
        }, 30);
      }
    }, opts || { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
    els.forEach(function (e) { io.observe(e); });
    return io;
  }

  var rafPending = false, scrollTasks = [];
  function onScroll(fn) { scrollTasks.push(fn); }
  function runScroll() {
    rafPending = false;
    for (var i = 0; i < scrollTasks.length; i++) scrollTasks[i]();
  }
  window.addEventListener('scroll', function () {
    if (!rafPending) { rafPending = true; requestAnimationFrame(runScroll); }
  }, { passive: true });
  window.addEventListener('resize', function () { phone = window.innerWidth < 700; if (!rafPending) { rafPending = true; requestAnimationFrame(runScroll); } }, { passive: true });

  /* ---------------------------------------------------------
     1. Scroll progress line + back-to-top ring
     --------------------------------------------------------- */
  var bar = document.createElement('div');
  bar.className = 'fx-progress';
  bar.setAttribute('aria-hidden', 'true');
  document.body.appendChild(bar);

  var ringFg = null;
  var totop = $('#toTop');
  if (totop) {
    var NS = 'http://www.w3.org/2000/svg';
    var svg = document.createElementNS(NS, 'svg');
    svg.setAttribute('class', 'fx-ring'); svg.setAttribute('viewBox', '0 0 58 58'); svg.setAttribute('aria-hidden', 'true');
    ['bg', 'fg'].forEach(function (c) {
      var ci = document.createElementNS(NS, 'circle');
      ci.setAttribute('class', c); ci.setAttribute('cx', '29'); ci.setAttribute('cy', '29'); ci.setAttribute('r', '25');
      svg.appendChild(ci);
    });
    totop.insertBefore(svg, totop.firstChild);
    ringFg = svg.querySelector('.fg');
  }

  function progress() {
    var h = root.scrollHeight - window.innerHeight;
    var p = h > 0 ? Math.min(1, Math.max(0, window.scrollY / h)) : 0;
    bar.style.transform = 'scaleX(' + p.toFixed(4) + ')';
    if (ringFg) ringFg.style.strokeDashoffset = (157 * (1 - p)).toFixed(1);
  }
  onScroll(progress); progress();
  window.addEventListener('load', progress);

  /* ---------------------------------------------------------
     2. Photo curtain reveal + camera-viewfinder corners
     --------------------------------------------------------- */
  var PHOTO = '.gallery__item, .m-item, .pf-card__media, .post__media, .story__img, .block__media, .vcard__media';
  var CORNERS = '.gallery__item, .m-item, .pf-card__media, .post__media, .story__img, .vcard__media';

  function preparePhotos(scope) {
    var fresh = $$(PHOTO, scope).filter(function (el) { return !el.hasAttribute('data-fx'); });
    fresh.forEach(function (el) {
      if (!el.querySelector('img')) return;
      el.setAttribute('data-fx', 'curtain');
      if (el.matches(CORNERS) && !el.querySelector('.fx-corners')) {
        var c = document.createElement('i'); c.className = 'fx-corners'; c.setAttribute('aria-hidden', 'true');
        el.appendChild(c);
        if (getComputedStyle(el).position === 'static') el.style.position = 'relative';
      }
    });
    var todo = fresh.filter(function (el) { return el.hasAttribute('data-fx'); });
    onceVisible(todo, function (el, i) {
      el.style.setProperty('--fx-d', Math.min(i, 8) * 0.09 + 's');
      el.classList.add('fx-in');
    });
  }
  preparePhotos(document);

  // galleries load more photos / re-filter without a page reload: treat new nodes the same way
  var dyn = $('[data-gallery]') || $('[data-videos]');
  if (dyn && 'MutationObserver' in window) {
    new MutationObserver(function (muts) {
      muts.forEach(function (m) { m.addedNodes.forEach(function (n) { if (n.nodeType === 1) preparePhotos(n.parentNode || dyn); }); });
    }).observe(dyn, { childList: true });
  }

  /* ---------------------------------------------------------
     3. Section heading ornaments
     --------------------------------------------------------- */
  onceVisible($$('.section-head'), function (el) { el.classList.add('fx-in'); }, { threshold: 0.6 });

  /* ---------------------------------------------------------
     4. Hero: letter-by-letter headline, mouse drift
     --------------------------------------------------------- */
  $$('.hero__word').forEach(function (h) {
    var text = (h.textContent || '').trim();
    if (!text || text.length > 40 || h.querySelector('.fx-c')) return;
    h.setAttribute('aria-label', text);
    h.textContent = '';
    var n = 0;
    text.split(/(\s+)/).forEach(function (part) {
      if (/^\s+$/.test(part)) { h.appendChild(document.createTextNode(' ')); return; }
      var w = document.createElement('span'); w.className = 'fx-w'; w.setAttribute('aria-hidden', 'true');
      part.split('').forEach(function (ch) {
        var c = document.createElement('span'); c.className = 'fx-c'; c.style.setProperty('--i', n++); c.textContent = ch;
        w.appendChild(c);
      });
      h.appendChild(w);
    });
  });

  var hero = $('.hero');
  if (hero && fine) {
    var tx = 0, ty = 0, raf = 0;
    hero.addEventListener('mousemove', function (e) {
      var r = hero.getBoundingClientRect();
      tx = (e.clientX - r.left) / r.width - 0.5; ty = (e.clientY - r.top) / r.height - 0.5;
      if (!raf) raf = requestAnimationFrame(function () { raf = 0; hero.style.setProperty('--px', (tx * 2).toFixed(3)); hero.style.setProperty('--py', (ty * 2).toFixed(3)); });
    });
    hero.addEventListener('mouseleave', function () { hero.style.setProperty('--px', 0); hero.style.setProperty('--py', 0); });
  }

  /* ---------------------------------------------------------
     5. Falling petals (hero) and floating light (banners, quote band)
     --------------------------------------------------------- */
  var PALETTE = ['255,255,255', '247,243,238', '201,154,134', '230,195,179', '240,216,204'];

  function Particles(host, mode) {
    var canvas = document.createElement('canvas');
    canvas.className = 'fx-canvas'; canvas.setAttribute('aria-hidden', 'true');
    host.insertBefore(canvas, host.firstChild);
    var ctx = canvas.getContext('2d');
    var w = 0, h = 0, dpr = 1, items = [], running = false, visible = true, last = 0, rafId = 0;

    function count() {
      var base = mode === 'petals' ? 8 : 22;   // petals: just a few, so the photo stays the star
      if (phone) base = Math.round(base * 0.45);
      if (weak) base = Math.round(base * 0.5);
      return Math.max(3, base);
    }
    function make(initial) {
      var big = mode === 'petals';
      return {
        x: Math.random() * w, y: initial ? Math.random() * h : (big ? -20 : h + 10),
        s: big ? 5 + Math.random() * 8 : 0.8 + Math.random() * 2,
        vy: big ? 20 + Math.random() * 28 : -(5 + Math.random() * 12),
        sway: 12 + Math.random() * 30, ph: Math.random() * 6.28, sp: 0.5 + Math.random() * 0.9,
        rot: Math.random() * 6.28, vr: (Math.random() - 0.5) * 1.4,
        flip: Math.random() * 6.28, vf: 1 + Math.random() * 2,
        col: PALETTE[(Math.random() * PALETTE.length) | 0], a: 0.35 + Math.random() * 0.5
      };
    }
    function size() {
      dpr = Math.min(window.devicePixelRatio || 1, phone ? 1.5 : 2);
      w = host.clientWidth; h = host.clientHeight;
      canvas.width = Math.round(w * dpr); canvas.height = Math.round(h * dpr);
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      var n = count();
      while (items.length < n) items.push(make(true));
      items.length = n;
      canvas.setAttribute('data-count', n);
    }
    function petal(p) {
      var s = p.s, fx = Math.cos(p.flip);
      ctx.save();
      ctx.translate(p.x, p.y); ctx.rotate(p.rot); ctx.scale(Math.max(0.25, Math.abs(fx)), 1);
      ctx.fillStyle = 'rgba(' + p.col + ',' + p.a.toFixed(2) + ')';
      ctx.beginPath();
      ctx.moveTo(0, -s);
      ctx.bezierCurveTo(s * 0.95, -s * 0.55, s * 0.75, s * 0.75, 0, s);
      ctx.bezierCurveTo(-s * 0.75, s * 0.75, -s * 0.95, -s * 0.55, 0, -s);
      ctx.fill();
      ctx.restore();
    }
    function mote(p, t) {
      var tw = 0.5 + 0.5 * Math.sin(t * p.sp * 2 + p.ph);
      var g = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, p.s * 4);
      var a = (p.a * tw).toFixed(2);
      g.addColorStop(0, 'rgba(' + p.col + ',' + a + ')'); g.addColorStop(1, 'rgba(' + p.col + ',0)');
      ctx.fillStyle = g; ctx.beginPath(); ctx.arc(p.x, p.y, p.s * 4, 0, 6.283); ctx.fill();
    }
    function frame(now) {
      if (!running) return;
      rafId = requestAnimationFrame(frame);
      var dt = Math.min(0.05, (now - last) / 1000); last = now;
      ctx.clearRect(0, 0, w, h);
      var t = now / 1000;
      for (var i = 0; i < items.length; i++) {
        var p = items[i];
        p.ph += dt * p.sp; p.y += p.vy * dt; p.x += Math.sin(p.ph) * p.sway * dt;
        if (mode === 'petals') { p.rot += p.vr * dt; p.flip += p.vf * dt; }
        if (p.y > h + 24 || p.y < -24 || p.x < -30 || p.x > w + 30) { items[i] = make(false); items[i].x = Math.random() * w; continue; }
        mode === 'petals' ? petal(p) : mote(p, t);
      }
    }
    function start() {
      if (running || !visible || document.hidden) return;
      running = true; last = performance.now(); canvas.setAttribute('data-running', '1');
      rafId = requestAnimationFrame(frame);
    }
    function stop() { running = false; cancelAnimationFrame(rafId); canvas.setAttribute('data-running', '0'); }

    // Run only while the host is on screen and the tab is visible (checked on scroll/resize: no observer races)
    function check() {
      var r = host.getBoundingClientRect();
      visible = r.bottom > 0 && r.top < window.innerHeight;
      if (visible && !document.hidden) start(); else stop();
    }

    size();
    if ('ResizeObserver' in window) new ResizeObserver(function () { size(); check(); }).observe(host); else window.addEventListener('resize', size);
    onScroll(check);
    window.addEventListener('load', check);
    document.addEventListener('visibilitychange', check);
    setTimeout(check, 1200);
    check();
  }

  if (hero) new Particles(hero, 'petals');
  $$('.page-banner, .quote').forEach(function (el) { new Particles(el, 'motes'); });

  /* ---------------------------------------------------------
     6. Category band: make sure each half is wider than the screen (seamless loop)
     --------------------------------------------------------- */
  $$('.marquee').forEach(function (m) {
    var lists = $$('.marquee__list', m);
    if (lists.length < 2) return;
    var need = window.innerWidth * 1.15, guard = 0;
    while (lists[0].scrollWidth < need && guard++ < 6) {
      [lists[0], lists[1]].forEach(function (l) {
        $$('li', l).slice(0, 12).forEach(function (li) {
          var c = li.cloneNode(true); c.setAttribute('aria-hidden', 'true');
          var a = c.querySelector('a'); if (a) a.setAttribute('tabindex', '-1');
          l.appendChild(c);
        });
      });
    }
  });

  /* ---------------------------------------------------------
     7. Parallax float for the "About" photos
     --------------------------------------------------------- */
  var floats = [[$('.story__img--one'), -30], [$('.story__img--two'), 48]].filter(function (f) { return f[0]; });
  if (floats.length) {
    onScroll(function () {
      var vh = window.innerHeight, k = phone ? 0.5 : 1;
      floats.forEach(function (f) {
        var r = f[0].parentNode.getBoundingClientRect();
        if (r.bottom < -100 || r.top > vh + 100) return;
        var p = (r.top + r.height / 2 - vh / 2) / vh;           // -1 .. 1 around the middle of the screen
        f[0].style.translate = '0 ' + (p * f[1] * k).toFixed(1) + 'px';
      });
    });
  }

  /* ---------------------------------------------------------
     8. Mouse-only effects: tilt cards, magnetic buttons, cursor label
     --------------------------------------------------------- */
  if (fine) {
    $$('.service, .pf-card, .post, .vcard').forEach(function (el) { el.classList.add('fx-tilt', 'fx-spot'); });
    document.addEventListener('mousemove', function (e) {
      var card = e.target.closest && e.target.closest('.fx-tilt');
      if (card && card.classList.contains('in')) {
        var r = card.getBoundingClientRect();
        var x = (e.clientX - r.left) / r.width, y = (e.clientY - r.top) / r.height;
        var ry = (x - 0.5) * 10, rx = (0.5 - y) * 8;
        card.style.transform = 'perspective(1000px) rotateX(' + rx.toFixed(2) + 'deg) rotateY(' + ry.toFixed(2) + 'deg) translateY(-6px)';
        card.style.setProperty('--mx', (x * 100).toFixed(1) + '%'); card.style.setProperty('--my', (y * 100).toFixed(1) + '%');
      }
    }, { passive: true });
    document.addEventListener('mouseout', function (e) {
      var card = e.target.closest && e.target.closest('.fx-tilt');
      if (card && (!e.relatedTarget || !card.contains(e.relatedTarget))) {
        card.style.transform = ''; card.style.removeProperty('--mx'); card.style.removeProperty('--my');
      }
    });

    $$('.btn, .rsvp-btn').forEach(function (b) {
      b.addEventListener('mousemove', function (e) {
        var r = b.getBoundingClientRect();
        b.style.translate = ((e.clientX - r.left - r.width / 2) * 0.22).toFixed(1) + 'px ' + ((e.clientY - r.top - r.height / 2) * 0.3).toFixed(1) + 'px';
      });
      b.addEventListener('mouseleave', function () { b.style.translate = ''; });
    });

    var follow = $('#cursorFollow');
    if (follow) {
      document.addEventListener('mouseover', function (e) {
        var t = e.target.closest && e.target.closest('[data-lightbox], .pf-card, .gallery__item, [data-video], .vcard');
        var label = !t ? '' : (t.matches('[data-video], .vcard') ? 'Play' : (t.matches('.pf-card') ? 'Open' : 'View'));
        if (follow.getAttribute('data-label') !== label) {
          follow.setAttribute('data-label', label); follow.textContent = label; follow.classList.toggle('label', !!label);
        }
      });
    }
  }
})();

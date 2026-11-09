/* =========================================================
   Bisou — page scripts for Portfolio + Gallery detail + Lightbox
   Depends on js/galleries.js (window.BISOU_GALLERIES)
   ========================================================= */
(function () {
  'use strict';
  const $  = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
  const data = window.BISOU_GALLERIES || [];

  /* ---------- Portfolio listing ---------- */
  const portfolioGrid = $('#portfolioGrid');
  if (portfolioGrid) {
    portfolioGrid.innerHTML = data.map((g, i) => `
      <a class="pf-card" href="gallery.html?id=${g.id}" data-reveal="up" style="--d:${(i % 3) * 0.1}s">
        <div class="pf-card__media">
          <img src="${g.cover}" alt="${g.name}" loading="lazy" />
          <span class="pf-card__view"><i class="fa-solid fa-arrow-right"></i></span>
        </div>
        <div class="pf-card__body">
          <span class="pf-card__cat">${g.category}</span>
          <h3 class="pf-card__title">${g.name}</h3>
          <span class="pf-card__count">${g.images.length} Photos</span>
        </div>
      </a>
    `).join('');
  }

  /* ---------- Gallery detail ---------- */
  const galleryWrap = $('#galleryDetail');
  if (galleryWrap) {
    const params = new URLSearchParams(location.search);
    const id = params.get('id');
    const g = data.find(x => x.id === id) || data[0];

    // Banner
    const banner = $('#galleryBanner');
    if (banner && g) {
      banner.style.backgroundImage = `url('${g.cover}')`;
    }
    const catEl = $('#galleryCat');
    const titleEl = $('#galleryTitle');
    const descEl = $('#galleryDesc');
    if (g) {
      if (catEl) catEl.textContent = g.category;
      if (titleEl) titleEl.textContent = g.name;
      if (descEl) descEl.textContent = g.description;
      document.title = `${g.name} — Magic Frames`;
    }

    // Image grid
    if (g) {
      galleryWrap.innerHTML = g.images.map((src, i) => `
        <a class="g-item" href="${src}" data-index="${i}" data-reveal="up" style="--d:${(i % 3) * 0.08}s">
          <img src="${src}" alt="${g.name} photo ${i + 1}" loading="lazy" />
          <span class="g-item__zoom"><i class="fa-solid fa-magnifying-glass-plus"></i></span>
        </a>
      `).join('');
    }

    // Prev / Next gallery navigation
    const idx = data.findIndex(x => x.id === (g && g.id));
    const prev = data[(idx - 1 + data.length) % data.length];
    const next = data[(idx + 1) % data.length];
    const prevLink = $('#galleryPrev');
    const nextLink = $('#galleryNext');
    if (prevLink && prev) { prevLink.href = `gallery.html?id=${prev.id}`; $('.gnav__name', prevLink).textContent = prev.name; }
    if (nextLink && next) { nextLink.href = `gallery.html?id=${next.id}`; $('.gnav__name', nextLink).textContent = next.name; }

    // ----- Lightbox -----
    const lb = $('#lightbox');
    if (lb) {
      const lbImg = $('#lbImg');
      const lbClose = $('#lbClose');
      const lbPrev = $('#lbPrev');
      const lbNext = $('#lbNext');
      const items = $$('.g-item', galleryWrap);
      let current = 0;

      const open = i => {
        current = i;
        lbImg.src = g.images[current];
        lb.classList.add('open');
        document.body.style.overflow = 'hidden';
      };
      const close = () => {
        lb.classList.remove('open');
        document.body.style.overflow = '';
      };
      const show = dir => {
        current = (current + dir + g.images.length) % g.images.length;
        lbImg.classList.remove('loaded');
        lbImg.src = g.images[current];
      };

      items.forEach((it, i) => it.addEventListener('click', e => { e.preventDefault(); open(i); }));
      lbImg.addEventListener('load', () => lbImg.classList.add('loaded'));
      lbClose.addEventListener('click', close);
      lbPrev.addEventListener('click', () => show(-1));
      lbNext.addEventListener('click', () => show(1));
      lb.addEventListener('click', e => { if (e.target === lb) close(); });
      document.addEventListener('keydown', e => {
        if (!lb.classList.contains('open')) return;
        if (e.key === 'Escape') close();
        if (e.key === 'ArrowLeft') show(-1);
        if (e.key === 'ArrowRight') show(1);
      });
    }
  }

  /* ---------- Re-trigger reveal observer for dynamically added items ---------- */
  const dynamic = $$('[data-reveal]');
  if (dynamic.length && 'IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries, obs) => {
      entries.forEach(en => {
        if (en.isIntersecting) { en.target.classList.add('in'); obs.unobserve(en.target); }
      });
    }, { threshold: 0.12 });
    dynamic.forEach(el => io.observe(el));
  }
})();

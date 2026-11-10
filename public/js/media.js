/* =========================================================
   Magic Frames — galleries & videos
   Lightbox · in-place category filtering · load more · layout toggle · video player
   Everything works through /api/v1, so uploads in the admin appear here automatically.
   ========================================================= */
(function () {
  'use strict';
  const $  = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
  const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  async function getJson(url) {
    const res = await fetch(url, { headers: { Accept: 'application/json' } });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    return res.json();
  }

  /* ---------------------------------------------------------
     LIGHTBOX
     --------------------------------------------------------- */
  const Lightbox = (() => {
    let root, img, caption, counter, btnPrev, btnNext, btnFull, items = [], index = 0, opener = null, startX = null;

    function build() {
      root = document.createElement('div');
      root.className = 'lightbox';
      root.setAttribute('role', 'dialog');
      root.setAttribute('aria-modal', 'true');
      root.setAttribute('aria-label', 'Image viewer');
      root.innerHTML = `
        <button class="lb-btn lb-close" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        <button class="lb-btn lb-full" aria-label="Full screen"><i class="fa-solid fa-expand"></i></button>
        <button class="lb-btn lb-prev" aria-label="Previous image"><i class="fa-solid fa-chevron-left"></i></button>
        <figure class="lb-figure"><img alt="" /><figcaption class="lb-caption"></figcaption></figure>
        <button class="lb-btn lb-next" aria-label="Next image"><i class="fa-solid fa-chevron-right"></i></button>
        <span class="lb-count" aria-live="polite"></span>`;
      document.body.appendChild(root);
      img = $('img', root); caption = $('.lb-caption', root); counter = $('.lb-count', root);
      btnPrev = $('.lb-prev', root); btnNext = $('.lb-next', root); btnFull = $('.lb-full', root);

      $('.lb-close', root).addEventListener('click', close);
      btnPrev.addEventListener('click', () => go(-1));
      btnNext.addEventListener('click', () => go(1));
      if (document.fullscreenEnabled) btnFull.addEventListener('click', toggleFullscreen); else btnFull.hidden = true;
      root.addEventListener('click', e => { if (e.target === root || e.target.classList.contains('lb-figure')) close(); });
      root.addEventListener('touchstart', e => { startX = e.touches[0].clientX; }, { passive: true });
      root.addEventListener('touchend', e => {
        if (startX === null) return;
        const dx = e.changedTouches[0].clientX - startX; startX = null;
        if (Math.abs(dx) > 50) go(dx < 0 ? 1 : -1);
      });
      document.addEventListener('keydown', e => {
        if (!root.classList.contains('open')) return;
        if (e.key === 'Escape') close();
        else if (e.key === 'ArrowLeft') go(-1);
        else if (e.key === 'ArrowRight') go(1);
        else if (e.key === 'Tab') { // keep focus inside the dialog
          const f = $$('button:not([hidden])', root);
          const first = f[0], last = f[f.length - 1];
          if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
          else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        }
      });
    }

    function toggleFullscreen() {
      if (document.fullscreenElement) document.exitFullscreen();
      else root.requestFullscreen && root.requestFullscreen().catch(() => {});
    }

    function show() {
      const it = items[index];
      img.classList.remove('loaded');
      void img.offsetWidth; // restart the slide-in animation even when the image is cached
      img.onload = () => img.classList.add('loaded');
      img.alt = it.alt;
      img.src = it.src;
      if (img.complete && img.naturalWidth) img.classList.add('loaded');
      const text = [it.title, it.caption].filter(Boolean);
      caption.innerHTML = text.map((t, i) => i === 0 && it.title ? `<strong>${esc(t)}</strong>` : `<span>${esc(t)}</span>`).join('');
      counter.textContent = `${index + 1} / ${items.length}`;
      const single = items.length < 2;
      btnPrev.hidden = btnNext.hidden = single;
      [index - 1, index + 1].forEach(i => { // preload neighbours
        const n = items[(i + items.length) % items.length];
        if (n && n !== it) { const p = new Image(); p.src = n.src; }
      });
    }

    function go(step) {
      if (items.length < 2) return;
      img.setAttribute('data-dir', step > 0 ? 'next' : 'prev'); // used by the (optional) slide animation
      index = (index + step + items.length) % items.length;
      show();
    }

    function open(list, i, trigger) {
      if (!root) build();
      items = list; index = i; opener = trigger;
      img.removeAttribute('data-dir');
      show();
      root.classList.add('open');
      document.body.style.overflow = 'hidden';
      $('.lb-close', root).focus();
    }

    function close() {
      if (document.fullscreenElement) document.exitFullscreen();
      root.classList.remove('open');
      document.body.style.overflow = '';
      img.removeAttribute('src');
      if (opener) opener.focus();
    }

    return { open };
  })();

  function collect(container) {
    return $$('a[data-lightbox]', container).map(a => ({
      src: a.dataset.full || a.href,
      title: a.dataset.title || '',
      caption: a.dataset.caption || '',
      alt: $('img', a)?.alt || '',
    }));
  }

  /* ---------------------------------------------------------
     GALLERY (listing + category pages)
     --------------------------------------------------------- */
  function itemHtml(img) {
    const ratio = img.width && img.height ? `${img.width}/${img.height}` : '4/3';
    const alt = img.alt || (img.category && img.category.name) || 'Photograph';
    return `<a class="m-item" href="${esc(img.large_url)}" data-lightbox data-full="${esc(img.large_url)}" data-title="${esc(img.title)}" data-caption="${esc(img.caption)}" style="aspect-ratio:${ratio}">
      <img src="${esc(img.thumb_url)}" ${img.srcset ? `srcset="${esc(img.srcset)}" sizes="(max-width:600px) 50vw, (max-width:1000px) 33vw, 25vw"` : ''} alt="${esc(alt)}" ${img.width ? `width="${img.width}" height="${img.height}"` : ''} loading="lazy" decoding="async" />
      <span class="m-item__cap">${img.title ? esc(img.title) : '<i class="fa-solid fa-magnifying-glass-plus"></i>'}</span></a>`;
  }

  function videoHtml(v) {
    return `<a class="vcard" href="/videos?play=${esc(v.slug)}" data-video data-slug="${esc(v.slug)}" data-title="${esc(v.title)}" data-desc="${esc(v.description)}" data-embed="${esc(v.embed_url)}" data-file="${esc(v.file_url)}" data-poster="${esc(v.thumbnail_url)}">
      <span class="vcard__media">${v.thumbnail_url ? `<img src="${esc(v.thumbnail_url)}" alt="" loading="lazy" decoding="async" />` : ''}<span class="vcard__play"><i class="fa-solid fa-play"></i></span></span>
      <span class="vcard__title">${esc(v.title)}</span>
      ${v.category ? `<span class="vcard__cat">${esc(v.category.name)}</span>` : ''}
      ${v.description ? `<span class="vcard__desc">${esc(v.description.length > 110 ? v.description.slice(0, 107) + '…' : v.description)}</span>` : ''}</a>`;
  }

  /** Shared "filter + load more" behaviour for gallery and video grids. */
  function paged(box, render, basePath) {
    const more = $('.g-more'), empty = $('.g-empty');
    let page = +box.dataset.page || 1, last = +box.dataset.last || 1, category = box.dataset.category || '', busy = false;

    async function load(p, replace) {
      if (busy) return; busy = true;
      if (more) { more.disabled = true; more.textContent = 'Loading…'; }
      try {
        const q = new URLSearchParams({ page: p, per_page: box.dataset.perPage || 12 });
        if (category) q.set('category', category);
        const data = await getJson(`${box.dataset.endpoint}?${q}`);
        const html = data.data.map(render).join('');
        if (replace) box.innerHTML = html; else box.insertAdjacentHTML('beforeend', html);
        page = data.meta.current_page; last = data.meta.last_page;
        if (more) more.hidden = page >= last;
        if (empty) { empty.hidden = box.children.length > 0; empty.textContent = empty.dataset.msg || empty.textContent; }
      } catch (e) {
        if (empty) { empty.hidden = false; empty.textContent = 'Sorry, this could not be loaded. Please try again.'; }
      } finally {
        busy = false;
        if (more) { more.disabled = false; more.textContent = 'Load more'; }
      }
    }

    if (more) more.addEventListener('click', () => load(page + 1, false));

    $$('.filter[data-filter]').forEach(f => f.addEventListener('click', e => {
      e.preventDefault();
      $$('.filter').forEach(x => x.classList.toggle('active', x === f));
      category = f.dataset.filter;
      history.replaceState(null, '', category ? `${basePath}?category=${encodeURIComponent(category)}` : basePath);
      load(1, true);
    }));
  }

  const galleryBox = $('[data-gallery]');
  if (galleryBox) {
    paged(galleryBox, itemHtml, '/gallery');
    galleryBox.dataset.perPage = galleryBox.dataset.perPage || 24;
    galleryBox.addEventListener('click', e => {
      const a = e.target.closest('a[data-lightbox]');
      if (!a || e.metaKey || e.ctrlKey || e.shiftKey) return;
      e.preventDefault();
      const list = $$('a[data-lightbox]', galleryBox);
      Lightbox.open(collect(galleryBox), list.indexOf(a), a);
    });

    // masonry / grid layout switch (remembered)
    let saved = null;
    try { saved = localStorage.getItem('mf-layout'); } catch (e) { /* storage blocked (private mode / privacy settings) */ }
    const applyLayout = mode => {
      galleryBox.classList.toggle('is-grid', mode === 'grid');
      $$('.layout-toggle button').forEach(b => b.classList.toggle('active', b.dataset.layout === mode));
    };
    if (saved === 'grid') applyLayout('grid');
    $$('.layout-toggle button').forEach(b => b.addEventListener('click', () => {
      applyLayout(b.dataset.layout);
      try { localStorage.setItem('mf-layout', b.dataset.layout); } catch (e) { /* private mode */ }
    }));
  }

  /* ---------------------------------------------------------
     VIDEO PLAYER
     --------------------------------------------------------- */
  const SAFE_EMBED = /^https:\/\/(www\.youtube-nocookie\.com\/embed\/|player\.vimeo\.com\/video\/)[\w-]+/;
  const Player = (() => {
    let root, stage, title, desc, opener;
    function build() {
      root = document.createElement('div');
      root.className = 'vmodal';
      root.setAttribute('role', 'dialog'); root.setAttribute('aria-modal', 'true'); root.setAttribute('aria-label', 'Video player');
      root.innerHTML = `<button class="lb-btn lb-close" aria-label="Close video"><i class="fa-solid fa-xmark"></i></button>
        <div class="vmodal__box"><div class="vmodal__stage"></div><div class="vmodal__info"><h3></h3><p></p></div></div>`;
      document.body.appendChild(root);
      stage = $('.vmodal__stage', root); title = $('h3', root); desc = $('p', root);
      $('.lb-close', root).addEventListener('click', close);
      root.addEventListener('click', e => { if (e.target === root) close(); });
      document.addEventListener('keydown', e => { if (e.key === 'Escape' && root.classList.contains('open')) close(); });
    }
    function open(d, trigger) {
      if (!root) build();
      opener = trigger || null;
      stage.innerHTML = '';
      if (d.embed && SAFE_EMBED.test(d.embed)) {
        const f = document.createElement('iframe');
        f.src = d.embed + (d.embed.includes('?') ? '&' : '?') + 'autoplay=1';
        f.allow = 'autoplay; fullscreen; picture-in-picture; encrypted-media';
        f.allowFullscreen = true; f.title = d.title || 'Video';
        stage.appendChild(f);
      } else if (d.file) {
        const v = document.createElement('video');
        v.controls = true; v.autoplay = true; v.playsInline = true; v.preload = 'metadata';
        if (d.poster) v.poster = d.poster;
        v.src = d.file; // only requested now — never preloaded with the page
        stage.appendChild(v);
      } else return;
      title.textContent = d.title || ''; desc.textContent = d.desc || '';
      root.classList.add('open');
      document.body.style.overflow = 'hidden';
      $('.lb-close', root).focus();
    }
    function close() {
      root.classList.remove('open');
      stage.innerHTML = ''; // stops playback and releases the file
      document.body.style.overflow = '';
      if (location.search.includes('play=')) {
        const u = new URL(location.href); u.searchParams.delete('play'); history.replaceState(null, '', u.pathname + u.search);
      }
      if (opener) opener.focus();
    }
    return { open };
  })();

  const videoBox = $('[data-videos]');
  if (videoBox) {
    paged(videoBox, videoHtml, '/videos');
    videoBox.addEventListener('click', e => {
      const a = e.target.closest('a[data-video]');
      if (!a || e.metaKey || e.ctrlKey || e.shiftKey) return;
      e.preventDefault();
      Player.open({ embed: a.dataset.embed, file: a.dataset.file, poster: a.dataset.poster, title: a.dataset.title, desc: a.dataset.desc }, a);
    });

    const play = videoBox.dataset.play;
    if (play) {
      const card = $(`a[data-video][data-slug="${CSS.escape(play)}"]`, videoBox);
      if (card) card.click();
      else getJson(`/api/v1/videos/${encodeURIComponent(play)}`).then(r => {
        const v = r.data;
        Player.open({ embed: v.embed_url, file: v.file_url, poster: v.thumbnail_url, title: v.title, desc: v.description });
      }).catch(() => {});
    }
  }
})();

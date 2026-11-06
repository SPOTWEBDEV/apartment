// Room galleries, the shared full-screen viewer, and the filterable photo grid on the Gallery page.
(() => {
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const dlg = $('#lightbox');

  // ----- full-screen viewer -----
  let list = [], idx = 0, lastFocus = null;
  const img = dlg && $('[data-lb-img]', dlg), cap = dlg && $('[data-lb-cap]', dlg), count = dlg && $('[data-lb-count]', dlg);
  function paint() {
    const it = list[idx];
    img.src = it.src; img.alt = it.caption || ''; cap.textContent = it.caption || '';
    count.textContent = list.length > 1 ? `${idx + 1} / ${list.length}` : '';
    $$('[data-lb-prev],[data-lb-next]', dlg).forEach(b => b.classList.toggle('hidden', list.length < 2));
  }
  function openViewer(items, i) { if (!dlg || !items.length) return; list = items; idx = i; lastFocus = document.activeElement; paint(); dlg.showModal(); }
  const step = n => { idx = (idx + n + list.length) % list.length; paint(); };
  if (dlg) {
    $('[data-lb-prev]', dlg).addEventListener('click', () => step(-1));
    $('[data-lb-next]', dlg).addEventListener('click', () => step(1));
    $('[data-lb-close]', dlg).addEventListener('click', () => dlg.close());
    $('[data-lb-wrap]', dlg).addEventListener('click', e => { if (e.target === e.currentTarget) dlg.close(); });
    dlg.addEventListener('keydown', e => { if (e.key === 'ArrowLeft') step(-1); if (e.key === 'ArrowRight') step(1); });
    dlg.addEventListener('close', () => lastFocus?.focus?.());
    let x0 = null;
    dlg.addEventListener('touchstart', e => { x0 = e.touches[0].clientX; }, { passive: true });
    dlg.addEventListener('touchend', e => { if (x0 === null) return; const dx = e.changedTouches[0].clientX - x0; if (Math.abs(dx) > 50) step(dx < 0 ? 1 : -1); x0 = null; });
  }

  // ----- room galleries -----
  $$('[data-gallery]').forEach(el => {
    const data = $('[data-images]', el); if (!data) return;
    const imgs = JSON.parse(data.textContent), name = el.dataset.name || '';
    let i = 0;
    const main = $('[data-main]', el), capEl = $('[data-cap]', el), countEl = $('[data-count]', el), thumbs = $$('[data-thumb]', el);
    const show = n => {
      i = (n + imgs.length) % imgs.length;
      main.src = imgs[i].src; main.alt = imgs[i].caption || name;
      if (capEl) { capEl.textContent = imgs[i].caption || ''; capEl.classList.toggle('hidden', !imgs[i].caption); }
      if (countEl) countEl.textContent = `${i + 1} / ${imgs.length}`;
      thumbs.forEach((t, k) => t.setAttribute('aria-current', k === i ? 'true' : 'false'));
      thumbs[i]?.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    };
    $('[data-prev]', el)?.addEventListener('click', () => show(i - 1));
    $('[data-next]', el)?.addEventListener('click', () => show(i + 1));
    thumbs.forEach(t => t.addEventListener('click', () => show(+t.dataset.thumb)));
    $('[data-open]', el).addEventListener('click', () => openViewer(imgs, i));
  });

  // ----- photo grid with category filter -----
  $$('[data-lightbox-group]').forEach(group => {
    group.addEventListener('click', e => {
      const a = e.target.closest('[data-lb]'); if (!a) return;
      e.preventDefault();
      const visible = $$('[data-lb]', group).filter(x => !x.closest('[hidden]'));
      openViewer(visible.map(x => ({ src: x.getAttribute('href'), caption: x.dataset.caption || '' })), visible.indexOf(a));
    });
  });
  $$('[data-filter]').forEach(btn => btn.addEventListener('click', () => {
    const cat = btn.dataset.filter;
    $$('[data-filter]').forEach(b => b.setAttribute('aria-pressed', b === btn ? 'true' : 'false'));
    $$('[data-cat]').forEach(it => { it.hidden = cat !== 'all' && it.dataset.cat !== cat; });
    $('#gridEmpty')?.classList.toggle('hidden', $$('[data-cat]').some(it => !it.hidden));
  }));
})();

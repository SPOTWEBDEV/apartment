// Booking flow: dates -> room -> details -> done. Talks to api/availability.php and api/booking.php.
(() => {
  if (!window.CL) return;
  const { api, esc, peso, iso, reduce } = window.CL;
  const $ = (s, r = document) => r.querySelector(s);
  const flow = $('#flow'); if (!flow) return;

  const panels = [...flow.querySelectorAll('[data-panel]')];
  const stepEls = [...flow.querySelectorAll('[data-step]')];
  const dateForm = $('#dateForm'), dateMsg = $('#dateMsg');
  const guestForm = $('#guestForm'), guestMsg = $('#guestMsg');
  const params = new URLSearchParams(location.search);
  const state = { q: null, nights: 0, rooms: [], room: null, preferred: params.get('room'), discount: null };
  const fmt = d => new Date(d + 'T00:00:00').toLocaleDateString('en-PH', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });

  function go(n, { scroll = true } = {}) {
    panels.forEach(p => p.hidden = +p.dataset.panel !== n);
    stepEls.forEach(s => {
      const i = +s.dataset.step;
      s.classList.toggle('is-current', i === n);
      s.classList.toggle('is-done', i < n);
      i === n ? s.setAttribute('aria-current', 'step') : s.removeAttribute('aria-current');
    });
    const panel = panels[n - 1], h = $('h2', panel);
    if (scroll) {
      flow.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
      if (h) { h.tabIndex = -1; h.focus({ preventScroll: true }); }
    }
    if (window.gsap && !reduce && scroll) gsap.fromTo(panel, { y: 16, opacity: 0 }, { y: 0, opacity: 1, duration: .5, ease: 'power3.out' });
  }

  // Date inputs: no past dates, checkout after checkin
  const today = new Date();
  dateForm.checkin.min = iso(today);
  dateForm.checkout.min = iso(new Date(today.getTime() + 864e5));
  dateForm.checkin.addEventListener('change', () => {
    const next = iso(new Date(new Date(dateForm.checkin.value + 'T00:00:00').getTime() + 864e5));
    dateForm.checkout.min = next;
    if (dateForm.checkout.value && dateForm.checkout.value <= dateForm.checkin.value) dateForm.checkout.value = next;
  });

  async function search(q) {
    const btn = dateForm.querySelector('button');
    dateMsg.textContent = 'Checking availability…'; btn.disabled = true;
    try {
      const r = await api('availability.php', q);
      if (!r.ok) { dateMsg.textContent = r.error || 'Could not check availability.'; go(1, { scroll: false }); return false; }
      state.q = q; state.nights = r.nights; state.rooms = r.rooms;
      if (!r.rooms.length) { dateMsg.textContent = 'No rooms fit those dates and guest count. Try fewer guests or different dates.'; go(1, { scroll: false }); return false; }
      dateMsg.textContent = '';
      renderRooms();
      return true;
    } catch { dateMsg.textContent = 'Connection problem. Check your internet and try again.'; return false; }
    finally { btn.disabled = false; }
  }

  function renderRooms() {
    const { q, nights } = state;
    $('#pickSummary').textContent = `${fmt(q.checkin)} to ${fmt(q.checkout)}, ${nights} ${nights === 1 ? 'night' : 'nights'}, ${q.guests} ${q.guests === 1 ? 'guest' : 'guests'}`;
    $('#roomResults').innerHTML = state.rooms.map(x => `
      <article class="grid overflow-hidden rounded-2xl border ${x.id === state.preferred ? 'border-bronze ring-2 ring-bronze/30' : 'border-lagoon/10'} sm:grid-cols-[13rem_1fr]">
        <div class="ph min-h-36 bg-cover bg-center" style="background-image:url('assets/img/${esc(x.img)}'),linear-gradient(135deg,#14405F,#2E6C8A 55%,#F2A65A)"></div>
        <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h3 class="font-display text-xl">${esc(x.name)}</h3>
            <p class="mt-1 text-sm text-lagoon/70">${esc(x.blurb)}</p>
            <p class="mt-2 text-sm text-lagoon/60">Sleeps up to ${x.guests}. ${peso(x.nightly)} a night.${x.left <= 2 ? ` <span class="font-medium text-bronze">Only ${x.left} left.</span>` : ''}</p>
          </div>
          <div class="shrink-0 sm:text-right">
            <p class="font-display text-2xl">${peso(x.total)}</p>
            <p class="text-xs text-lagoon/60">total for ${nights} ${nights === 1 ? 'night' : 'nights'}</p>
            <button type="button" data-select="${esc(x.id)}" class="mt-3 rounded-full bg-bronze px-5 py-2.5 text-sm font-semibold text-white hover:bg-lagoon">Select</button>
          </div>
        </div>
      </article>`).join('');
  }

  function selectRoom(id, opts) {
    const room = state.rooms.find(x => x.id === id); if (!room) return;
    state.room = room;
    const { q, nights } = state;
    state.discount = null; $('#codeMsg').textContent = ''; guestForm.code.value = '';
    renderSummary();
    go(3, opts);
  }

  function renderSummary() {
    const { q, nights, room, discount } = state;
    const total = room.total - (discount ? discount.amount : 0);
    $('#summary').innerHTML = `
      <h3 class="font-display text-xl">${esc(room.name)}</h3>
      <dl class="mt-4 space-y-2 text-sm">
        <div class="flex justify-between gap-4"><dt class="text-lagoon/60">Check-in</dt><dd class="font-medium">${fmt(q.checkin)}</dd></div>
        <div class="flex justify-between gap-4"><dt class="text-lagoon/60">Check-out</dt><dd class="font-medium">${fmt(q.checkout)}</dd></div>
        <div class="flex justify-between gap-4"><dt class="text-lagoon/60">Guests</dt><dd class="font-medium">${q.guests}</dd></div>
        <div class="flex justify-between gap-4"><dt class="text-lagoon/60">${peso(room.nightly)} x ${nights} ${nights === 1 ? 'night' : 'nights'}</dt><dd class="font-medium">${peso(room.total)}</dd></div>
        ${discount ? `<div class="flex justify-between gap-4 text-emerald-700"><dt>Code ${esc(discount.code)}</dt><dd class="font-medium">-${peso(discount.amount)}</dd></div>` : ''}
      </dl>
      <p class="mt-5 flex items-baseline justify-between border-t border-lagoon/15 pt-4"><span>Estimated total</span><strong class="font-display text-2xl">${peso(total)}</strong></p>
      <p class="mt-3 text-xs text-lagoon/60">Final rates are confirmed by the reservations team. No payment is taken now.</p>`;
  }

  $('#applyCode').addEventListener('click', async () => {
    const msg = $('#codeMsg'), code = guestForm.code.value.trim();
    msg.className = 'mt-1 min-h-5 text-sm';
    if (!code) { state.discount = null; renderSummary(); msg.textContent = ''; return; }
    msg.textContent = 'Checking…';
    try {
      const r = await api('discount.php', { ...state.q, room: state.room.id, code });
      if (r.ok) { state.discount = { code: r.code, amount: r.amount }; msg.classList.add('text-emerald-700'); msg.textContent = `Code applied. You save ${peso(r.amount)}.`; }
      else { state.discount = null; msg.classList.add('text-red-700'); msg.textContent = r.error || 'That code could not be applied.'; }
    } catch { msg.classList.add('text-red-700'); msg.textContent = 'Connection problem. Try again.'; }
    renderSummary();
  });

  // Events
  dateForm.addEventListener('submit', async e => {
    e.preventDefault();
    const q = { checkin: dateForm.checkin.value, checkout: dateForm.checkout.value, guests: +dateForm.guests.value };
    if (await search(q)) go(2);
  });
  flow.addEventListener('click', e => {
    const sel = e.target.closest('[data-select]'); if (sel) selectRoom(sel.dataset.select);
    const back = e.target.closest('[data-back]'); if (back) go(+back.dataset.back);
  });
  document.addEventListener('click', e => {
    const pick = e.target.closest('[data-pick]'); if (!pick) return;
    state.preferred = pick.dataset.pick;
    if (state.q) { renderRooms(); go(2); }
    else { go(1); dateForm.checkin.focus({ preventScroll: true }); dateMsg.className = 'mt-4 min-h-6 text-sm text-lagoon/70'; dateMsg.textContent = 'Choose your dates and we will check this room.'; }
  });

  guestForm.addEventListener('submit', async e => {
    e.preventDefault();
    guestForm.querySelectorAll('.err').forEach(p => p.textContent = '');
    guestMsg.textContent = 'Sending…';
    const btn = guestForm.querySelector('button'); btn.disabled = true;
    try {
      const r = await api('booking.php', { ...state.q, room: state.room.id, ...Object.fromEntries(new FormData(guestForm)), code: state.discount ? state.discount.code : '' });
      if (r.ok) { $('#refCode').textContent = r.ref; $('#receiptLink').href = r.receipt; $('#payLink').href = r.pay; state.summary = r.summary; $('#imagePreview').classList.add('hidden'); guestMsg.textContent = ''; guestForm.reset(); go(4); loadCrypto(r.ref); return; }
      if (r.unavailable) { guestMsg.textContent = r.error; if (await search(state.q)) go(2); return; }
      for (const [k, m] of Object.entries(r.errors || {})) { const p = guestForm.querySelector(`.err[data-for="${k}"]`); if (p) p.textContent = m; }
      guestMsg.textContent = r.errors ? 'Fix the highlighted fields and send again.' : (r.error || 'Could not send. Try again.');
    } catch { guestMsg.textContent = 'Connection problem. Your request was not sent.'; }
    finally { btn.disabled = false; }
  });

  $('#receiptImage').addEventListener('click', async e => {
    const btn = e.currentTarget; if (!state.summary || !window.CLReceipt) return;
    btn.disabled = true; const label = btn.textContent; btn.textContent = 'Preparing image…';
    try {
      const blob = await CLReceipt.render(state.summary, window.SITE);
      const url = URL.createObjectURL(blob);
      $('#receiptPreview').src = url; $('#imagePreview').classList.remove('hidden');
      const a = document.createElement('a'); a.href = url; a.download = `receipt-${state.summary.ref}.png`; document.body.append(a); a.click(); a.remove();
    } catch { alert('The image could not be created. Please use the PDF receipt instead.'); }
    btn.disabled = false; btn.textContent = label;
  });

  async function loadCrypto(ref) {
    try {
      const r = await api('crypto.php'); if (!r.ok || !r.wallets.length) return;
      $('#cryptoRef').textContent = ref;
      $('#cryptoList').innerHTML = r.wallets.map(w => `<li class="rounded-xl bg-white p-4">
        <p class="text-sm font-semibold">${esc(w.coin)} on ${esc(w.network)}${w.label ? ` <span class="font-normal text-lagoon/60">· ${esc(w.label)}</span>` : ''}</p>
        <div class="mt-2 flex items-start justify-between gap-3"><code class="break-all text-xs">${esc(w.address)}</code>
        <button type="button" data-copy="${esc(w.address)}" class="shrink-0 rounded-lg border border-lagoon/20 px-3 py-1 text-xs font-semibold hover:bg-lagoon hover:text-white">Copy</button></div></li>`).join('');
      $('#cryptoBox').classList.remove('hidden');
    } catch { /* crypto details are optional */ }
  }
  document.addEventListener('click', async e => {
    const c = e.target.closest('[data-copy]'); if (!c) return;
    try { await navigator.clipboard.writeText(c.dataset.copy); c.textContent = 'Copied'; setTimeout(() => c.textContent = 'Copy', 1500); } catch { c.textContent = 'Select and copy'; }
  });

  // Arriving from the home page with dates (and maybe a room) in the URL
  if (params.get('checkin') && params.get('checkout')) {
    dateForm.checkin.value = params.get('checkin');
    dateForm.checkout.value = params.get('checkout');
    dateForm.guests.value = params.get('guests') || 2;
    (async () => {
      const q = { checkin: dateForm.checkin.value, checkout: dateForm.checkout.value, guests: +dateForm.guests.value };
      if (await search(q)) {
        if (state.preferred && state.rooms.some(x => x.id === state.preferred)) selectRoom(state.preferred, { scroll: false });
        else go(2, { scroll: false });
      }
    })();
  }
})();

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
  const state = { q: null, nights: 0, rooms: [], room: null, preferred: params.get('room') };
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
    $('#summary').innerHTML = `
      <h3 class="font-display text-xl">${esc(room.name)}</h3>
      <dl class="mt-4 space-y-2 text-sm">
        <div class="flex justify-between gap-4"><dt class="text-lagoon/60">Check-in</dt><dd class="font-medium">${fmt(q.checkin)}</dd></div>
        <div class="flex justify-between gap-4"><dt class="text-lagoon/60">Check-out</dt><dd class="font-medium">${fmt(q.checkout)}</dd></div>
        <div class="flex justify-between gap-4"><dt class="text-lagoon/60">Guests</dt><dd class="font-medium">${q.guests}</dd></div>
        <div class="flex justify-between gap-4"><dt class="text-lagoon/60">${peso(room.nightly)} x ${nights} ${nights === 1 ? 'night' : 'nights'}</dt><dd class="font-medium">${peso(room.total)}</dd></div>
      </dl>
      <p class="mt-5 flex items-baseline justify-between border-t border-lagoon/15 pt-4"><span>Estimated total</span><strong class="font-display text-2xl">${peso(room.total)}</strong></p>
      <p class="mt-3 text-xs text-lagoon/60">Final rates are confirmed by the reservations team. No payment is taken now.</p>`;
    go(3, opts);
  }

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
      const r = await api('booking.php', { ...state.q, room: state.room.id, ...Object.fromEntries(new FormData(guestForm)) });
      if (r.ok) { $('#refCode').textContent = r.ref; guestMsg.textContent = ''; guestForm.reset(); go(4); return; }
      if (r.unavailable) { guestMsg.textContent = r.error; if (await search(state.q)) go(2); return; }
      for (const [k, m] of Object.entries(r.errors || {})) { const p = guestForm.querySelector(`.err[data-for="${k}"]`); if (p) p.textContent = m; }
      guestMsg.textContent = r.errors ? 'Fix the highlighted fields and send again.' : (r.error || 'Could not send. Try again.');
    } catch { guestMsg.textContent = 'Connection problem. Your request was not sent.'; }
    finally { btn.disabled = false; }
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

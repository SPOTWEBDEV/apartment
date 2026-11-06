(() => {
  const $ = (s, r = document) => r.querySelector(s);
  const peso = n => '₱' + Number(n).toLocaleString('en-PH');
  const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

  // JSON in, JSON out. Resolves with the parsed body even on 4xx so callers can show errors.
  async function api(path, payload) {
    const res = await fetch(`api/${path}`, {
      method: payload ? 'POST' : 'GET',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: payload ? JSON.stringify(payload) : undefined
    });
    const data = await res.json().catch(() => ({ ok: false, error: 'Unexpected server response.' }));
    return { status: res.status, ...data };
  }

  // Local-date ISO string (toISOString alone returns UTC, which is wrong for Manila mornings)
  const iso = d => new Date(d.getTime() - d.getTimezoneOffset() * 6e4).toISOString().slice(0, 10);
  window.CL = { api, esc, peso, iso, reduce };

  // Header: solid after scrolling (always solid on inner pages)
  const nav = $('#nav');
  const onScroll = () => nav.classList.toggle('is-solid', nav.dataset.solid === '1' || scrollY > 40);
  onScroll(); addEventListener('scroll', onScroll, { passive: true });

  // Mobile menu
  const btn = $('#menuBtn'), menu = $('#mobileMenu');
  btn.addEventListener('click', () => {
    const open = menu.classList.toggle('hidden') === false;
    btn.setAttribute('aria-expanded', open);
  });
  menu.addEventListener('click', e => { if (e.target.closest('a')) { menu.classList.add('hidden'); btn.setAttribute('aria-expanded', false); } });

  // Availability (home only)
  const book = $('#book');
  if (book) {
    const bookMsg = $('#bookMsg');
    book.checkin.min = iso(new Date());
    book.checkout.min = iso(new Date(Date.now() + 864e5));
    book.checkin.addEventListener('change', () => {
      const next = new Date(new Date(book.checkin.value).getTime() + 864e5);
      book.checkout.min = iso(next);
      if (book.checkout.value && book.checkout.value <= book.checkin.value) book.checkout.value = iso(next);
    });

    book.addEventListener('submit', async e => {
      e.preventDefault();
      const submit = book.querySelector('button');
      bookMsg.textContent = 'Checking availability…';
      submit.disabled = true;
      const q = { checkin: book.checkin.value, checkout: book.checkout.value, guests: +book.guests.value };
      try {
        const r = await api('availability.php', q);
        if (!r.ok) { bookMsg.textContent = r.error || 'Could not check availability.'; return; }
        bookMsg.textContent = '';
        renderResults(r, q);
      } catch { bookMsg.textContent = 'Connection problem. Check your internet and try again.'; }
      finally { submit.disabled = false; }
    });

    function renderResults(r, q) {
      const sec = $('#results');
      $('#resultsTitle').textContent = r.rooms.length
        ? `${r.rooms.length} ${r.rooms.length === 1 ? 'room is' : 'rooms are'} available for ${r.nights} ${r.nights === 1 ? 'night' : 'nights'}`
        : 'No rooms fit those dates and guest count. Try fewer guests or different dates.';
      $('#resultsList').innerHTML = r.rooms.map(x => {
        const href = 'rooms.php?' + new URLSearchParams({ room: x.id, ...q }) + '#booking';
        return `<article class="rounded-3xl bg-white p-6 shadow-sm">
          <h3 class="font-display text-xl">${esc(x.name)}</h3>
          <p class="mt-1 text-sm text-lagoon/70">Sleeps up to ${x.guests}. ${peso(x.nightly)} a night.</p>
          <p class="mt-4 font-display text-2xl">${peso(x.total)} <span class="font-sans text-sm text-lagoon/60">total</span></p>
          <a href="${esc(href)}" class="mt-4 inline-block rounded-full bg-bronze px-5 py-2.5 text-sm font-semibold text-white hover:bg-lagoon">Book this room</a>
        </article>`;
      }).join('');
      sec.classList.remove('hidden');
      if (window.gsap && !reduce) gsap.fromTo('#resultsList > *', { y: 24, opacity: 0 }, { y: 0, opacity: 1, duration: .6, stagger: .1, ease: 'power3.out' });
      sec.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
    }
  }

  // Reviews carousel (home only)
  const list = $('#reviewList');
  if (list) {
    (async () => {
      try {
        const r = await api('reviews.php');
        if (!r.ok) throw 0;
        const avg = r.reviews.reduce((t, v) => t + v.rating, 0) / r.reviews.length;
        const label = $('#revAvg'); if (label) label.textContent = `${avg.toFixed(1)} out of 5 from ${r.reviews.length} guest reviews`;
        list.innerHTML = r.reviews.map(v => `
          <figure class="flex min-w-[85%] snap-start flex-col rounded-3xl bg-white p-7 shadow-sm md:min-w-[calc(33.333%-1rem)]">
            <div class="flex items-center justify-between gap-3"><p class="text-bronze" role="img" aria-label="${v.rating} out of 5 stars">${'★'.repeat(v.rating)}${'☆'.repeat(5 - v.rating)}</p><p class="text-sm text-lagoon/60">${esc(v.date)}</p></div>
            <h3 class="mt-2 font-display text-xl">${esc(v.title)}</h3>
            <blockquote class="mt-3 flex-1 text-lagoon/80">${esc(v.text)}</blockquote>
            <figcaption class="mt-5 text-sm"><span class="font-semibold">${esc(v.name)}</span><span class="text-lagoon/60">, ${esc(v.stay)}</span></figcaption>
          </figure>`).join('');
      } catch { list.innerHTML = '<p class="text-lagoon/70">Reviews could not be loaded right now.</p>'; }
    })();
    const step = dir => list.scrollBy({ left: dir * ((list.firstElementChild?.offsetWidth || 320) + 24), behavior: reduce ? 'auto' : 'smooth' });
    $('#revPrev').addEventListener('click', () => step(-1));
    $('#revNext').addEventListener('click', () => step(1));
  }

  // Inquiry form (contact page only)
  const form = $('#inquiryForm');
  if (form) {
    const formMsg = $('#formMsg');
    const typeSel = $('#type'), extra = $('#extraFields'), timeWrap = $('#timeWrap');
    const syncType = () => { extra.classList.toggle('hidden', !['table', 'wedding', 'corporate'].includes(typeSel.value)); timeWrap.classList.toggle('hidden', typeSel.value !== 'table'); };
    typeSel.addEventListener('change', syncType); syncType();
    form.addEventListener('submit', async e => {
      e.preventDefault();
      form.querySelectorAll('.err').forEach(p => p.textContent = '');
      formMsg.className = 'min-h-6 text-sm'; formMsg.textContent = 'Sending…';
      const b = form.querySelector('button'); b.disabled = true;
      try {
        const r = await api('inquiry.php', Object.fromEntries(new FormData(form)));
        if (r.ok) {
          form.reset();
          formMsg.classList.add('text-emerald-700');
          formMsg.textContent = 'Inquiry sent. We will reply by email, usually within a day.';
        } else {
          for (const [k, m] of Object.entries(r.errors || {})) { const p = form.querySelector(`.err[data-for="${k}"]`); if (p) p.textContent = m; }
          formMsg.classList.add('text-red-700');
          formMsg.textContent = r.errors ? 'Fix the highlighted fields and send again.' : (r.error || 'Could not send. Try again.');
        }
      } catch { formMsg.classList.add('text-red-700'); formMsg.textContent = 'Connection problem. Your message was not sent.'; }
      finally { b.disabled = false; }
    });
  }
})();

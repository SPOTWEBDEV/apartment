(() => {
  const { $, esc, money, dstr, api, toast, iso } = A;
  const CW = 44, LEFT = 190;
  const COLORS = { pending: 'bg-amber-100 text-amber-900 ring-amber-300', confirmed: 'bg-sky-100 text-sky-900 ring-sky-300', checked_in: 'bg-teal-100 text-teal-900 ring-teal-300', completed: 'bg-emerald-100 text-emerald-900 ring-emerald-300' };
  const LABEL = { pending: 'Pending', confirmed: 'Confirmed', checked_in: 'Checked in', completed: 'Completed' };
  const num = s => Date.UTC(+s.slice(0, 4), +s.slice(5, 7) - 1, +s.slice(8, 10)) / 864e5;
  const strOf = n => new Date(n * 864e5).toISOString().slice(0, 10);
  const today = iso();
  let from = strOf(num(today) - 3), days = 30;

  $('#legend').innerHTML = Object.keys(LABEL).map(k => `<li class="flex items-center gap-1.5"><i class="inline-block h-3 w-3 rounded ring-1 ${COLORS[k]}"></i>${LABEL[k]}</li>`).join('');

  async function load() {
    const r = await api('calendar', 'range', { from, days });
    if (!r.ok) { toast(r.error, 'err'); return; }
    stats(r); grid(r);
  }

  function stats(r) {
    const t = r.today, card = (l, v, s) => `<div class="card p-5"><p class="text-sm font-medium text-slate-500">${l}</p><p class="mt-1 text-2xl font-bold">${v}</p><p class="text-sm text-slate-400">${s}</p></div>`;
    $('#stats').innerHTML = card('Occupied tonight', `${t.occupied} / ${r.capacity}`, 'Rooms with a guest tonight') + card('Arriving today', t.arrivals, 'Pending or confirmed') + card('Leaving today', t.departures, 'Confirmed or checked in');
  }

  function grid(r) {
    const N = r.days, s0 = num(r.from), t0 = num(today) - s0;
    $('#range').textContent = `${dstr(r.from)} to ${dstr(strOf(s0 + N - 1))}`;
    const dayInfo = Array.from({ length: N }, (_, i) => { const d = new Date((s0 + i) * 864e5); return { i, date: strOf(s0 + i), dow: d.getUTCDay(), dom: d.getUTCDate(), mon: d.toLocaleDateString('en-PH', { month: 'short', timeZone: 'UTC' }) }; });
    const bg = d => (d.i === t0 ? 'bg-sunset/15' : (d.dow === 0 || d.dow === 6) ? 'bg-slate-50' : '');
    const label = 'sticky left-0 z-20 shrink-0 border-r border-slate-200 bg-white';

    const head = `<div class="flex border-b border-slate-200"><div class="${label} flex items-end px-4 pb-2 text-xs font-semibold text-slate-500" style="width:${LEFT}px">Room</div>
      ${dayInfo.map(d => `<div class="shrink-0 border-l border-slate-100 py-2 text-center ${bg(d)}" style="width:${CW}px"><p class="h-3.5 text-[10px] font-semibold uppercase text-bronze">${d.dom === 1 || d.i === 0 ? d.mon : ''}</p><p class="text-[11px] text-slate-400">${'SMTWTFS'[d.dow]}</p><p class="mx-auto grid h-6 w-6 place-items-center rounded-full text-sm font-semibold ${d.i === t0 ? 'bg-bronze text-white' : ''}">${d.dom}</p></div>`).join('')}</div>`;

    const live = r.bookings;
    const cap = r.capacity || 1;
    const occ = `<div class="flex border-b border-slate-200"><div class="${label} flex items-center px-4 py-2 text-xs font-semibold text-slate-500" style="width:${LEFT}px">Occupancy</div>
      ${dayInfo.map(d => { const used = live.filter(b => num(b.checkin) - s0 <= d.i && d.i < num(b.checkout) - s0).length, pct = Math.round(used / cap * 100);
        return `<div class="shrink-0 border-l border-slate-100 py-2 text-center text-[11px] font-semibold" style="width:${CW}px;background:rgba(168,116,59,${(pct / 100 * .55).toFixed(2)});color:${pct > 55 ? '#fff' : '#0B2A43'}" title="${used} of ${cap} rooms">${pct}%</div>`; }).join('')}</div>`;

    const body = r.rooms.map(room => {
      const mine = live.filter(b => b.room_id === room.id);
      if (!room.active && !mine.length) return '';
      const lanes = []; // greedy: first unit that is free on the check-in date
      mine.forEach(b => { let l = lanes.find(x => x.end <= b.checkin); if (!l) { l = { end: '', items: [] }; lanes.push(l); } l.items.push(b); l.end = b.checkout; });
      const rows = Math.max(+room.units, lanes.length);
      let html = `<div class="flex border-b border-slate-200 bg-slate-50"><div class="${label} bg-slate-50 px-4 py-2" style="width:${LEFT}px"><p class="text-sm font-semibold">${esc(room.name)}${room.active ? '' : ' <span class="text-xs font-normal text-slate-400">(hidden)</span>'}</p><p class="text-xs text-slate-500">${money(room.price)} · ${room.units} ${room.units == 1 ? 'unit' : 'units'}</p></div><div style="width:${CW * N}px"></div></div>`;
      for (let u = 0; u < rows; u++) {
        const items = lanes[u]?.items || [];
        const bars = items.map(b => {
          const S = Math.max(num(b.checkin) - s0 + .5, 0), E = Math.min(num(b.checkout) - s0 + .5, N); if (E <= S) return '';
          const cutL = num(b.checkin) - s0 + .5 < 0, cutR = num(b.checkout) - s0 + .5 > N;
          const tip = `${b.name} · ${b.ref}\n${dstr(b.checkin)} to ${dstr(b.checkout)} · ${b.guests} guests\n${LABEL[b.status] || b.status}, ${b.pay_status}, ${money(b.total)}`;
          return `<a href="bookings.php?open=${b.id}" title="${esc(tip)}" class="absolute top-1.5 z-10 h-7 truncate px-2 text-xs font-semibold leading-7 ring-1 hover:brightness-95 ${COLORS[b.status] || COLORS.confirmed} ${cutL ? 'rounded-l-none' : 'rounded-l-lg'} ${cutR ? 'rounded-r-none' : 'rounded-r-lg'}" style="left:${S * CW}px;width:${(E - S) * CW}px">${esc(b.name)}</a>`;
        }).join('');
        html += `<div class="flex border-b border-slate-100"><div class="${label} flex items-center px-4 text-xs text-slate-500" style="width:${LEFT}px">Unit ${u + 1}</div>
          <div class="relative flex shrink-0" style="width:${CW * N}px">${dayInfo.map(d => `<button type="button" class="h-10 shrink-0 border-l border-slate-100 hover:bg-bronze/10 ${bg(d)}" style="width:${CW}px" data-room="${esc(room.id)}" data-date="${d.date}" aria-label="Add booking for ${esc(room.name)} on ${d.date}"></button>`).join('')}${bars}</div></div>`;
      }
      return html;
    }).join('');
    $('#cal').innerHTML = `<div style="min-width:${LEFT + CW * N}px">${head}${occ}${body || '<p class="px-6 py-16 text-center text-sm text-slate-400">No rooms yet. Add rooms first.</p>'}</div>`;
    const t = $('#cal').scrollLeft; if (!t && t0 > 4) $('#cal').scrollLeft = 0;
  }

  const shift = n => { from = strOf(num(from) + n); load(); };
  $('#prev').addEventListener('click', () => shift(-Math.round(days / 2)));
  $('#next').addEventListener('click', () => shift(Math.round(days / 2)));
  $('#today').addEventListener('click', () => { from = strOf(num(today) - 3); load(); });
  $('#span').addEventListener('change', e => { days = +e.target.value; load(); });
  $('#cal').addEventListener('click', e => {
    const c = e.target.closest('[data-room]'); if (!c) return;
    location.href = 'bookings.php?' + new URLSearchParams({ new: 1, room: c.dataset.room, checkin: c.dataset.date });
  });
  load();
})();

(() => {
  const { $, esc, money, dstr, api, toast, badge, methodLabel } = A;

  const delta = (cur, prev) => {
    if (!prev) return cur ? '<span class="text-emerald-600">New this month</span>' : '<span class="text-slate-400">No data yet</span>';
    const p = Math.round((cur - prev) / prev * 100), up = p >= 0;
    return `<span class="${up ? 'text-emerald-600' : 'text-red-600'}">${up ? '+' : ''}${p}%</span> <span class="text-slate-400">vs last month</span>`;
  };
  const card = (label, value, sub) => `<div class="card p-6"><p class="text-sm font-medium text-slate-500">${label}</p><p class="mt-2 text-3xl font-bold tracking-tight">${value}</p><p class="mt-2 text-sm">${sub}</p></div>`;
  const empty = t => `<p class="px-6 py-10 text-center text-sm text-slate-400">${t}</p>`;

  function chart(el, days) {
    const W = 640, H = 230, L = 52, R = 8, T = 10, B = 26;
    const max = Math.max(1, ...days.map(d => d.revenue)), pw = Math.pow(10, Math.floor(Math.log10(max)));
    const top = [1, 2, 2.5, 5, 10].map(m => m * pw).find(v => v >= max);
    const x = i => L + i * (W - L - R) / (days.length - 1), y = v => T + (H - T - B) * (1 - v / top);
    const line = days.map((d, i) => `${i ? 'L' : 'M'}${x(i).toFixed(1)} ${y(d.revenue).toFixed(1)}`).join('');
    const grid = [0, .25, .5, .75, 1].map(f => `<line x1="${L}" x2="${W - R}" y1="${y(top * f)}" y2="${y(top * f)}" stroke="#E2E8F0" stroke-dasharray="3 4"/><text x="${L - 8}" y="${y(top * f) + 4}" text-anchor="end" font-size="11" fill="#94A3B8">${top * f >= 1000 ? (top * f / 1000) + 'k' : top * f}</text>`).join('');
    const xl = days.map((d, i) => i % 6 === 0 ? `<text x="${x(i)}" y="${H - 6}" text-anchor="middle" font-size="11" fill="#94A3B8">${new Date(d.d + 'T00:00:00').toLocaleDateString('en-PH', { month: 'short', day: 'numeric' })}</text>` : '').join('');
    el.innerHTML = `<svg viewBox="0 0 ${W} ${H}" class="h-auto w-full" role="img" aria-label="Revenue per day for the last 30 days">
      <defs><linearGradient id="g" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#A8743B" stop-opacity=".28"/><stop offset="1" stop-color="#A8743B" stop-opacity="0"/></linearGradient></defs>
      ${grid}${xl}<path d="${line}L${x(days.length - 1)} ${y(0)}L${x(0)} ${y(0)}Z" fill="url(#g)"/><path d="${line}" fill="none" stroke="#A8743B" stroke-width="2.5" stroke-linejoin="round"/>
      <circle id="dot" r="5" fill="#fff" stroke="#A8743B" stroke-width="2.5" class="hidden"/></svg><div id="tip" class="pointer-events-none absolute hidden rounded-lg bg-lagoon px-3 py-2 text-xs text-white shadow-lg"></div>`;
    const svg = $('svg', el), dot = $('#dot', el), tip = $('#tip', el);
    svg.addEventListener('mousemove', e => {
      const r = svg.getBoundingClientRect(), px = (e.clientX - r.left) / r.width * W;
      const i = Math.max(0, Math.min(days.length - 1, Math.round((px - L) / ((W - L - R) / (days.length - 1)))));
      dot.classList.remove('hidden'); dot.setAttribute('cx', x(i)); dot.setAttribute('cy', y(days[i].revenue));
      tip.classList.remove('hidden'); tip.innerHTML = `<strong>${money(days[i].revenue)}</strong><br>${dstr(days[i].d)}`;
      tip.style.left = Math.min(r.width - 110, Math.max(0, x(i) / W * r.width - 40)) + 'px'; tip.style.top = Math.max(0, y(days[i].revenue) / H * r.height - 52) + 'px';
    });
    svg.addEventListener('mouseleave', () => { dot.classList.add('hidden'); tip.classList.add('hidden'); });
  }

  async function load() {
    const r = await api('dashboard', 'stats');
    if (!r.ok) { toast(r.error || 'Could not load the dashboard.', 'err'); return; }
    const k = r.kpi;
    if (r.default_password) $('#pwWarn').classList.replace('hidden', 'flex');
    $('#kpis').innerHTML =
      card('Revenue this month', money(k.revenue), delta(k.revenue, k.revenue_prev)) +
      card('New bookings', k.bookings, delta(k.bookings, k.bookings_prev)) +
      card('Occupancy, next 30 nights', k.occupancy + '%', `<span class="text-slate-400">Booked nights vs capacity</span>`) +
      card('Outstanding balance', money(k.outstanding), `<a class="font-medium text-bronze hover:underline" href="payments.php">${k.pending} pending ${k.pending === 1 ? 'request' : 'requests'}</a>`);

    $('#revTotal').textContent = money(r.series.reduce((t, d) => t + d.revenue, 0));
    chart($('#chart'), r.series);

    const S = { pending: ['Pending', '#F59E0B'], confirmed: ['Confirmed', '#0EA5E9'], checked_in: ['Checked in', '#14B8A6'], completed: ['Completed', '#10B981'], cancelled: ['Cancelled', '#94A3B8'] };
    const total = Object.values(r.status).reduce((a, b) => a + +b, 0);
    $('#statusBar').innerHTML = total ? Object.keys(S).map(s => r.status[s] ? `<span style="width:${r.status[s] / total * 100}%;background:${S[s][1]}" title="${S[s][0]}"></span>` : '').join('') : '';
    $('#statusList').innerHTML = total ? Object.keys(S).map(s => `<li class="flex items-center justify-between"><span class="flex items-center gap-2"><i class="h-2.5 w-2.5 rounded-full" style="background:${S[s][1]}"></i>${S[s][0]}</span><strong>${r.status[s] || 0}</strong></li>`).join('') : '<li class="text-slate-400">No bookings yet.</li>';

    const row = (b, right) => `<div class="flex items-center justify-between gap-3 px-6 py-3.5"><a href="bookings.php?open=${b.id}" class="min-w-0"><p class="truncate text-sm font-semibold hover:underline">${esc(b.name)}</p><p class="truncate text-xs text-slate-500">${esc(b.room_name)} · ${dstr(b.checkin)}</p></a>${right}</div>`;
    $('#pendingList').innerHTML = r.pending.length ? r.pending.map(b => row(b, `<div class="flex shrink-0 gap-1.5"><button class="btn-primary btn-sm" data-act="confirmed" data-id="${b.id}">Confirm</button><button class="btn-danger btn-sm" data-act="cancelled" data-id="${b.id}">Decline</button></div>`)).join('') : empty('All caught up. No pending requests.');
    $('#arrivalList').innerHTML = r.arrivals.length ? r.arrivals.map(b => row(b, `<div class="shrink-0 text-right">${badge('booking', b.status)}<p class="mt-1 text-xs text-slate-500">${b.nights} ${b.nights == 1 ? 'night' : 'nights'}, ${b.guests} guests</p></div>`)).join('') : empty('No arrivals in the next 7 days.');
    $('#payList').innerHTML = r.payments.length ? r.payments.map(p => `<div class="flex items-center justify-between gap-3 px-6 py-3.5"><div class="min-w-0"><p class="truncate text-sm font-semibold">${esc(p.name)}</p><p class="text-xs text-slate-500">${methodLabel(p.method)} · ${dstr(p.paid_at)}</p></div><strong class="text-sm text-emerald-700">+${money(p.amount)}</strong></div>`).join('') : empty('No payments recorded yet.');
  }

  document.addEventListener('click', async e => {
    const b = e.target.closest('[data-act]'); if (!b) return;
    b.disabled = true;
    const r = await api('bookings', 'status', { id: b.dataset.id, status: b.dataset.act });
    if (r.ok) { toast(b.dataset.act === 'confirmed' ? 'Booking confirmed' : 'Booking declined'); load(); } else { toast(r.error, 'err'); b.disabled = false; }
  });
  load();
})();

(() => {
  const { $, esc, money, dstr, iso, api, toast, drawer, badge, debounce, pager, payForm, methodLabel, confirm } = A;
  const st = { status: new URLSearchParams(location.search).get('status') || 'all', q: '', page: 1 };
  const TABS = [['all', 'All'], ['pending', 'Pending'], ['confirmed', 'Confirmed'], ['checked_in', 'Checked in'], ['completed', 'Completed'], ['cancelled', 'Cancelled']];

  async function load() {
    $('#rows').innerHTML = `<tr><td colspan="6" class="p-4"><div class="skel h-40 rounded-xl"></div></td></tr>`;
    const r = await api('bookings', 'list', st);
    if (!r.ok) { toast(r.error, 'err'); return; }
    const all = Object.values(r.counts).reduce((a, b) => a + +b, 0);
    $('#tabs').innerHTML = TABS.map(([k, l]) => `<button role="tab" aria-selected="${k === st.status}" data-tab="${k}" class="rounded-lg px-3 py-1.5 text-sm font-medium ${k === st.status ? 'bg-lagoon text-white' : 'text-slate-600 hover:bg-slate-100'}">${l} <span class="${k === st.status ? 'text-white/70' : 'text-slate-400'}">${k === 'all' ? all : (r.counts[k] || 0)}</span></button>`).join('');
    $('#rows').innerHTML = r.bookings.length ? r.bookings.map(b => `
      <tr class="cursor-pointer hover:bg-slate-50" data-id="${b.id}" tabindex="0">
        <td class="td"><p class="font-semibold">${esc(b.name)}</p><p class="text-xs text-slate-500">${esc(b.ref)}${b.source === 'admin' ? ' · manual' : ''}</p></td>
        <td class="td">${dstr(b.checkin)}<p class="text-xs text-slate-500">${b.nights} ${b.nights == 1 ? 'night' : 'nights'}, ${b.guests} guests</p></td>
        <td class="td">${esc(b.room_name)}</td>
        <td class="td text-right font-semibold">${money(b.total)}</td>
        <td class="td">${badge('pay', b.pay_status)}</td>
        <td class="td">${badge('booking', b.status)}</td>
      </tr>`).join('') : `<tr><td colspan="6" class="px-4 py-16 text-center text-sm text-slate-400">${st.q || st.status !== 'all' ? 'No bookings match these filters.' : 'No bookings yet. They appear here when guests send a request.'}</td></tr>`;
    pager($('#pager'), r, p => { st.page = p; load(); });
  }

  async function openBooking(id) {
    const r = await api('bookings', 'get', { id });
    if (!r.ok) { toast(r.error, 'err'); return; }
    const b = r.booking, root = document.createElement('div');
    const act = { confirmed: ['Confirm booking', 'btn-primary'], checked_in: ['Check in', 'btn-primary'], completed: ['Mark completed', 'btn-primary'], cancelled: ['Cancel booking', 'btn-danger'], pending: ['Reopen as pending', 'btn-ghost'] };
    const kv = (k, v) => `<div class="flex justify-between gap-4 py-2 text-sm"><dt class="text-slate-500">${k}</dt><dd class="text-right font-medium">${v}</dd></div>`;
    root.innerHTML = `
      <div class="flex flex-wrap items-center gap-2"><span class="font-mono text-sm font-semibold">${esc(b.ref)}</span>${badge('booking', b.status)}${badge('pay', b.pay_status)}</div>
      <div class="mt-5 flex flex-wrap gap-2">${r.next.map(n => `<button class="${act[n][1]}" data-go="${n}">${act[n][0]}</button>`).join('')}<a class="btn-ghost" href="../receipt.php?ref=${encodeURIComponent(b.ref)}&t=${esc(b.token)}" target="_blank" rel="noopener">Download receipt</a></div>
      <h3 class="mt-7 text-sm font-semibold">Stay</h3><dl class="divide-y divide-slate-100">${kv('Room', esc(b.room_name))}${kv('Check-in', dstr(b.checkin))}${kv('Check-out', dstr(b.checkout))}${kv('Nights', b.nights)}${kv('Guests', b.guests)}</dl>
      <h3 class="mt-6 text-sm font-semibold">Guest</h3><dl class="divide-y divide-slate-100">${kv('Name', esc(b.name))}${kv('Email', b.email ? `<a class="text-bronze hover:underline" href="mailto:${esc(b.email)}">${esc(b.email)}</a>` : '<span class="text-slate-400">None</span>')}${kv('Phone', b.phone ? `<a class="text-bronze hover:underline" href="tel:${esc(b.phone)}">${esc(b.phone)}</a>` : '<span class="text-slate-400">None</span>')}${b.notes ? kv('Guest notes', esc(b.notes)) : ''}</dl>
      <h3 class="mt-6 text-sm font-semibold">Pricing</h3><dl class="divide-y divide-slate-100">${kv('Subtotal', money(b.subtotal))}${b.discount_code ? kv('Discount ' + esc(b.discount_code), '-' + money(b.discount_amount)) : ''}${kv('Total', money(b.total))}${kv('Paid', money(b.paid))}${kv('Balance', `<strong>${money(b.balance)}</strong>`)}</dl>
      <h3 class="mt-6 text-sm font-semibold">Payments</h3>
      <ul class="mt-2 divide-y divide-slate-100 text-sm">${r.payments.length ? r.payments.map(p => `<li class="flex items-center justify-between gap-3 py-2"><span>${esc(methodLabel(p.method, p))} · ${dstr(p.paid_at)}${p.reference ? ' · ' + esc(p.reference) : ''}${p.status === 'pending' ? ` <a class="font-semibold text-bronze hover:underline" href="payments.php?open=${p.id}">Review</a>` : ''}</span><span class="flex items-center gap-2">${p.status !== 'paid' ? badge('pay', p.status === 'refunded' ? 'refunded' : p.status) : ''}<span class="${['refunded', 'declined', 'pending'].includes(p.status) ? 'text-slate-400' : 'font-medium'} ${['refunded', 'declined'].includes(p.status) ? 'line-through' : ''}">${money(p.amount)}</span></span></li>`).join('') : '<li class="py-2 text-slate-400">No payments yet.</li>'}</ul>
      <div id="payHere" class="mt-4"></div>
      <h3 class="mt-6 text-sm font-semibold">Internal notes</h3>
      <textarea id="notes" rows="3" class="input mt-2" placeholder="Only visible to staff">${esc(b.admin_notes)}</textarea>
      <button id="saveNotes" class="btn-ghost btn-sm mt-2">Save notes</button>`;
    if (b.balance > 0 && b.status !== 'cancelled') $('#payHere', root).append(payForm({ id: b.id, balance: b.balance }, () => { openBooking(id); load(); }));
    drawer.open('Booking details', root);
    root.addEventListener('click', async e => {
      const g = e.target.closest('[data-go]');
      if (g) {
        if (g.dataset.go === 'cancelled' && !(await confirm('Cancel this booking?', 'The room becomes available again and the guest is emailed.', 'Cancel booking'))) return;
        g.disabled = true; const x = await api('bookings', 'status', { id, status: g.dataset.go });
        if (x.ok) { toast('Booking updated'); openBooking(id); load(); } else { toast(x.error, 'err'); g.disabled = false; }
      }
      if (e.target.id === 'saveNotes') { const x = await api('bookings', 'notes', { id, admin_notes: $('#notes', root).value }); toast(x.ok ? 'Notes saved' : x.error, x.ok ? 'ok' : 'err'); }
    });
  }

  async function newBooking() {
    const r = await api('bookings', 'rooms'); if (!r.ok) { toast(r.error, 'err'); return; }
    if (!r.rooms.length) { drawer.open('New booking', '<p class="text-slate-600">There are no rooms yet. Add a room first, then you can create bookings.</p><a href="rooms.php" class="btn-primary mt-4">Add a room</a>'); return; }
    const f = document.createElement('form'); f.className = 'space-y-4'; f.noValidate = true;
    f.innerHTML = `
      <div><label class="lbl">Room</label><select class="input" name="room">${r.rooms.map(x => `<option value="${esc(x.id)}">${esc(x.name)} (${money(x.price)} a night, up to ${x.guests})</option>`).join('')}</select></div>
      <div class="grid grid-cols-3 gap-3"><div><label class="lbl">Check-in</label><input class="input" type="date" name="checkin" min="${iso()}"></div><div><label class="lbl">Check-out</label><input class="input" type="date" name="checkout" min="${iso()}"></div><div><label class="lbl">Guests</label><input class="input" type="number" name="guests" min="1" value="2"></div></div>
      <div class="grid grid-cols-2 gap-3"><div><label class="lbl">Guest name</label><input class="input" name="name"></div><div><label class="lbl">Phone</label><input class="input" name="phone"></div></div>
      <div><label class="lbl">Email (optional)</label><input class="input" type="email" name="email"></div>
      <div class="grid grid-cols-2 gap-3"><div><label class="lbl">Discount code (optional)</label><input class="input uppercase" name="code"></div><div><label class="lbl">Status</label><select class="input" name="status"><option value="confirmed">Confirmed</option><option value="pending">Pending</option></select></div></div>
      <div><label class="lbl">Notes</label><textarea class="input" name="notes" rows="2"></textarea></div>
      <div class="flex items-center gap-3"><button class="btn-primary">Create booking</button><p class="text-sm text-red-700" data-err></p></div>`;
    const pre = new URLSearchParams(location.search);
    if (pre.get('room')) f.room.value = pre.get('room');
    if (pre.get('checkin')) { f.checkin.value = pre.get('checkin'); f.checkout.value = pre.get('checkout') || iso(new Date(new Date(pre.get('checkin') + 'T00:00:00').getTime() + 864e5)); }
    f.onsubmit = async e => {
      e.preventDefault(); const btn = $('button', f); btn.disabled = true; $('[data-err]', f).textContent = '';
      const x = await api('bookings', 'create', new FormData(f)); btn.disabled = false;
      if (x.ok) { toast('Booking ' + x.ref + ' created'); drawer.close(); load(); } else $('[data-err]', f).textContent = x.error;
    };
    drawer.open('New booking', f);
  }

  $('#tabs').addEventListener('click', e => { const t = e.target.closest('[data-tab]'); if (t) { st.status = t.dataset.tab; st.page = 1; load(); } });
  $('#q').addEventListener('input', debounce(e => { st.q = e.target.value.trim(); st.page = 1; load(); }));
  $('#rows').addEventListener('click', e => { const tr = e.target.closest('[data-id]'); if (tr) openBooking(tr.dataset.id); });
  $('#rows').addEventListener('keydown', e => { if (e.key === 'Enter') { const tr = e.target.closest('[data-id]'); if (tr) openBooking(tr.dataset.id); } });
  $('#newBtn').addEventListener('click', newBooking);
  load();
  const qs = new URLSearchParams(location.search);
  if (qs.get('open')) openBooking(qs.get('open'));
  if (qs.get('new')) newBooking();
})();
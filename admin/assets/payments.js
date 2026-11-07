(() => {
  const { $, esc, money, dstr, api, toast, drawer, badge, debounce, pager, payForm, METHODS, methodLabel, confirm } = A;
  const qs = new URLSearchParams(location.search);
  const st = { q: '', method: '', status: ['pending', 'paid', 'declined', 'refunded'].includes(qs.get('status')) ? qs.get('status') : '', month: '', page: 1 };
  $('#method').insertAdjacentHTML('beforeend', METHODS.map(m => `<option value="${m[0]}">${m[1]}</option>`).join(''));
  const STATUS_BADGE = { pending: 'pending', paid: 'approved', declined: 'declined', refunded: 'refunded' };
  const sbadge = s => badge('pay', STATUS_BADGE[s] || s);
  const shortHash = h => h.length > 16 ? `${esc(h.slice(0, 8))}…${esc(h.slice(-6))}` : esc(h);
  const when = d => { const x = new Date(String(d).replace(' ', 'T')); return x.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) + ', ' + x.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' }); };
  const card = (l, v, s, hot) => `<div class="card p-6 ${hot ? 'ring-2 ring-amber-300' : ''}"><p class="text-sm font-medium text-slate-500">${l}</p><p class="mt-2 text-3xl font-bold tracking-tight">${v}</p><p class="mt-2 text-sm text-slate-400">${s}</p></div>`;
  let list = [];

  // Confirms the booking after a payment is approved.
  // Only moves bookings that are still pending, so checked-in or completed stays are never moved backwards.
  async function autoConfirm(bookingId) {
    const r = await api('bookings', 'get', { id: bookingId });
    if (r.ok && r.booking.status === 'pending' && r.next.includes('confirmed')) {
      const x = await api('bookings', 'status', { id: bookingId, status: 'confirmed' });
      return x.ok;
    }
    return false;
  }

  const sync = () => { $('#export').href = 'api/payments.php?' + new URLSearchParams({ action: 'export', q: st.q, method: st.method, status: st.status, month: st.month }); };

  async function load() {
    sync();
    const r = await api('payments', 'list', st); if (!r.ok) { toast(r.error, 'err'); return; }
    list = r.payments; const s = r.summary, c = r.counts, pend = +c.pending || 0, d = s.collected_prev ? Math.round((s.collected - s.collected_prev) / s.collected_prev * 100) : null;
    $('#sum').innerHTML =
      card('Awaiting your review', pend, pend ? `${money(r.amounts.pending || 0)} to approve or decline` : 'Nothing waiting', pend > 0) +
      card('Collected this month', money(s.collected), d === null ? 'No approved payments last month' : `${d >= 0 ? '+' : ''}${d}% vs last month`) +
      card('Outstanding balance', money(s.outstanding), 'On confirmed and completed stays') +
      card('Refunded this month', money(s.refunded), 'Marked as refunded');
    const all = Object.values(c).reduce((a, b) => a + +b, 0);
    $('#tabs').innerHTML = [['pending', 'Needs review', pend], ['paid', 'Approved', c.paid || 0], ['declined', 'Declined', c.declined || 0], ['refunded', 'Refunded', c.refunded || 0], ['', 'All', all]]
      .map(([k, l, n]) => `<button role="tab" aria-selected="${k === st.status}" data-tab="${k}" class="rounded-lg px-3 py-1.5 text-sm font-medium ${k === st.status ? 'bg-lagoon text-white' : 'text-slate-600 hover:bg-slate-100'}">${l} <span class="${k === st.status ? 'text-white/70' : (k === 'pending' && n ? 'font-bold text-amber-600' : 'text-slate-400')}">${n}</span></button>`).join('');
    $('#rows').innerHTML = list.length ? list.map(p => `
      <tr class="cursor-pointer hover:bg-slate-50 ${p.status === 'pending' ? 'bg-amber-50/50' : ''}" data-id="${p.id}" tabindex="0">
        <td class="td whitespace-nowrap">${dstr(p.paid_at)}</td>
        <td class="td"><p class="font-semibold">${esc(p.name)}</p><p class="text-xs text-slate-500">${esc(p.ref)} · ${p.submitted_by === 'guest' ? 'sent by guest' : 'recorded by staff'}</p></td>
        <td class="td">${esc(methodLabel(p.method, p))}${p.method === 'crypto' ? `<p class="text-xs text-slate-500">${+p.crypto_amount} ${esc(p.coin)}</p>` : ''}</td>
        <td class="td text-slate-500">${p.method === 'crypto' && p.tx_hash ? `<span class="font-mono text-xs">${shortHash(p.tx_hash)}</span>` : (esc(p.reference) || '<span class="text-slate-300">None</span>')}${p.proof ? '<p class="text-xs text-bronze">Screenshot attached</p>' : ''}</td>
        <td class="td text-right font-semibold ${p.status === 'refunded' || p.status === 'declined' ? 'text-slate-400 line-through' : ''}">${money(p.amount)}</td>
        <td class="td">${sbadge(p.status)}</td>
        <td class="td text-right">${p.status === 'pending' ? '<button class="btn-primary btn-sm" data-review>Review</button>' : ''}</td></tr>`).join('')
      : `<tr><td colspan="7" class="px-4 py-16 text-center text-sm text-slate-400">${st.status === 'pending' ? 'Nothing to review. Payments guests send will wait here for you.' : 'No payments match these filters.'}</td></tr>`;
    pager($('#pager'), r, p => { st.page = p; load(); });
  }

  function openPayment(p) {
    const root = document.createElement('div'), kv = (k, v) => v === '' || v == null ? '' : `<div class="flex justify-between gap-4 py-2 text-sm"><dt class="text-slate-500">${k}</dt><dd class="max-w-[16rem] break-words text-right font-medium">${v}</dd></div>`;
    const img = /\.(jpe?g|png|webp)$/i.test(p.proof || ''), pending = p.status === 'pending';
    const partial = pending && +p.amount < +p.balance;
    const reasonText = partial ? `We received ${money(p.amount)}, but the full balance of ${money(p.balance)} is required. Please send the full amount.` : '';
    root.innerHTML = `
      <div class="flex flex-wrap items-center gap-2">${sbadge(p.status)}<span class="badge bg-slate-100 text-slate-600">${p.submitted_by === 'guest' ? 'Sent by guest' : 'Recorded by staff'}</span></div>
      <dl class="mt-5 divide-y divide-slate-100">
        ${kv('Guest', `<a class="text-bronze hover:underline" href="bookings.php?open=${p.booking_id}">${esc(p.name)}</a>`)}${kv('Booking', esc(p.ref) + ' · ' + esc(p.room_name))}
        ${kv('Booking total', money(p.booking_total))}${kv('Balance due now', `<strong>${money(p.balance)}</strong>`)}
        ${kv('Amount', money(p.amount))}${kv('Method', esc(methodLabel(p.method, p)))}${p.method === 'crypto' ? kv('Crypto received', `${+p.crypto_amount} ${esc(p.coin)}`) + kv('Transaction hash', `<button type="button" class="font-mono text-xs text-bronze hover:underline" data-copy="${esc(p.tx_hash)}" title="Copy">${esc(p.tx_hash)}</button>`) : ''}
        ${kv('Reference', esc(p.reference))}${kv('Date paid', dstr(p.paid_at))}${kv('Submitted', when(p.created_at))}${kv('Reviewed', p.reviewed_at ? when(p.reviewed_at) : '')}${kv('Decline reason', esc(p.decline_reason))}${kv('Note', esc(p.note))}
      </dl>
      ${p.proof ? `<h3 class="mt-6 text-sm font-semibold">Proof of payment</h3>${img ? `<a href="proof.php?id=${p.id}" target="_blank" rel="noopener"><img src="proof.php?id=${p.id}" alt="Proof of payment" class="mt-2 max-h-96 rounded-xl border border-slate-200"></a>` : `<a class="btn-ghost mt-2" href="proof.php?id=${p.id}" target="_blank" rel="noopener">Open the PDF</a>`}` : (pending ? '<p class="mt-6 text-sm text-slate-400">The guest did not attach a screenshot.</p>' : '')}
      ${pending ? `
        ${partial ? `
          <div class="mt-8 rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
            <strong>Partial payment.</strong> The guest sent ${money(p.amount)}, but the balance is ${money(p.balance)}. Decline it with a reason so the guest can pay the full amount.
          </div>` : `
          <div class="mt-8 rounded-2xl border border-slate-200 p-4">
            <h3 class="text-sm font-semibold">Approve</h3>
            <p class="mt-1 text-xs text-slate-500">Check the money arrived first. Approving also confirms the booking.</p>
            <button id="approve" class="btn-primary mt-3">Approve payment (${money(Math.min(p.amount, p.balance))})</button>
          </div>`}
        <div class="mt-4 rounded-2xl border border-slate-200 p-4">
          <h3 class="text-sm font-semibold">Decline</h3>
          <label class="lbl mt-3" for="reason">Reason (the guest receives this by email)</label>
          <textarea id="reason" class="input" rows="3" placeholder="e.g. We could not find this transfer in our account">${esc(reasonText)}</textarea>
          <button id="decline" class="btn-danger mt-2">Decline payment</button>
        </div>
        <p id="err" class="mt-3 text-sm text-red-700" role="alert"></p>` : (p.status === 'paid' ? '<button id="refund" class="btn-danger mt-6">Mark as refunded</button>' : '')}`;
    drawer.open(pending ? 'Review payment' : 'Payment details', root);
    const fail = m => { const el = $('#err', root); if (el) el.textContent = m; };
    root.addEventListener('click', async e => {
      const done = (msg) => { toast(msg); drawer.close(); load(); };
      if (e.target.id === 'approve') {
        e.target.disabled = true;
        const r = await api('payments', 'approve', { id: p.id, amount: Math.min(p.amount, p.balance) });
        if (r.ok) {
          const confirmed = await autoConfirm(p.booking_id);
          done(confirmed ? 'Payment approved and booking confirmed' : 'Payment approved');
        } else { fail(r.error); e.target.disabled = false; }
      }
      if (e.target.id === 'decline') {
        e.target.disabled = true; const r = await api('payments', 'decline', { id: p.id, reason: $('#reason', root).value });
        r.ok ? done('Payment declined') : (fail(r.error), e.target.disabled = false);
      }
      if (e.target.id === 'refund' && await confirm('Mark this payment as refunded?', 'It stops counting toward the booking and the balance is due again. Refund the money to the guest separately.', 'Mark refunded')) {
        const r = await api('payments', 'refund', { id: p.id }); r.ok ? done('Payment marked as refunded') : toast(r.error, 'err');
      }
    });
  }

  function recordDrawer(preset = {}) {
    const root = document.createElement('div');
    root.innerHTML = `<p class="mb-4 text-sm text-slate-500">Use this for money you already received yourself (cash, a bank transfer you checked). It is approved straight away and confirms the booking.</p><label class="lbl" for="bs">Find a booking</label><input id="bs" class="input" placeholder="Guest name or reference" autocomplete="off"><ul id="bres" class="mt-3 divide-y divide-slate-100 rounded-xl border border-slate-200"></ul><div id="bform" class="mt-6"></div>`;
    drawer.open('Record payment', root);
    const input = $('#bs', root), results = $('#bres', root);
    const search = async () => {
      const r = await api('payments', 'booking_search', { q: input.value.trim() });
      results.innerHTML = r.ok && r.bookings.length ? r.bookings.map(b => `<li><button type="button" class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left hover:bg-slate-50" data-b='${esc(JSON.stringify({ id: b.id, balance: b.balance, name: b.name, ref: b.ref }))}'><span><strong class="text-sm">${esc(b.name)}</strong><br><span class="text-xs text-slate-500">${esc(b.ref)} · ${esc(b.room_name)}</span></span><span class="text-sm font-semibold">${money(b.balance)} due</span></button></li>`).join('') : '<li class="px-4 py-6 text-center text-sm text-slate-400">No bookings with a balance found.</li>';
    };
    input.addEventListener('input', debounce(search)); search(); input.focus();
    results.addEventListener('click', e => {
      const b = e.target.closest('[data-b]'); if (!b) return;
      const bk = JSON.parse(b.dataset.b); results.innerHTML = `<li class="px-4 py-3 text-sm"><strong>${esc(bk.name)}</strong> · ${esc(bk.ref)}</li>`;
      const holder = $('#bform', root); holder.innerHTML = '';
      holder.append(payForm(bk, async () => { await autoConfirm(bk.id); drawer.close(); load(); }, preset));
    });
  }

  const openRow = e => { const tr = e.target.closest('[data-id]'); if (tr) openPayment(list.find(x => x.id == tr.dataset.id)); };
  $('#rows').addEventListener('click', openRow);
  $('#rows').addEventListener('keydown', e => { if (e.key === 'Enter') openRow(e); });
  $('#tabs').addEventListener('click', e => { const t = e.target.closest('[data-tab]'); if (t) { st.status = t.dataset.tab; st.page = 1; load(); } });
  $('#q').addEventListener('input', debounce(e => { st.q = e.target.value.trim(); st.page = 1; load(); }));
  $('#method').addEventListener('change', e => { st.method = e.target.value; st.page = 1; load(); });
  $('#month').addEventListener('change', e => { st.month = e.target.value; st.page = 1; load(); });
  $('#addPay').addEventListener('click', () => recordDrawer());
  load();
  if (qs.get('open')) api('payments', 'get', { id: qs.get('open') }).then(r => r.ok && openPayment(r.payment));
})();
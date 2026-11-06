(() => {
  const { $, esc, money, dstr, api, toast, drawer, badge, debounce, pager, payForm, METHODS, methodLabel, confirm } = A;
  const st = { q: '', method: '', status: '', month: '', page: 1 };
  $('#method').insertAdjacentHTML('beforeend', METHODS.map(m => `<option value="${m[0]}">${m[1]}</option>`).join(''));

  const sync = () => { $('#export').href = 'api/payments.php?' + new URLSearchParams({ action: 'export', q: st.q, method: st.method, status: st.status, month: st.month }); };
  const card = (l, v, s) => `<div class="card p-6"><p class="text-sm font-medium text-slate-500">${l}</p><p class="mt-2 text-3xl font-bold tracking-tight">${v}</p><p class="mt-2 text-sm text-slate-400">${s}</p></div>`;

  async function load() {
    sync();
    const r = await api('payments', 'list', st); if (!r.ok) { toast(r.error, 'err'); return; }
    const s = r.summary, d = s.collected_prev ? Math.round((s.collected - s.collected_prev) / s.collected_prev * 100) : null;
    $('#sum').innerHTML = card('Collected this month', money(s.collected), d === null ? 'No payments last month' : `${d >= 0 ? '+' : ''}${d}% vs last month`) + card('Outstanding balance', money(s.outstanding), 'On confirmed and completed stays') + card('Refunded this month', money(s.refunded), 'Marked as refunded');
    $('#rows').innerHTML = r.payments.length ? r.payments.map(p => `
      <tr><td class="td whitespace-nowrap">${dstr(p.paid_at)}</td>
        <td class="td"><a class="font-semibold hover:underline" href="bookings.php?open=${p.booking_id}">${esc(p.name)}</a><p class="text-xs text-slate-500">${esc(p.ref)} · ${esc(p.room_name)}</p></td>
        <td class="td">${esc(methodLabel(p.method, p))}${p.method === 'crypto' ? `<p class="text-xs text-slate-500">${+p.crypto_amount} ${esc(p.coin)}</p>` : ''}</td><td class="td text-slate-500">${p.method === 'crypto' && p.tx_hash ? `<button type="button" class="font-mono text-xs text-bronze hover:underline" data-copy="${esc(p.tx_hash)}" title="Copy transaction hash">${esc(p.tx_hash.slice(0, 8))}…${esc(p.tx_hash.slice(-6))}</button>` : (esc(p.reference) || '<span class="text-slate-300">None</span>')}</td>
        <td class="td text-right font-semibold ${p.status === 'refunded' ? 'text-slate-400 line-through' : ''}">${money(p.amount)}</td>
        <td class="td">${badge('pay', p.status === 'paid' ? 'paid' : 'refunded')}</td>
        <td class="td text-right">${p.status === 'paid' ? `<button class="btn-danger btn-sm" data-refund="${p.id}">Refund</button>` : ''}</td></tr>`).join('')
      : `<tr><td colspan="7" class="px-4 py-16 text-center text-sm text-slate-400">No payments match these filters.</td></tr>`;
    pager($('#pager'), r, p => { st.page = p; load(); });
  }

  function recordDrawer(preset = {}) {
    const root = document.createElement('div');
    root.innerHTML = `<label class="lbl" for="bs">Find a booking</label><input id="bs" class="input" placeholder="Guest name or reference" autocomplete="off"><ul id="bres" class="mt-3 divide-y divide-slate-100 rounded-xl border border-slate-200"></ul><div id="bform" class="mt-6"></div>`;
    drawer.open(preset.method === 'crypto' ? 'Record crypto payment' : 'Record payment', root);
    const input = $('#bs', root), list = $('#bres', root);
    const search = async () => {
      const r = await api('payments', 'booking_search', { q: input.value.trim() });
      list.innerHTML = r.ok && r.bookings.length ? r.bookings.map(b => `<li><button type="button" class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left hover:bg-slate-50" data-b='${esc(JSON.stringify({ id: b.id, balance: b.balance, name: b.name, ref: b.ref }))}'><span><strong class="text-sm">${esc(b.name)}</strong><br><span class="text-xs text-slate-500">${esc(b.ref)} · ${esc(b.room_name)}</span></span><span class="text-sm font-semibold">${money(b.balance)} due</span></button></li>`).join('') : '<li class="px-4 py-6 text-center text-sm text-slate-400">No bookings with a balance found.</li>';
    };
    input.addEventListener('input', debounce(search)); search(); input.focus();
    list.addEventListener('click', e => {
      const b = e.target.closest('[data-b]'); if (!b) return;
      const bk = JSON.parse(b.dataset.b); list.innerHTML = `<li class="px-4 py-3 text-sm"><strong>${esc(bk.name)}</strong> · ${esc(bk.ref)}</li>`;
      const holder = $('#bform', root); holder.innerHTML = ''; holder.append(payForm(bk, () => { drawer.close(); load(); }, preset));
    });
  }

  document.addEventListener('click', async e => {
    const b = e.target.closest('[data-refund]'); if (!b) return;
    if (!(await confirm('Mark this payment as refunded?', 'It stops counting toward the booking and the balance is due again. Refund the money to the guest separately.', 'Mark refunded'))) return;
    const r = await api('payments', 'refund', { id: b.dataset.refund }); if (r.ok) { toast('Payment marked as refunded'); load(); } else toast(r.error, 'err');
  });
  const bind = (id, key) => $(id).addEventListener(key === 'q' ? 'input' : 'change', debounce(e => { st[key] = e.target.value.trim(); st.page = 1; load(); }, key === 'q' ? 300 : 0));
  bind('#q', 'q'); bind('#method', 'method'); bind('#status', 'status'); bind('#month', 'month');
  $('#addPay').addEventListener('click', () => recordDrawer());
  $('#addCrypto').addEventListener('click', () => recordDrawer({ method: 'crypto' }));
  load();
})();

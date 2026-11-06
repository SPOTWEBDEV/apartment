// Shared admin helpers: window.A
window.A = (() => {
  const meta = n => document.querySelector(`meta[name=${n}]`)?.content || '';
  const CSRF = meta('csrf'), CUR = meta('cur') || '₱';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const money = n => (Number(n) < 0 ? '-' : '') + CUR + Math.abs(Number(n) || 0).toLocaleString('en-PH');
  const dstr = d => d ? new Date(String(d).slice(0, 10) + 'T00:00:00').toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '';
  const iso = (d = new Date()) => new Date(d.getTime() - d.getTimezoneOffset() * 6e4).toISOString().slice(0, 10);
  const debounce = (fn, ms = 300) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };

  async function api(res, action, data = {}) {
    let fd;
    if (data instanceof FormData) fd = data;
    else { fd = new FormData(); for (const [k, v] of Object.entries(data)) if (v !== undefined && v !== null) fd.append(k, v); }
    fd.set('action', action); fd.set('csrf', CSRF);
    let r, j;
    try { r = await fetch(`api/${res}.php`, { method: 'POST', body: fd }); j = await r.json(); }
    catch { j = { ok: false, error: 'Connection problem. Check your internet and try again.' }; }
    if (r && r.status === 401) location.href = 'login.php';
    return j;
  }

  function toast(msg, type = 'ok') {
    const el = document.createElement('div');
    el.className = `flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-medium text-white shadow-lg ${type === 'ok' ? 'bg-lagoon' : 'bg-red-600'}`;
    el.textContent = msg; $('#toasts').append(el);
    setTimeout(() => el.remove(), 4000);
  }

  const drawer = (() => {
    const d = $('#drawer'), s = $('#scrim'); let last;
    const open = (title, content) => {
      last = document.activeElement;
      $('#drawerTitle').textContent = title;
      const b = $('#drawerBody'); b.innerHTML = ''; typeof content === 'string' ? b.innerHTML = content : b.append(content);
      s.classList.remove('hidden'); d.classList.remove('translate-x-full'); d.setAttribute('aria-hidden', 'false');
      setTimeout(() => $('#drawerClose').focus(), 50); return b;
    };
    const close = () => { s.classList.add('hidden'); d.classList.add('translate-x-full'); d.setAttribute('aria-hidden', 'true'); last?.focus?.(); };
    s.addEventListener('click', close); $('#drawerClose').addEventListener('click', close);
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !d.classList.contains('translate-x-full')) close(); });
    return { open, close, body: () => $('#drawerBody') };
  })();

  function confirmBox(title, text, label = 'Confirm') {
    const dlg = $('#dlg'); $('#dlgTitle').textContent = title; $('#dlgText').textContent = text; $('#dlgYes').textContent = label;
    dlg.showModal();
    return new Promise(res => {
      const done = v => { dlg.close(); $('#dlgYes').onclick = $('#dlgNo').onclick = null; res(v); };
      $('#dlgYes').onclick = () => done(true); $('#dlgNo').onclick = () => done(false);
    });
  }

  const BK = { pending: ['Pending', 'bg-amber-50 text-amber-700 ring-amber-200'], confirmed: ['Confirmed', 'bg-sky-50 text-sky-700 ring-sky-200'], checked_in: ['Checked in', 'bg-teal-50 text-teal-700 ring-teal-200'], completed: ['Completed', 'bg-emerald-50 text-emerald-700 ring-emerald-200'], cancelled: ['Cancelled', 'bg-slate-100 text-slate-600 ring-slate-200'] };
  const PY = { unpaid: ['Unpaid', 'bg-red-50 text-red-700 ring-red-200'], partial: ['Partial', 'bg-amber-50 text-amber-700 ring-amber-200'], paid: ['Paid', 'bg-emerald-50 text-emerald-700 ring-emerald-200'], refunded: ['Refunded', 'bg-slate-100 text-slate-600 ring-slate-200'], active: ['Active', 'bg-emerald-50 text-emerald-700 ring-emerald-200'], off: ['Off', 'bg-slate-100 text-slate-600 ring-slate-200'], expired: ['Expired', 'bg-amber-50 text-amber-700 ring-amber-200'] };
  const badge = (kind, key) => { const [l, c] = (kind === 'booking' ? BK : PY)[key] || [key, 'bg-slate-100 text-slate-600 ring-slate-200']; return `<span class="badge ring-1 ring-inset ${c}">${l}</span>`; };
  const METHODS = [['cash', 'Cash'], ['bank', 'Bank transfer'], ['gcash', 'GCash'], ['maya', 'Maya'], ['card', 'Card'], ['crypto', 'Crypto'], ['other', 'Other']];
  const methodLabel = (k, p) => (METHODS.find(m => m[0] === k) || [0, k])[1] + (k === 'crypto' && p?.coin ? ` · ${p.coin} (${p.network})` : '');

  function pager(el, { page, pages, total, per }, go) {
    if (!total) { el.innerHTML = ''; return; }
    const from = (page - 1) * per + 1, to = Math.min(total, page * per);
    el.innerHTML = `<p class="text-sm text-slate-500">Showing ${from} to ${to} of ${total}</p>
      <div class="flex gap-2"><button class="btn-ghost btn-sm" data-p="${page - 1}" ${page <= 1 ? 'disabled' : ''}>Previous</button><button class="btn-ghost btn-sm" data-p="${page + 1}" ${page >= pages ? 'disabled' : ''}>Next</button></div>`;
    el.onclick = e => { const b = e.target.closest('[data-p]'); if (b && !b.disabled) go(+b.dataset.p); };
  }

  const CRYPTO = { BTC: ['Bitcoin'], ETH: ['Ethereum'], USDT: ['TRC20', 'ERC20', 'BEP20'], USDC: ['ERC20', 'Solana'], SOL: ['Solana'] };
  const netOptions = coin => (CRYPTO[coin] || []).map(n => `<option>${n}</option>`).join('');
  const copy = async text => { try { await navigator.clipboard.writeText(text); toast('Copied'); } catch { toast('Could not copy. Select the text and copy it manually.', 'err'); } };
  document.addEventListener('click', e => { const c = e.target.closest('[data-copy]'); if (c) copy(c.dataset.copy); });

  // Record-payment form for one booking: b = { id, balance }, preset = { method }
  function payForm(b, onDone, preset = {}) {
    const f = document.createElement('form'); f.className = 'space-y-3'; f.noValidate = true;
    f.innerHTML = `
      <div class="grid grid-cols-2 gap-3">
        <div><label class="lbl">Amount credited (PHP, balance ${money(b.balance)})</label><input class="input" name="amount" type="number" min="1" max="${b.balance}" value="${b.balance}"></div>
        <div><label class="lbl">Method</label><select class="input" name="method">${METHODS.map(m => `<option value="${m[0]}" ${m[0] === preset.method ? 'selected' : ''}>${m[1]}</option>`).join('')}</select></div>
        <div><label class="lbl">Date received</label><input class="input" name="paid_at" type="date" value="${iso()}" max="${iso()}"></div>
        <div><label class="lbl">Reference (optional)</label><input class="input" name="reference" placeholder="Receipt or transfer no."></div>
      </div>
      <div data-crypto class="hidden space-y-3 rounded-xl border border-amber-200 bg-amber-50/60 p-4">
        <p class="text-xs text-slate-600">Enter what actually arrived on-chain. The PHP amount above is what you credit to the booking.</p>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="lbl">Coin</label><select class="input" name="coin">${Object.keys(CRYPTO).map(c => `<option>${c}</option>`).join('')}</select></div>
          <div><label class="lbl">Network</label><select class="input" name="network">${netOptions('BTC')}</select></div>
          <div><label class="lbl">Crypto amount received</label><input class="input" name="crypto_amount" type="number" step="any" min="0" placeholder="0.00"></div>
          <div class="flex items-end"><p class="pb-2 text-xs text-slate-500" data-rate>Rate appears here</p></div>
        </div>
        <div><label class="lbl">Transaction hash</label><input class="input font-mono text-xs" name="tx_hash" autocomplete="off" placeholder="Paste the transaction ID"></div>
      </div>
      <input type="hidden" name="booking_id" value="${b.id}">
      <div class="flex items-center gap-3"><button class="btn-primary">Record payment</button><p class="text-sm text-red-700" data-err></p></div>`;
    const box = $('[data-crypto]', f), rate = () => {
      const a = +f.amount.value, c = +f.crypto_amount.value;
      $('[data-rate]', f).textContent = a > 0 && c > 0 ? `About ${money(Math.round(a / c))} per ${f.coin.value}` : 'Rate appears here';
    };
    const toggle = () => box.classList.toggle('hidden', f.method.value !== 'crypto');
    f.method.addEventListener('change', toggle); toggle();
    f.coin.addEventListener('change', () => { f.network.innerHTML = netOptions(f.coin.value); });
    f.amount.addEventListener('input', rate); f.crypto_amount.addEventListener('input', rate);
    f.onsubmit = async e => {
      e.preventDefault(); const btn = $('button', f); btn.disabled = true; $('[data-err]', f).textContent = '';
      const r = await api('payments', 'create', new FormData(f)); btn.disabled = false;
      if (r.ok) { toast('Payment recorded'); onDone?.(r); } else $('[data-err]', f).textContent = r.error || 'Could not record payment.';
    };
    return f;
  }

  // Mobile sidebar
  const side = $('#side'), sScrim = $('#sideScrim');
  $('#sideBtn')?.addEventListener('click', () => { side.classList.remove('-translate-x-full'); sScrim.classList.remove('hidden'); });
  sScrim?.addEventListener('click', () => { side.classList.add('-translate-x-full'); sScrim.classList.add('hidden'); });

  return { $, $$, esc, money, dstr, iso, debounce, api, toast, drawer, confirm: confirmBox, badge, METHODS, methodLabel, pager, payForm, CRYPTO, copy };
})();

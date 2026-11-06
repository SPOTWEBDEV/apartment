(() => {
  const { $, $$, api, toast } = A;
  const say = (form, ok, text) => { const m = $('[data-msg]', form); m.className = 'text-sm ' + (ok ? 'text-emerald-700' : 'text-red-700'); m.textContent = text; };

  api('settings', 'get').then(r => {
    if (!r.ok) { toast(r.error, 'err'); return; }
    for (const [k, v] of Object.entries(r.values)) { const el = $(`[name="${k}"]`); if (el) el.value = v ?? ''; }
  });

  $$('form[data-section]').forEach(f => f.addEventListener('submit', async e => {
    e.preventDefault(); const btn = $('button', f); btn.disabled = true; say(f, true, '');
    const fd = new FormData(f); fd.set('section', f.dataset.section);
    const r = await api('settings', 'save', fd); btn.disabled = false;
    if (r.ok) { say(f, true, 'Saved. The website now uses these values.'); toast('Settings saved'); } else say(f, false, r.error);
  }));

  $('#pwForm').addEventListener('submit', async e => {
    e.preventDefault(); const f = e.target, btn = $('button', f); btn.disabled = true; say(f, true, '');
    const r = await api('settings', 'password', new FormData(f)); btn.disabled = false;
    if (r.ok) { f.reset(); say(f, true, 'Password changed.'); toast('Password changed'); } else say(f, false, r.error);
  });
  if (location.hash === '#account') $('#account').scrollIntoView();
})();

// Crypto wallets
(() => {
  const { $, $$, api, toast, confirm, CRYPTO, esc } = A;
  const list = $('#wallets'), form = $('#cryptoForm');
  const opts = (arr, sel) => arr.map(x => `<option ${x === sel ? 'selected' : ''}>${x}</option>`).join('');
  const hint = () => { if (!list.children.length) list.innerHTML = '<p class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-400" data-empty>No wallets yet. Add one to start accepting crypto.</p>'; };

  function addRow(w = {}) {
    $('[data-empty]', list)?.remove();
    const coin = w.coin || 'USDT', row = document.createElement('div');
    row.className = 'grid gap-2 rounded-xl border border-slate-200 p-3 sm:grid-cols-[6rem_8rem_1fr]';
    row.innerHTML = `
      <select class="input" data-f="coin" aria-label="Coin">${opts(Object.keys(CRYPTO), coin)}</select>
      <select class="input" data-f="network" aria-label="Network">${opts(CRYPTO[coin], w.network)}</select>
      <input class="input font-mono text-xs" data-f="address" value="${esc(w.address || '')}" placeholder="Wallet address" autocomplete="off" aria-label="Wallet address">
      <input class="input sm:col-span-2" data-f="label" value="${esc(w.label || '')}" placeholder="Label shown to guests (optional), e.g. Main wallet" aria-label="Label">
      <button type="button" class="btn-danger btn-sm justify-self-end" data-rm>Remove</button>`;
    $('[data-f=coin]', row).addEventListener('change', e => { $('[data-f=network]', row).innerHTML = opts(CRYPTO[e.target.value]); });
    $('[data-rm]', row).addEventListener('click', () => { row.remove(); hint(); });
    list.append(row);
  }

  api('settings', 'get').then(r => {
    if (!r.ok) return;
    form.enabled.checked = !!r.values.crypto_enabled;
    (r.values.wallets || []).forEach(addRow); hint();
  });
  $('#addWallet').addEventListener('click', () => addRow());

  form.addEventListener('submit', async e => {
    e.preventDefault();
    const wallets = $$('#wallets > div[class*=grid]').map(row => Object.fromEntries($$('[data-f]', row).map(i => [i.dataset.f, i.value.trim()])));
    const msg = $('[data-msg]', form); msg.className = 'text-sm'; msg.textContent = '';
    if (wallets.length && !(await confirm('Save these wallets?', 'Guests will be shown these addresses and will send money to them. Have you checked every character?', 'Yes, save'))) return;
    const btn = $('button:not([type=button])', form); btn.disabled = true;
    const r = await api('settings', 'save', { section: 'crypto', enabled: form.enabled.checked ? 1 : 0, wallets: JSON.stringify(wallets) });
    btn.disabled = false;
    if (r.ok) { msg.classList.add('text-emerald-700'); msg.textContent = 'Saved.'; toast('Crypto wallets saved'); } else { msg.classList.add('text-red-700'); msg.textContent = r.error; }
  });
})();

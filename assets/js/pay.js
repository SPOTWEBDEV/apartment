// Payment details form (booking page and the guest's payment page) plus Copy buttons.
window.CLPay = (() => {
  const CRYPTO = window.CRYPTO || {};

  document.addEventListener('click', async e => {
    const c = e.target.closest('[data-copy]'); if (!c) return;
    try { await navigator.clipboard.writeText(c.dataset.copy); const t = c.dataset.label || (c.dataset.label = c.textContent); c.textContent = 'Copied'; setTimeout(() => c.textContent = t, 1500); }
    catch { c.textContent = 'Select and copy'; }
  });

  async function shrink(file) { // big phone screenshots are resized in the browser
    if (!file || !file.type.startsWith('image/') || file.size < 1.5e6 || !window.createImageBitmap) return file;
    try {
      const bmp = await createImageBitmap(file), s = Math.min(1, 1800 / Math.max(bmp.width, bmp.height));
      const c = document.createElement('canvas'); c.width = Math.round(bmp.width * s); c.height = Math.round(bmp.height * s);
      c.getContext('2d').drawImage(bmp, 0, 0, c.width, c.height);
      const blob = await new Promise(r => c.toBlob(r, 'image/jpeg', .85));
      return blob && blob.size < file.size ? new File([blob], 'proof.jpg', { type: 'image/jpeg' }) : file;
    } catch { return file; }
  }

  // Fills the "How much are you paying?" select: half (only when nothing is paid yet) or the full amount due.
  // due = amount still owed, total = booking total (omit it when nothing has been paid yet).
  function setAmounts(form, due, total = 0) {
    const sel = form.elements.namedItem('amount'); if (!sel || !(due > 0)) return;
    const money = n => (window.CL && window.CL.peso) ? window.CL.peso(n) : '₱' + Number(n).toLocaleString('en-PH');
    const half = Math.ceil(due / 2), opts = [];
    if (due > 1 && half < due && (!total || due >= total)) opts.push([half, 'Half payment']);
    opts.push([due, total && due < total ? 'Remaining balance' : 'Full payment']);
    sel.innerHTML = opts.map(([v, l]) => `<option value="${v}">${l} · ${money(v)}</option>`).join('');
    sel.value = String(due);
  }

  function bindMethods(root) {
  const bar = root.querySelector('[data-pay-methods]'); if (!bar) return;
  const form = root.querySelector('[data-pay-form]');
  const sel = form && form.elements.namedItem('method');
  const apply = m => {
    bar.querySelectorAll('[data-method]').forEach(b => b.setAttribute('aria-pressed', b.dataset.method === m ? 'true' : 'false'));
    const crypto = m === 'crypto';
    root.querySelectorAll('[data-acct-group]').forEach(g => g.hidden = (g.dataset.acctGroup === 'crypto') !== crypto);
    root.querySelectorAll('[data-acct-type]').forEach(li => li.hidden = !crypto && li.dataset.acctType !== m);
    if (sel && sel.value !== m) { sel.value = m; sel.dispatchEvent(new Event('change')); }
  };
  bar.addEventListener('click', e => { const b = e.target.closest('[data-method]'); if (b) apply(b.dataset.method); });
  if (sel) sel.addEventListener('change', e => { if (e.isTrusted) apply(sel.value); });
  apply(bar.querySelector('[data-method]').dataset.method);
}

  function init(form, { onSent } = {}) {
    if (form.dataset.payReady) return; form.dataset.payReady = '1'; // never wire the same form twice
    const el = n => form.elements.namedItem(n), msg = form.querySelector('[data-pay-msg]'), box = form.querySelector('[data-crypto]');
    const nets = () => { el('network').innerHTML = (CRYPTO[el('coin').value] || []).map(n => `<option>${n}</option>`).join(''); };
    const sync = () => box.classList.toggle('hidden', el('method').value !== 'crypto');
    el('coin').addEventListener('change', nets); el('method').addEventListener('change', sync); nets(); sync();

    form.addEventListener('submit', async e => {
      e.preventDefault();
      const btn = form.querySelector('button:not([type=button])'); btn.disabled = true;
      msg.className = 'min-h-6 text-sm text-slate-500'; msg.textContent = 'Sending…';
      const fd = new FormData(form), f = fd.get('proof');
      if (f && f.size) { const small = await shrink(f); fd.set('proof', small, small.name || 'proof'); } else fd.delete('proof');
      try {
        const r = await (await fetch('api/payment.php', { method: 'POST', body: fd })).json();
        if (r.ok) {
          if (onSent) { msg.textContent = ''; btn.disabled = false; onSent(); return; }
          const u = new URL(location.href); u.searchParams.set('sent', '1'); location.href = u.toString(); return;
        }
        msg.className = 'min-h-6 text-sm text-red-700'; msg.textContent = r.error || 'Could not send. Please try again.';
      } catch { msg.className = 'min-h-6 text-sm text-red-700'; msg.textContent = 'Connection problem. Your details were not sent.'; }
      btn.disabled = false;
    });
  }

  document.querySelectorAll('[data-pay-form]:not([data-manual])').forEach(f => init(f));
  return { init, setAmounts };
})();
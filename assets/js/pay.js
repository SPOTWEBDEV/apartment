(() => {
  document.addEventListener('click', async e => {
    const c = e.target.closest('[data-copy]'); if (!c) return;
    try { await navigator.clipboard.writeText(c.dataset.copy); const t = c.textContent; c.textContent = 'Copied'; setTimeout(() => c.textContent = t, 1500); } catch { c.textContent = 'Select and copy'; }
  });
  const form = document.getElementById('payForm'); if (!form) return;
  const $ = s => form.querySelector(s), msg = document.getElementById('payMsg');
  const CRYPTO = window.CRYPTO || {};
  const nets = () => { $('#network').innerHTML = (CRYPTO[$('#coin').value] || []).map(n => `<option>${n}</option>`).join(''); };
  const sync = () => $('#cryptoFields').classList.toggle('hidden', $('#method').value !== 'crypto');
  $('#coin').addEventListener('change', nets); $('#method').addEventListener('change', sync); nets(); sync();

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

  form.addEventListener('submit', async e => {
    e.preventDefault(); const btn = $('button'); btn.disabled = true; msg.className = 'min-h-6 text-sm text-slate-500'; msg.textContent = 'Sending…';
    const fd = new FormData(form), f = fd.get('proof');
    if (f && f.size) { const small = await shrink(f); fd.set('proof', small, small.name || 'proof'); } else fd.delete('proof');
    try {
      const r = await (await fetch('api/payment.php', { method: 'POST', body: fd })).json();
      if (r.ok) { const u = new URL(location.href); u.searchParams.set('sent', '1'); location.href = u.toString(); return; }
      msg.className = 'min-h-6 text-sm text-red-700'; msg.textContent = r.error || 'Could not send. Please try again.';
    } catch { msg.className = 'min-h-6 text-sm text-red-700'; msg.textContent = 'Connection problem. Your details were not sent.'; }
    btn.disabled = false;
  });
})();

(() => {
  const { $, esc, money, dstr, api, toast, drawer, badge, confirm } = A;
  let list = [], today = '';

  const state = d => !+d.active ? 'off' : (d.ends && d.ends < today) || (+d.max_uses && +d.used >= +d.max_uses) ? 'expired' : 'active';
  async function load() {
    const r = await api('discounts', 'list'); if (!r.ok) { toast(r.error, 'err'); return; }
    list = r.discounts; today = r.today;
    $('#rows').innerHTML = list.length ? list.map(d => `
      <tr><td class="td"><span class="rounded-md bg-slate-100 px-2 py-1 font-mono text-xs font-bold">${esc(d.code)}</span>${d.label ? `<p class="mt-1 text-xs text-slate-500">${esc(d.label)}</p>` : ''}</td>
        <td class="td font-semibold">${d.type === 'percent' ? d.value + '% off' : money(d.value) + ' off'}${+d.min_nights ? `<p class="text-xs font-normal text-slate-500">Min. ${d.min_nights} nights</p>` : ''}</td>
        <td class="td text-slate-600">${d.starts || d.ends ? `${d.starts ? dstr(d.starts) : 'Now'} to ${d.ends ? dstr(d.ends) : 'No end'}` : 'Always'}</td>
        <td class="td">${d.used}${+d.max_uses ? ' / ' + d.max_uses : ''}${+d.max_uses ? `<div class="mt-1.5 h-1.5 w-24 overflow-hidden rounded-full bg-slate-100"><div class="h-full bg-bronze" style="width:${Math.min(100, d.used / d.max_uses * 100)}%"></div></div>` : ''}</td>
        <td class="td">${badge('pay', state(d))}</td>
        <td class="td text-right whitespace-nowrap"><button class="btn-ghost btn-sm" data-edit="${d.id}">Edit</button> <button class="btn-ghost btn-sm" data-tog="${d.id}" data-on="${+d.active ? 0 : 1}">${+d.active ? 'Turn off' : 'Turn on'}</button></td></tr>`).join('')
      : '<tr><td colspan="6" class="px-4 py-16 text-center text-sm text-slate-400">No codes yet. Create one to run a promotion.</td></tr>';
  }

  function form(d) {
    const x = d || { code: '', label: '', type: 'percent', value: '', min_nights: 0, starts: '', ends: '', max_uses: 0, active: 1 };
    const f = document.createElement('form'); f.className = 'space-y-4'; f.noValidate = true;
    f.innerHTML = `<input type="hidden" name="id" value="${d ? d.id : 0}">
      <div class="grid grid-cols-2 gap-3"><div><label class="lbl">Code</label><input class="input uppercase" name="code" value="${esc(x.code)}" placeholder="SUMMER15"></div><div><label class="lbl">Label (internal)</label><input class="input" name="label" value="${esc(x.label)}"></div></div>
      <div class="grid grid-cols-2 gap-3"><div><label class="lbl">Type</label><select class="input" name="type"><option value="percent" ${x.type === 'percent' ? 'selected' : ''}>Percentage</option><option value="fixed" ${x.type === 'fixed' ? 'selected' : ''}>Fixed amount</option></select></div><div><label class="lbl">Value</label><input class="input" type="number" min="1" name="value" value="${x.value}"></div></div>
      <div class="grid grid-cols-2 gap-3"><div><label class="lbl">Starts (optional)</label><input class="input" type="date" name="starts" value="${esc(x.starts)}"></div><div><label class="lbl">Ends (optional)</label><input class="input" type="date" name="ends" value="${esc(x.ends)}"></div></div>
      <div class="grid grid-cols-2 gap-3"><div><label class="lbl">Minimum nights</label><input class="input" type="number" min="0" name="min_nights" value="${x.min_nights}"></div><div><label class="lbl">Max uses (0 = unlimited)</label><input class="input" type="number" min="0" name="max_uses" value="${x.max_uses}"></div></div>
      <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="active" value="1" ${+x.active ? 'checked' : ''} class="h-4 w-4 accent-[#A8743B]"> Active</label>
      <div class="flex flex-wrap items-center gap-3 pt-2"><button class="btn-primary">Save code</button>${d ? '<button type="button" class="btn-danger" data-del>Delete</button>' : ''}<p class="text-sm text-red-700" data-err></p></div>`;
    f.onsubmit = async e => {
      e.preventDefault(); const btn = $('button', f); btn.disabled = true; $('[data-err]', f).textContent = '';
      const fd = new FormData(f); if (!f.active.checked) fd.set('active', '0');
      const r = await api('discounts', 'save', fd); btn.disabled = false;
      if (r.ok) { toast('Code saved'); drawer.close(); load(); } else $('[data-err]', f).textContent = r.error;
    };
    $('[data-del]', f)?.addEventListener('click', async () => {
      if (!(await confirm('Delete this code?', 'Existing bookings keep their discount. The code stops working immediately.', 'Delete'))) return;
      const r = await api('discounts', 'delete', { id: d.id }); if (r.ok) { toast('Code deleted'); drawer.close(); load(); } else toast(r.error, 'err');
    });
    return f;
  }

  $('#addCode').addEventListener('click', () => drawer.open('New discount code', form()));
  $('#rows').addEventListener('click', async e => {
    const ed = e.target.closest('[data-edit]'); if (ed) drawer.open('Edit discount code', form(list.find(x => x.id == ed.dataset.edit)));
    const tg = e.target.closest('[data-tog]'); if (tg) { const r = await api('discounts', 'toggle', { id: tg.dataset.tog, active: tg.dataset.on }); r.ok ? load() : toast(r.error, 'err'); }
  });
  load();
})();

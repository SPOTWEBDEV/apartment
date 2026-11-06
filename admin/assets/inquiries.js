(() => {
  const { $, esc, dstr, api, toast, drawer, debounce, pager, confirm } = A;
  const st = { status: 'new', type: '', q: '', page: 1 };
  const TYPES = { stay: 'Stay question', table: 'Table reservation', wedding: 'Wedding', corporate: 'Corporate event', other: 'General' };
  const TYPE_CLS = { table: 'bg-sky-50 text-sky-700 ring-sky-200', wedding: 'bg-rose-50 text-rose-700 ring-rose-200', corporate: 'bg-indigo-50 text-indigo-700 ring-indigo-200', stay: 'bg-teal-50 text-teal-700 ring-teal-200', other: 'bg-slate-100 text-slate-600 ring-slate-200' };
  const typeBadge = t => `<span class="badge ring-1 ring-inset ${TYPE_CLS[t] || TYPE_CLS.other}">${TYPES[t] || t}</span>`;
  const stBadge = s => s === 'new' ? '<span class="badge bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200">New</span>' : '<span class="badge bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200">Handled</span>';
  const when = d => { const x = new Date(String(d).replace(' ', 'T')); return x.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' }) + ', ' + x.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' }); };
  const clock = t => { const [h, m] = t.split(':'); return `${(+h % 12) || 12}:${m} ${+h < 12 ? 'AM' : 'PM'}`; };
  const detail = i => i.type === 'table' ? `${i.event_date ? dstr(i.event_date) : ''}${i.reserve_time ? ' at ' + clock(i.reserve_time) : ''} · ${i.guests} ${i.guests == 1 ? 'guest' : 'guests'}`
    : (i.event_date ? `${dstr(i.event_date)}${i.guests ? ' · ' + i.guests + ' guests' : ''}` : '');
  let list = [];

  async function load() {
    $('#rows').innerHTML = `<tr><td colspan="5" class="p-4"><div class="skel h-32 rounded-xl"></div></td></tr>`;
    const r = await api('inquiries', 'list', st); if (!r.ok) { toast(r.error, 'err'); return; }
    list = r.inquiries; const c = r.counts, all = (+c.new || 0) + (+c.handled || 0);
    $('#tabs').innerHTML = [['new', 'New', c.new || 0], ['handled', 'Handled', c.handled || 0], ['all', 'All', all]].map(([k, l, n]) => `<button role="tab" aria-selected="${k === st.status}" data-tab="${k}" class="rounded-lg px-3 py-1.5 text-sm font-medium ${k === st.status ? 'bg-lagoon text-white' : 'text-slate-600 hover:bg-slate-100'}">${l} <span class="${k === st.status ? 'text-white/70' : 'text-slate-400'}">${n}</span></button>`).join('');
    $('#rows').innerHTML = list.length ? list.map(i => `
      <tr class="cursor-pointer hover:bg-slate-50" data-id="${i.id}" tabindex="0">
        <td class="td"><p class="font-semibold">${esc(i.name)}</p><p class="text-xs text-slate-500">${esc(i.email)}</p></td>
        <td class="td">${typeBadge(i.type)}</td>
        <td class="td max-w-xs"><p class="font-medium">${esc(detail(i))}</p><p class="truncate text-slate-500">${esc(i.message)}</p></td>
        <td class="td whitespace-nowrap text-slate-600">${when(i.created_at)}</td>
        <td class="td">${stBadge(i.status)}</td></tr>`).join('')
      : `<tr><td colspan="5" class="px-4 py-16 text-center text-sm text-slate-400">${st.q || st.type || st.status !== 'all' ? 'No messages match these filters.' : 'No messages yet. They appear here when someone uses the contact form or reserves a table.'}</td></tr>`;
    pager($('#pager'), r, p => { st.page = p; load(); });
  }

  function open(i) {
    const root = document.createElement('div'), kv = (k, v) => v ? `<div class="flex justify-between gap-4 py-2 text-sm"><dt class="text-slate-500">${k}</dt><dd class="text-right font-medium">${v}</dd></div>` : '';
    const subject = encodeURIComponent('Re: your ' + (TYPES[i.type] || 'message').toLowerCase());
    root.innerHTML = `
      <div class="flex flex-wrap items-center gap-2">${typeBadge(i.type)}${stBadge(i.status)}<span class="text-sm text-slate-500">${when(i.created_at)}</span></div>
      <div class="mt-5 flex flex-wrap gap-2">
        <a class="btn-primary" href="mailto:${esc(i.email)}?subject=${subject}">Reply by email</a>
        ${i.phone ? `<a class="btn-ghost" href="tel:${esc(i.phone)}">Call</a>` : ''}
        <button class="btn-ghost" data-st="${i.status === 'new' ? 'handled' : 'new'}">${i.status === 'new' ? 'Mark as handled' : 'Reopen'}</button>
      </div>
      <dl class="mt-6 divide-y divide-slate-100">${kv('Name', esc(i.name))}${kv('Email', `<a class="text-bronze hover:underline" href="mailto:${esc(i.email)}">${esc(i.email)}</a>`)}${kv('Phone', i.phone ? `<a class="text-bronze hover:underline" href="tel:${esc(i.phone)}">${esc(i.phone)}</a>` : '')}
        ${i.type === 'table' ? kv('Date', i.event_date ? dstr(i.event_date) : '') + kv('Time', i.reserve_time ? clock(i.reserve_time) : '') + kv('Guests', i.guests) : kv('Date', i.event_date ? dstr(i.event_date) : '') + kv('Guests', i.guests || '')}</dl>
      <h3 class="mt-6 text-sm font-semibold">Message</h3>
      <p class="mt-2 whitespace-pre-wrap rounded-xl bg-slate-50 p-4 text-sm leading-relaxed">${esc(i.message) || '<span class="text-slate-400">No message.</span>'}</p>
      <h3 class="mt-6 text-sm font-semibold">Internal notes</h3>
      <textarea id="notes" rows="3" class="input mt-2" placeholder="Only visible to staff">${esc(i.admin_notes)}</textarea>
      <div class="mt-2 flex items-center gap-2"><button id="saveNotes" class="btn-ghost btn-sm">Save notes</button><button id="del" class="btn-danger btn-sm ml-auto">Delete</button></div>`;
    drawer.open(TYPES[i.type] || 'Message', root);
    root.addEventListener('click', async e => {
      const s = e.target.closest('[data-st]');
      if (s) { const r = await api('inquiries', 'status', { id: i.id, status: s.dataset.st }); if (r.ok) { toast(s.dataset.st === 'handled' ? 'Marked as handled' : 'Reopened'); drawer.close(); load(); } else toast(r.error, 'err'); }
      if (e.target.id === 'saveNotes') { const r = await api('inquiries', 'notes', { id: i.id, admin_notes: $('#notes', root).value }); toast(r.ok ? 'Notes saved' : r.error, r.ok ? 'ok' : 'err'); }
      if (e.target.id === 'del' && await confirm('Delete this message?', 'This cannot be undone.', 'Delete')) { const r = await api('inquiries', 'delete', { id: i.id }); if (r.ok) { toast('Message deleted'); drawer.close(); load(); } else toast(r.error, 'err'); }
    });
  }

  $('#tabs').addEventListener('click', e => { const t = e.target.closest('[data-tab]'); if (t) { st.status = t.dataset.tab; st.page = 1; load(); } });
  $('#type').addEventListener('change', e => { st.type = e.target.value; st.page = 1; load(); });
  $('#q').addEventListener('input', debounce(e => { st.q = e.target.value.trim(); st.page = 1; load(); }));
  const openRow = e => { const tr = e.target.closest('[data-id]'); if (tr) open(list.find(x => x.id == tr.dataset.id)); };
  $('#rows').addEventListener('click', openRow);
  $('#rows').addEventListener('keydown', e => { if (e.key === 'Enter') openRow(e); });
  load();
})();

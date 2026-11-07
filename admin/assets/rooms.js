(() => {
  const { $, esc, money, api, toast, drawer, confirm } = A;
  const MAX = +document.querySelector('meta[name=max-images]')?.content || 10;
  const CAPTIONS = ['Living room', 'Parlor', 'Master bedroom', 'Bedroom 2', 'Bedroom 3', 'Kitchen', 'Bathroom', 'Terrace', 'Exterior', 'View'];
  const FALLBACK = 'linear-gradient(135deg,#14405F,#2E6C8A 55%,#F2A65A)';
  // Photos live in assets/img/rooms/. Seeded rooms store just the file name; photos uploaded
  // in the admin store "rooms/<file>". This handles both so the folder is never doubled or missing.
  const imgUrl = f => '../assets/img/' + (String(f).includes('/') ? f : 'rooms/' + f);
  let rooms = [];
  const coverBg = r => `background-image:${r.images?.[0] ? `url('${esc(imgUrl(r.images[0].file))}'),` : ''}${FALLBACK}`;

  async function load() {
    const r = await api('rooms', 'list'); if (!r.ok) { toast(r.error, 'err'); return; }
    rooms = r.rooms;
    $('#grid').innerHTML = rooms.length ? rooms.map(x => `
      <article class="card overflow-hidden ${+x.active ? '' : 'opacity-70'}">
        <div class="relative h-44 bg-cover bg-center" style="${coverBg(x)}">
          ${+x.featured ? '<span class="badge absolute left-3 top-3 bg-sunset text-lagoon">Featured on home</span>' : ''}
          <span class="badge absolute bottom-3 left-3 bg-lagoon/80 text-white backdrop-blur">${x.images.length} ${x.images.length === 1 ? 'photo' : 'photos'}</span>
        </div>
        <div class="p-5">
          <div class="flex items-start justify-between gap-3"><h2 class="font-semibold">${esc(x.name)}</h2>
            <label class="relative inline-flex cursor-pointer items-center" title="Show on website"><input type="checkbox" class="peer sr-only" data-toggle="${esc(x.id)}" ${+x.active ? 'checked' : ''} aria-label="Show ${esc(x.name)} on the website"><span class="h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-bronze peer-focus-visible:ring-2 peer-focus-visible:ring-sunset"></span><span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span></label></div>
          <p class="mt-1 text-sm text-slate-500">${esc(x.beds || '')}${x.beds ? ' · ' : ''}Sleeps ${x.guests} · ${x.units} ${x.units == 1 ? 'unit' : 'units'}</p>
          <p class="mt-4 text-2xl font-bold">${money(x.price)} <span class="text-sm font-medium text-slate-400">a night</span></p>
          <div class="mt-4 flex gap-4 text-xs text-slate-500"><span><strong class="text-lagoon">${x.occupied}</strong> of ${x.units} occupied tonight</span><span><strong class="text-lagoon">${x.upcoming}</strong> upcoming</span></div>
          <button class="btn-ghost mt-5 w-full" data-edit="${esc(x.id)}">Edit room and photos</button>
        </div>
      </article>`).join('') : '<p class="card col-span-full p-12 text-center text-slate-400">No rooms yet. Add your first room.</p>';
  }

  // Shrinks big phone photos in the browser (max 1800px) so uploads are fast and small.
  async function shrink(file) {
    if (!window.createImageBitmap) return file;
    try {
      const bmp = await createImageBitmap(file), s = Math.min(1, 1800 / Math.max(bmp.width, bmp.height));
      if (s === 1 && file.size < 1.5e6) { bmp.close?.(); return file; }
      const c = document.createElement('canvas'); c.width = Math.round(bmp.width * s); c.height = Math.round(bmp.height * s);
      c.getContext('2d').drawImage(bmp, 0, 0, c.width, c.height); bmp.close?.();
      const blob = await new Promise(r => c.toBlob(r, 'image/jpeg', .85));
      return blob && blob.size < file.size ? new File([blob], file.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' }) : file;
    } catch { return file; }
  }

  function form(room) {
    let roomId = room?.id || '';
    const r = room || { name: '', blurb: '', beds: '', guests: 2, units: 1, price: 0, amenities: [], featured: 0, active: 1 };
    // Gallery state: existing photos ('old') and newly chosen files ('new'); applied when the form is saved.
    let items = (room?.images || []).map(i => ({ kind: 'old', id: +i.id, caption: i.caption || '', url: imgUrl(i.file) }));
    const removed = [];

    const f = document.createElement('form'); f.className = 'space-y-4'; f.noValidate = true;
    f.innerHTML = `
      <div><label class="lbl">Name</label><input class="input" name="name" value="${esc(r.name)}" placeholder="3-bedroom flat"></div>
      <div><label class="lbl">Short description</label><textarea class="input" name="blurb" rows="2">${esc(r.blurb)}</textarea></div>
      <div class="grid grid-cols-2 gap-3"><div><label class="lbl">Beds</label><input class="input" name="beds" value="${esc(r.beds)}" placeholder="3 bedrooms"></div><div><label class="lbl">Price per night</label><input class="input" type="number" min="0" name="price" value="${r.price}"></div>
      <div><label class="lbl">Max guests</label><input class="input" type="number" min="1" name="guests" value="${r.guests}"></div><div><label class="lbl">Units available</label><input class="input" type="number" min="1" name="units" value="${r.units}"></div></div>
      <div><label class="lbl">Amenities (one per line)</label><textarea class="input" name="amenities" rows="3">${esc(r.amenities.join('\n'))}</textarea></div>

      <div>
        <div class="flex items-center justify-between"><label class="lbl !mb-0">Photos</label><span id="cnt" class="text-xs text-slate-400"></span></div>
        <label id="drop" class="mt-2 flex cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border-2 border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500 transition focus-within:border-bronze hover:border-bronze hover:bg-bronze/5">
          <span><strong class="text-lagoon">Choose photos</strong> or drop them here</span>
          <span class="text-xs">Select several at once: parlor, bedrooms, kitchen, bathroom. JPG, PNG or WebP, up to ${MAX} photos.</span>
          <input type="file" id="pick" class="sr-only" accept="image/jpeg,image/png,image/webp" multiple>
        </label>
        <p id="photoMsg" class="mt-2 min-h-5 text-xs text-red-700" role="status" aria-live="polite"></p>
        <div id="tiles" class="mt-2 grid grid-cols-2 gap-3 sm:grid-cols-3"></div>
        <datalist id="caps">${CAPTIONS.map(c => `<option value="${c}">`).join('')}</datalist>
        <p class="mt-2 text-xs text-slate-500">The first photo is the cover. Use the arrows to change the order and add a caption to each photo.</p>
      </div>

      <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="active" value="1" ${+r.active ? 'checked' : ''} class="h-4 w-4 accent-[#A8743B]"> Show on the website</label>
      <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="featured" value="1" ${+r.featured ? 'checked' : ''} class="h-4 w-4 accent-[#A8743B]"> Feature on the home page (only one room)</label>
      <div class="flex flex-wrap items-center gap-3 pt-2"><button class="btn-primary">Save room</button>${room ? '<button type="button" class="btn-danger" data-del>Delete room</button>' : ''}<p class="text-sm text-red-700" data-err role="status" aria-live="polite"></p></div>`;

    const tiles = $('#tiles', f), msg = $('#photoMsg', f), status = $('[data-err]', f);
    const renderTiles = () => {
      $('#cnt', f).textContent = `${items.length} of ${MAX}`;
      $('#pick', f).disabled = items.length >= MAX;
      tiles.innerHTML = items.map((it, i) => `
        <div class="overflow-hidden rounded-xl border ${it.error ? 'border-red-300' : 'border-slate-200'} bg-white" data-i="${i}">
          <div class="relative">
            <img src="${esc(it.url)}" alt="Photo ${i + 1}${it.caption ? ': ' + esc(it.caption) : ''}" class="aspect-[4/3] w-full object-cover">
            ${i === 0 ? '<span class="badge absolute left-2 top-2 bg-sunset text-lagoon">Cover</span>' : ''}
            ${it.kind === 'new' ? '<span class="badge absolute right-2 top-2 bg-lagoon/80 text-white">New</span>' : ''}
          </div>
          <div class="space-y-2 p-2">
            ${it.error ? `<p class="text-xs text-red-700">${esc(it.error)}</p>` : ''}
            <input class="input !py-1.5 text-xs" list="caps" data-cap value="${esc(it.caption)}" placeholder="Caption, e.g. Parlor" maxlength="60" aria-label="Caption for photo ${i + 1}">
            <div class="flex items-center justify-between">
              <div class="flex gap-1"><button type="button" class="btn-ghost btn-sm" data-mv="-1" ${i === 0 ? 'disabled' : ''} aria-label="Move photo ${i + 1} earlier">&lsaquo;</button><button type="button" class="btn-ghost btn-sm" data-mv="1" ${i === items.length - 1 ? 'disabled' : ''} aria-label="Move photo ${i + 1} later">&rsaquo;</button></div>
              <button type="button" class="btn-danger btn-sm" data-rm>Remove</button>
            </div>
          </div>
        </div>`).join('');
    };
    renderTiles();

    const add = files => {
      let note = '';
      for (const file of files) {
        if (items.length >= MAX) { note = `Only ${MAX} photos are allowed per room.`; break; }
        if (!/^image\/(jpeg|png|webp)$/.test(file.type)) { note = `${file.name}: use a JPG, PNG or WebP photo.`; continue; }
        if (file.size > 40 * 1048576) { note = `${file.name} is too large (over 40 MB).`; continue; }
        items.push({ kind: 'new', file, caption: '', url: URL.createObjectURL(file) });
      }
      msg.textContent = note; renderTiles();
    };
    $('#pick', f).addEventListener('change', e => { add([...e.target.files]); e.target.value = ''; });
    const drop = $('#drop', f);
    ['dragenter', 'dragover'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.add('border-bronze', 'bg-bronze/5'); }));
    ['dragleave', 'drop'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.remove('border-bronze', 'bg-bronze/5'); }));
    drop.addEventListener('drop', e => add([...e.dataTransfer.files]));

    tiles.addEventListener('click', e => {
      const tile = e.target.closest('[data-i]'); if (!tile) return; const i = +tile.dataset.i;
      const mv = e.target.closest('[data-mv]');
      if (mv) { const j = i + +mv.dataset.mv; [items[i], items[j]] = [items[j], items[i]]; renderTiles(); }
      if (e.target.closest('[data-rm]')) { const [it] = items.splice(i, 1); it.kind === 'old' ? removed.push(it.id) : URL.revokeObjectURL(it.url); renderTiles(); }
    });
    tiles.addEventListener('input', e => { const tile = e.target.closest('[data-i]'); if (tile && e.target.matches('[data-cap]')) items[+tile.dataset.i].caption = e.target.value; });

    f.onsubmit = async e => {
      e.preventDefault(); const btn = $('button', f); btn.disabled = true; status.className = 'text-sm text-slate-500'; status.textContent = 'Saving room…';
      const fd = new FormData(); for (const k of ['name', 'blurb', 'beds', 'price', 'guests', 'units', 'amenities']) fd.set(k, f[k].value);
      fd.set('id', roomId); fd.set('active', f.active.checked ? 1 : 0); fd.set('featured', f.featured.checked ? 1 : 0);
      const saved = await api('rooms', 'save', fd);
      if (!saved.ok) { status.className = 'text-sm text-red-700'; status.textContent = saved.error; btn.disabled = false; return; }
      roomId = saved.id;

      for (const id of removed.splice(0)) await api('rooms', 'image_delete', { id });
      const fresh = items.filter(i => i.kind === 'new'); let done = 0, failed = 0;
      for (const it of fresh) {
        status.textContent = `Uploading photo ${++done} of ${fresh.length}…`;
        const blob = await shrink(it.file), up = new FormData();
        up.set('room_id', roomId); up.set('caption', it.caption); up.set('image', blob, blob.name || it.file.name);
        const r = await api('rooms', 'upload', up);
        if (r.ok) { it.kind = 'old'; it.id = r.id; delete it.error; } else { it.error = r.error || 'Upload failed.'; failed++; }
      }
      const kept = items.filter(i => i.kind === 'old');
      if (kept.length) await api('rooms', 'images_save', { room_id: roomId, order: JSON.stringify(kept.map(i => ({ id: i.id, caption: i.caption }))) });
      btn.disabled = false; load();
      if (failed) { renderTiles(); status.className = 'text-sm text-red-700'; status.textContent = `${failed} ${failed === 1 ? 'photo' : 'photos'} could not be uploaded. Fix or remove ${failed === 1 ? 'it' : 'them'} and save again.`; }
      else { toast('Room saved'); drawer.close(); }
    };

    $('[data-del]', f)?.addEventListener('click', async () => {
      if (!(await confirm('Delete this room?', 'The room and all its photos are removed. Existing bookings for this room are kept.', 'Delete'))) return;
      const x = await api('rooms', 'delete', { id: room.id });
      if (x.ok) { toast('Room deleted'); drawer.close(); load(); } else toast(x.error, 'err');
    });
    return f;
  }

  $('#addRoom').addEventListener('click', () => drawer.open('Add room', form()));
  $('#grid').addEventListener('click', e => { const b = e.target.closest('[data-edit]'); if (b) drawer.open('Edit room', form(rooms.find(x => x.id === b.dataset.edit))); });
  $('#grid').addEventListener('change', async e => {
    const t = e.target.closest('[data-toggle]'); if (!t) return;
    const x = await api('rooms', 'toggle', { id: t.dataset.toggle, active: t.checked ? 1 : 0 });
    toast(x.ok ? (t.checked ? 'Room is now on the website' : 'Room hidden from the website') : x.error, x.ok ? 'ok' : 'err'); load();
  });
  load();
})();
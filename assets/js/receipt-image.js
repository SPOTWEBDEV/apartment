// Draws the booking receipt on a canvas so guests can save it as a PNG straight after booking.
window.CLReceipt = (() => {
  const NAVY = '#0B2A43', GRAY = '#64748B', BRONZE = '#A8743B', LINE = '#E2E8F0', TINT = '#EEF4F3';
  const FONT = 'Figtree, system-ui, -apple-system, "Segoe UI", sans-serif';
  const W = 900, PAD = 56;

  const loadImg = src => new Promise(res => { const i = new Image(); i.onload = () => res(i); i.onerror = () => res(null); i.src = src; });
  const fmtDate = d => new Date(d + 'T00:00:00').toLocaleDateString('en-PH', { day: 'numeric', month: 'short', year: 'numeric' });

  async function render(d, site) {
    if (document.fonts?.ready) { try { await document.fonts.ready; } catch {} }
    const logo = await loadImg('assets/img/logo-receipt.jpg');
    const money = n => (n < 0 ? '-' : '') + site.currency + Math.abs(Math.round(n)).toLocaleString('en-PH') + '.00';
    const scale = 2, tall = document.createElement('canvas');
    tall.width = W * scale; tall.height = 1800 * scale;
    const c = tall.getContext('2d'); c.scale(scale, scale);
    c.fillStyle = '#fff'; c.fillRect(0, 0, W, 1800); c.textBaseline = 'alphabetic';

    const t = (x, y, s, { size = 16, weight = 400, color = NAVY, align = 'left' } = {}) => { c.font = `${weight} ${size}px ${FONT}`; c.fillStyle = color; c.textAlign = align; c.fillText(s, x, y); };
    const hr = y => { c.strokeStyle = LINE; c.lineWidth = 1.5; c.beginPath(); c.moveTo(PAD, y); c.lineTo(W - PAD, y); c.stroke(); };
    const wrap = (s, x, y, maxW, o) => { let line = ''; for (const w of s.split(' ')) { const test = line ? line + ' ' + w : w; c.font = `${o.weight || 400} ${o.size}px ${FONT}`; if (c.measureText(test).width > maxW && line) { t(x, y, line, o); y += o.size * 1.5; line = w; } else line = test; } if (line) { t(x, y, line, o); y += o.size * 1.5; } return y; };

    if (logo) c.drawImage(logo, PAD, 44, 150, 150 * logo.height / logo.width);
    t(W - PAD, 76, 'BOOKING RECEIPT', { size: 26, weight: 700, align: 'right' });
    t(W - PAD, 104, 'Issued ' + new Date().toLocaleDateString('en-PH', { day: 'numeric', month: 'short', year: 'numeric' }), { size: 15, color: GRAY, align: 'right' });
    hr(150);

    let yl = 190, yr = 190;
    t(PAD, yl, 'FROM', { size: 13, weight: 700, color: BRONZE }); t(470, yr, 'BILLED TO', { size: 13, weight: 700, color: BRONZE });
    yl += 28; t(PAD, yl, site.name, { size: 18, weight: 700 });
    for (const a of site.address) { yl += 23; t(PAD, yl, a, { size: 15, color: GRAY }); }
    for (const v of [site.phone, site.email]) { yl += 23; t(PAD, yl, v, { size: 15, color: GRAY }); }
    yr += 28; t(470, yr, d.name, { size: 18, weight: 700 });
    for (const v of [d.email, d.phone]) if (v) { yr += 23; t(470, yr, v, { size: 15, color: GRAY }); }

    let y = Math.max(yl, yr) + 36;
    c.fillStyle = TINT; c.beginPath(); c.roundRect(PAD, y, W - 2 * PAD, 84, 14); c.fill();
    [['REFERENCE', d.ref, PAD + 24], ['BOOKING STATUS', d.status_label, 340], ['PAYMENT', d.balance > 0 ? (d.paid > 0 ? 'Partly paid' : 'Unpaid') : 'Paid in full', 610]].forEach(([k, v, x]) => {
      t(x, y + 32, k, { size: 12, weight: 700, color: GRAY }); t(x, y + 62, v, { size: 19, weight: 700 }); });

    y += 134; t(PAD, y, 'STAY', { size: 13, weight: 700, color: BRONZE }); y += 10;
    for (const [k, v] of [['Room', d.room], ['Check-in', fmtDate(d.checkin) + (site.check_in ? ' from ' + site.check_in : '')], ['Check-out', fmtDate(d.checkout) + (site.check_out ? ' by ' + site.check_out : '')], ['Nights', String(d.nights)], ['Guests', String(d.guests)]]) {
      y += 28; t(PAD, y, k, { size: 16, color: GRAY }); t(230, y, v, { size: 16, weight: 700 }); }

    y += 52; c.fillStyle = NAVY; c.beginPath(); c.roundRect(PAD, y, W - 2 * PAD, 42, 10); c.fill();
    t(PAD + 18, y + 27, 'Description', { size: 15, weight: 700, color: '#fff' }); t(W - PAD - 18, y + 27, 'Amount', { size: 15, weight: 700, color: '#fff', align: 'right' });
    y += 42; const rate = Math.round(d.subtotal / Math.max(1, d.nights));
    y += 34; t(PAD + 18, y, `${d.room}  x ${d.nights} ${d.nights === 1 ? 'night' : 'nights'} at ${money(rate)}`, { size: 16 }); t(W - PAD - 18, y, money(d.subtotal), { size: 16, align: 'right' });
    if (d.discount_amount > 0) { y += 32; t(PAD + 18, y, `Discount (${d.discount_code})`, { size: 16, color: '#047857' }); t(W - PAD - 18, y, money(-d.discount_amount), { size: 16, color: '#047857', align: 'right' }); }
    y += 20; hr(y); y += 36; t(PAD + 18, y, 'Total', { size: 20, weight: 700 }); t(W - PAD - 18, y, money(d.total), { size: 20, weight: 700, align: 'right' });

    y += 58; t(PAD, y, 'PAYMENTS RECEIVED', { size: 13, weight: 700, color: BRONZE }); y += 34;
    t(PAD, y, 'No payments recorded yet.', { size: 16, color: GRAY });
    y += 24; hr(y); y += 34; t(PAD, y, 'Amount paid', { size: 17, weight: 700 }); t(W - PAD, y, money(d.paid), { size: 17, weight: 700, color: '#047857', align: 'right' });
    y += 34; t(PAD, y, 'Balance due', { size: 19, weight: 700 }); t(W - PAD, y, money(d.balance), { size: 19, weight: 700, color: d.balance > 0 ? '#B91C1C' : NAVY, align: 'right' });

    y += 44; hr(y); y += 30;
    y = wrap('Your booking request has been received. The reservations team confirms by email or phone, and your stay is not guaranteed until it is confirmed. This is a booking receipt and not an official tax invoice. Keep your reference and the emailed receipt link.', PAD, y, W - 2 * PAD, { size: 14, color: GRAY });
    y += 10; t(W / 2, y + 6, `${site.name}  |  ${site.address.join(', ')}  |  ${site.email}`, { size: 13, color: GRAY, align: 'center' });
    const H = Math.ceil(y + 40);

    const out = document.createElement('canvas'); out.width = W * scale; out.height = H * scale;
    out.getContext('2d').drawImage(tall, 0, 0, W * scale, H * scale, 0, 0, W * scale, H * scale);
    return new Promise(res => out.toBlob(res, 'image/png'));
  }
  return { render };
})();

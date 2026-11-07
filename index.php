<?php
require __DIR__ . '/includes/bootstrap.php';
$data = require __DIR__ . '/includes/data.php';
$solid = false; $active = 'home';
$hasRooms = (bool)rooms_all(true);
include __DIR__ . '/includes/header.php';
?>
<main id="top">

<!-- Hero -->
<section data-hero class="relative flex min-h-[100svh] items-end overflow-hidden bg-lagoon text-white">
  <div data-hero-img class="absolute inset-0">
  <?php foreach ([
    ['garden-villa.jpg', 'Modern villas around the pool in daylight', '50% 50%'],
    ['house-night.jpeg', 'Villa and pool lit up at night',            '50% 35%'],
  ] as $i => [$file, $alt, $pos]): ?>
    <img src="assets/img/<?= $file ?>" alt="<?= e($alt) ?>"
         class="hero-slide absolute inset-0 h-full w-full object-cover<?= $i === 0 ? ' is-active' : '' ?>"
         style="object-position:<?= $pos ?>"
         <?= $i === 0 ? 'fetchpriority="high"' : '' ?>>
  <?php endforeach; ?>
</div>
  <div class="absolute inset-0 bg-gradient-to-b from-lagoon/60 via-lagoon/10 to-lagoon/90"></div>
  <div class="relative mx-auto w-full max-w-7xl px-5 pb-10 pt-40 lg:px-8 lg:pb-14">
    <h1 data-hero-title class="max-w-3xl font-display text-5xl leading-[1.05] sm:text-6xl lg:text-7xl">A quiet cove, two and a half hours from Manila.</h1>
    <p data-hero-sub class="mt-5 max-w-xl text-lg text-white/85">Beachfront casitas, two pools and a balsa raft at sunset, in San Juan, Batangas. Bring the family, and the dog.</p>

    <?php if ($hasRooms): ?>
    <form id="book" data-hero-form class="glass mt-10 grid gap-3 rounded-3xl p-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_.7fr_auto]" novalidate>
      <label class="block rounded-2xl bg-white/90 px-4 py-2.5 text-lagoon"><span class="block text-xs font-medium text-lagoon/70">Check-in</span>
        <input type="date" name="checkin" required class="w-full bg-transparent font-medium focus:outline-none"></label>
      <label class="block rounded-2xl bg-white/90 px-4 py-2.5 text-lagoon"><span class="block text-xs font-medium text-lagoon/70">Check-out</span>
        <input type="date" name="checkout" required class="w-full bg-transparent font-medium focus:outline-none"></label>
      <label class="block rounded-2xl bg-white/90 px-4 py-2.5 text-lagoon"><span class="block text-xs font-medium text-lagoon/70">Guests</span>
        <input type="number" name="guests" min="1" max="30" value="2" class="w-full bg-transparent font-medium focus:outline-none"></label>
      <button class="rounded-2xl bg-sunset px-8 py-4 font-semibold text-lagoon transition hover:bg-white disabled:opacity-60 sm:col-span-2 lg:col-span-1">Check availability</button>
    </form>
    <p id="bookMsg" class="mt-3 min-h-6 text-sm text-white/90" role="status" aria-live="polite"></p>
    <?php else: ?>
    <a href="contact.php" class="rise mt-10 inline-block rounded-full bg-sunset px-8 py-4 font-semibold text-lagoon hover:bg-white">Contact reservations</a>
    <?php endif; ?>
  </div>
</section>

<!-- Availability results (AJAX) -->
<section id="results" class="hidden bg-foam py-12" aria-live="polite">
  <div class="mx-auto max-w-7xl px-5 lg:px-8">
    <h2 id="resultsTitle" class="font-display text-3xl"></h2>
    <div id="resultsList" class="mt-6 grid gap-4 md:grid-cols-3"></div>
  </div>
</section>

<!-- Facts -->
<section class="mx-auto max-w-7xl px-5 py-16 lg:px-8">
  <dl class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
    <?php foreach ([['2.5 hours','South of Manila'],['2 pools','Both clean and new'],['Pet friendly','Grass, sand and open grounds'],['Free balsa raft','Included for every guest']] as [$k,$v]): ?>
      <div data-reveal class="border-l-2 border-bronze pl-4"><dt class="font-display text-2xl"><?= e($k) ?></dt><dd class="mt-1 text-lagoon/70"><?= e($v) ?></dd></div>
    <?php endforeach; ?>
  </dl>
</section>

<!-- About teaser -->
<section class="mx-auto grid max-w-7xl items-center gap-12 px-5 pb-24 lg:grid-cols-2 lg:px-8">
  <div data-reveal>
    <h2 class="font-display text-4xl sm:text-5xl">A tropical sanctuary, left as it was</h2>
    <p class="mt-5 max-w-lg text-lg text-lagoon/80"><?= e(cfg('site_name')) ?> sits in a cove in Laiya Ibabao, with open lawns, two swimming pools and a beachfront restaurant. It is built for slow weekends, family reunions and events that need space.</p>
    <a href="about.php" class="mt-8 inline-block font-semibold text-bronze underline underline-offset-4 hover:text-lagoon">Read our story</a>
  </div>
  <img data-clip src="assets/img/pool.jpg" alt="Swimming pool with sun loungers and a villa behind" class="aspect-[4/3] w-full rounded-3xl object-cover" loading="lazy">
</section>

<!-- Rooms -->
<section id="rooms" class="mx-auto max-w-7xl px-5 pb-24 lg:px-8">
  <div class="flex flex-wrap items-end justify-between gap-4">
    <h2 class="max-w-xl font-display text-4xl sm:text-5xl">Where you will stay</h2>
    <a href="rooms.php#booking" class="font-medium text-bronze underline underline-offset-4 hover:text-lagoon">See all rooms and book</a>
  </div>
  <?php if (!$hasRooms): ?><p class="mt-10 rounded-3xl bg-foam p-10 text-lagoon/80">Our rooms are being added. <a href="contact.php" class="font-semibold text-bronze underline underline-offset-4">Contact reservations</a> to book your stay.</p><?php else: ?>
  <div class="mt-10 grid gap-6 lg:grid-cols-5">
    <?php foreach (rooms_all(true) as $r): $feat = !empty($r['featured']); ?>
    <article data-reveal class="overflow-hidden rounded-3xl bg-white shadow-sm <?= $feat ? 'lg:col-span-3 lg:row-span-2' : 'lg:col-span-2' ?>">
      <div class="ph <?= $feat ? 'aspect-[4/3] lg:aspect-auto lg:h-[26rem]' : 'aspect-[16/9]' ?> relative bg-cover bg-center" style="background-image:url('<?= e(room_img($r['img'])) ?>'),linear-gradient(135deg,#14405F,#2E6C8A 55%,#F2A65A)" role="img" aria-label="<?= e($r['name']) ?>">
        <?php if (count($r['images'] ?? []) > 1): ?><a href="rooms.php#<?= e($r['id']) ?>" class="absolute bottom-3 left-3 rounded-full bg-lagoon/75 px-3 py-1 text-xs font-semibold text-white backdrop-blur hover:bg-lagoon"><?= count($r['images']) ?> photos</a><?php endif; ?>
      </div>
      <div class="p-6">
        <div class="flex items-baseline justify-between gap-4">
          <h3 class="font-display text-2xl"><?= e($r['name']) ?></h3>
          <p class="whitespace-nowrap text-sm text-lagoon/70">from <strong class="text-lagoon"><?= peso($r['price']) ?></strong> a night</p>
        </div>
        <p class="mt-2 text-lagoon/75"><?= e($r['blurb']) ?> Sleeps up to <?= (int)$r['guests'] ?>.</p>
        <a href="room.php?room=<?= e($r['id']) ?>#booking" class="mt-4 inline-block font-semibold text-bronze underline underline-offset-4 hover:text-lagoon">View room and book</a>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<!-- Experience rail -->
<section id="experience" class="bg-lagoon py-20 text-white">
  <div data-reveal class="mx-auto max-w-7xl px-5 lg:px-8">
    <h2 class="max-w-2xl font-display text-4xl sm:text-5xl">Days that start on the water</h2>
    <p class="mt-4 max-w-xl text-white/75">Most activities are a few steps from your door. Ask the front desk to book boat tours the day before.</p>
    <a href="experience.php" class="mt-5 inline-block font-semibold text-sunset underline underline-offset-4 hover:text-white">See all activities</a>
  </div>
  <ul class="mx-auto mt-10 flex max-w-7xl snap-x gap-5 overflow-x-auto px-5 pb-4 lg:px-8" tabindex="0" aria-label="Activities">
    <?php foreach ($data['experiences'] as [$t,$d,$img]): ?>
    <li class="min-w-[16rem] max-w-[16rem] shrink-0 snap-start rounded-3xl border border-white/15 bg-white/5 p-6">
      <div class="ph mb-5 h-36 rounded-2xl bg-cover bg-center" <?php if ($img): ?>style="background-image:url('assets/img/<?= e($img) ?>'),linear-gradient(135deg,#14405F,#F2A65A)" role="img" aria-label="<?= e($t) ?>"<?php endif; ?>></div>
      <h3 class="font-display text-xl"><?= e($t) ?></h3>
      <p class="mt-2 text-sm text-white/70"><?= e($d) ?></p>
    </li>
    <?php endforeach; ?>
  </ul>
</section>

<!-- Dining -->
<section id="dining" class="mx-auto grid max-w-7xl items-center gap-12 px-5 py-24 lg:grid-cols-2 lg:px-8">
  <div class="grid grid-cols-2 gap-3">
    <img data-clip src="assets/img/pizza.jpg" alt="Tomato and basil pizza on a wooden board" class="col-span-2 aspect-[16/10] w-full rounded-3xl object-cover" style="object-position:50% 70%" loading="lazy">
    <img src="assets/img/steak.jpg" alt="Seared steak with garlic butter, green beans and roasted garlic in a cast iron pan" class="aspect-square w-full rounded-3xl object-cover" loading="lazy">
    <img src="assets/img/dining-table.jpg" alt="Hardwood dining table and bar stools set with wine glasses in the evening light" class="aspect-square w-full rounded-3xl object-cover" loading="lazy">
  </div>
  <div data-reveal>
    <h2 class="font-display text-4xl sm:text-5xl">Steak, pizza and long dinners under the trees</h2>
    <p class="mt-5 max-w-lg text-lg text-lagoon/80">Dinner is unhurried: seared steak with garlic butter, tomato and basil pizza, and wine at a long hardwood table. Start the day with Filipino breakfast, and for groups the seafood boodle fight is the one to book.</p>
    <div class="mt-8 flex flex-wrap gap-3"><a href="dining.php" class="rounded-full bg-lagoon px-7 py-3 font-semibold text-white hover:bg-tide">See dining</a><a href="contact.php?type=table" class="rounded-full border border-lagoon/25 px-7 py-3 font-semibold hover:bg-lagoon hover:text-white">Reserve a table</a></div>
  </div>
</section>

<!-- Events -->
<section id="events" class="mx-auto max-w-7xl px-5 pb-24 lg:px-8">
  <div class="grid gap-6 md:grid-cols-2">
    <?php foreach ([['Weddings','Ceremony on the sand, reception under the trees, and casitas for the whole party.','wedding'],['Corporate events','Retreats and team days with a meeting room built around a long hardwood table, plus archery and boat tours after.','corporate']] as [$t,$d,$k]): ?>
    <article data-reveal class="rounded-3xl bg-foam p-8 lg:p-10">
      <h3 class="font-display text-3xl"><?= $t ?></h3>
      <p class="mt-3 max-w-md text-lagoon/80"><?= $d ?></p>
      <a href="events.php?type=<?= $k ?>#inquire" class="mt-6 inline-block font-semibold text-bronze underline underline-offset-4 hover:text-lagoon">Ask about <?= strtolower($t) ?></a>
    </article>
    <?php endforeach; ?>
  </div>
</section>

<!-- Reviews (AJAX carousel) -->
<section id="reviews" class="bg-foam py-20">
  <div class="mx-auto max-w-7xl px-5 lg:px-8">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div data-reveal><h2 class="font-display text-4xl sm:text-5xl">What guests say</h2><p id="revAvg" class="mt-2 text-lagoon/70" aria-live="polite"></p></div>
      <div class="flex gap-2">
        <button id="revPrev" class="rounded-full border border-lagoon/25 p-3 hover:bg-lagoon hover:text-white" aria-label="Previous reviews"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4 6 10l6 6"/></svg></button>
        <button id="revNext" class="rounded-full border border-lagoon/25 p-3 hover:bg-lagoon hover:text-white" aria-label="Next reviews"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="m8 4 6 6-6 6"/></svg></button>
      </div>
    </div>
    <div id="reviewList" class="mt-10 flex snap-x gap-6 overflow-x-auto pb-4" aria-live="polite" tabindex="0">
      <?php for ($i=0;$i<3;$i++): ?><div class="h-64 min-w-[85%] animate-pulse rounded-3xl bg-white/70 md:min-w-[calc(33.333%-1rem)]"></div><?php endfor; ?>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="mx-auto max-w-7xl px-5 py-24 lg:px-8">
  <div data-reveal class="rounded-[2rem] bg-lagoon px-8 py-14 text-center text-white sm:px-16">
    <h2 class="mx-auto max-w-2xl font-display text-4xl sm:text-5xl">Planning a family weekend, wedding or retreat?</h2>
    <p class="mx-auto mt-4 max-w-lg text-white/75">Send us the dates and the size of your group. We reply by email, usually within a day.</p>
    <div class="mt-8 flex flex-wrap justify-center gap-3">
      <a href="contact.php" class="rounded-full bg-sunset px-7 py-3 font-semibold text-lagoon hover:bg-white">Contact reservations</a>
      <?php if (messenger_url()): ?><a href="<?= e(messenger_url()) ?>" rel="noopener" class="rounded-full border border-white/30 px-7 py-3 font-semibold hover:bg-white hover:text-lagoon">Message on Messenger</a><?php endif; ?>
    </div>
  </div>
</section>
</main>

<?php
// Promotions pop-up: the price saved on each room is the 50% price, so the original price is double.
if (!function_exists('promo_type')) {
  function promo_type(string $n): string {
    foreach (['Villa', 'Suite', 'Family'] as $k) if (stripos($n, $k) !== false) return $k;
    return strtok($n, ' ') ?: 'Room';
  }
}
$promoRooms = $hasRooms ? rooms_all(true) : [];
if ($promoRooms): ?>
<style>
  .promo{position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;padding:1rem}
  .promo[hidden]{display:none}
  .promo-backdrop{position:absolute;inset:0;background:rgba(5,13,31,.65);backdrop-filter:blur(2px)}
  .promo-panel{position:relative;display:flex;flex-direction:column;width:100%;max-width:27.5rem;max-height:min(85vh,40rem);overflow:hidden;border-radius:1.75rem;background:#F6F4EE;box-shadow:0 30px 80px -20px rgba(0,0,0,.6);animation:promoIn .35s ease both}
  .promo-head{position:relative;padding:1.75rem 1.5rem 1.4rem;text-align:center;color:#fff;background:linear-gradient(135deg,#14405F,#2E6C8A)}
  .promo-brand{margin:0;font-size:.65rem;letter-spacing:.3em;text-transform:uppercase;opacity:.75}
  .promo-head h2{margin:.5rem 0 0;font-size:1.75rem;line-height:1.15}
  .promo-x{position:absolute;top:1rem;right:1rem;width:2.25rem;height:2.25rem;border:0;border-radius:9999px;background:rgba(255,255,255,.85);color:#14405F;font-size:1.3rem;line-height:1;cursor:pointer}
  .promo-x:hover{background:#fff}
  .promo-body{overflow-y:auto;padding:1.1rem 1.25rem}
  .promo-tag{display:flex;align-items:center;gap:.4rem;margin:0 0 .75rem;font-weight:600;color:#14405F}
  .promo-body ul{display:grid;gap:.6rem;margin:0;padding:0;list-style:none}
  .promo-item{display:flex;align-items:center;gap:.75rem;padding:.6rem;border-radius:.9rem;background:#fff;color:#14405F;text-decoration:none;transition:box-shadow .2s}
  .promo-item:hover,.promo-item:focus-visible{box-shadow:0 6px 18px -8px rgba(20,64,95,.4)}
  .promo-thumb{flex:none;width:4rem;height:3.5rem;border-radius:.4rem;background-size:cover;background-position:center}
  .promo-info{flex:1;min-width:0}
  .promo-type{display:block;font-size:.75rem;color:#2E6C8A}
  .promo-name{display:block;margin:.1rem 0 .25rem;overflow:hidden;font-weight:600;text-overflow:ellipsis;white-space:nowrap}
  .promo-old{font-size:.72rem;color:#8a97a3;text-decoration:line-through}
  .promo-new{margin:0 .35rem;font-size:1rem;font-weight:700}
  .promo-off{font-size:.7rem;font-weight:600;color:#dc2626}
  .promo-go{flex:none;display:grid;place-items:center;width:2rem;height:2.5rem;border-radius:.5rem;background:#E3EEF0}
  .promo-foot{padding:.9rem;text-align:center;border-top:1px solid rgba(20,64,95,.12)}
  .promo-foot a{font-size:.8rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#14405F;text-decoration:none}
  .promo-foot a:hover{color:#A8743B}
  @keyframes promoIn{from{opacity:0;transform:translateY(14px) scale(.98)}to{opacity:1;transform:none}}
  @media (prefers-reduced-motion:reduce){.promo-panel{animation:none}}
</style>
<div id="promo" class="promo" hidden role="dialog" aria-modal="true" aria-labelledby="promoTitle">
  <div class="promo-backdrop" data-close></div>
  <div class="promo-panel">
    <header class="promo-head">
      <p class="promo-brand"><?= e(cfg('site_name')) ?></p>
      <h2 id="promoTitle" class="font-display">Current Promotions</h2>
      <button type="button" class="promo-x" data-close aria-label="Close promotions">&times;</button>
    </header>
    <div class="promo-body">
      <p class="promo-tag"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/></svg>50% OFF</p>
      <ul>
        <?php foreach ($promoRooms as $r):
          $thumb = !empty($r['img']) ? "url('" . e(room_img($r['img'])) . "')," : ''; ?>
        <li>
          <a class="promo-item" href="room.php?room=<?= e($r['id']) ?>#booking">
            <span class="promo-thumb" style="background-image:<?= $thumb ?>linear-gradient(135deg,#14405F,#2E6C8A 55%,#F2A65A)" role="img" aria-label="<?= e($r['name']) ?>"></span>
            <span class="promo-info">
              <span class="promo-type"><?= e(promo_type($r['name'])) ?></span>
              <span class="promo-name"><?= e($r['name']) ?></span>
              <span><s class="promo-old"><?= peso($r['price'] * 2) ?></s><strong class="promo-new"><?= peso($r['price']) ?></strong><span class="promo-off">-50%</span></span>
            </span>
            <span class="promo-go" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></span>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="promo-foot"><a href="rooms.php#booking">View all rooms &rarr;</a></div>
  </div>
</div>
<script>
(function () {
  var modal = document.getElementById('promo'); if (!modal) return;
  var KEY = 'promoSeen', last = null;
  function focusables() { return modal.querySelectorAll('a[href],button'); }
  function open() {
    last = document.activeElement; modal.hidden = false; document.body.style.overflow = 'hidden';
    var x = modal.querySelector('.promo-x'); if (x) x.focus();
  }
  function close() {
    modal.hidden = true; document.body.style.overflow = '';
    try { sessionStorage.setItem(KEY, '1'); } catch (e) {}
    if (last && last.focus) last.focus();
  }
  modal.addEventListener('click', function (e) { if (e.target.closest('[data-close]')) close(); });
  document.addEventListener('keydown', function (e) {
    if (modal.hidden) return;
    if (e.key === 'Escape') { close(); return; }
    if (e.key === 'Tab') {
      var f = focusables(); if (!f.length) return;
      var first = f[0], lastEl = f[f.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); lastEl.focus(); }
      else if (!e.shiftKey && document.activeElement === lastEl) { e.preventDefault(); first.focus(); }
    }
  });
  // Shows once per visit. To show it on every page load, delete the sessionStorage check.
  var seen = false; try { seen = !!sessionStorage.getItem(KEY); } catch (e) {}
  if (!seen) setTimeout(open, 1200);
})();
</script>
<?php endif; ?>

<script>
(function () {
  var slides = document.querySelectorAll('[data-hero] .hero-slide');
  if (slides.length < 2 || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  var i = 0;
  setInterval(function () {
    if (document.hidden) return;
    slides[i].classList.remove('is-active');
    i = (i + 1) % slides.length;
    slides[i].classList.add('is-active');
  }, 6000);
})();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
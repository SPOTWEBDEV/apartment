<?php
require __DIR__ . '/includes/bootstrap.php';
$data = require __DIR__ . '/includes/data.php';
$solid = false; $active = 'home';
include __DIR__ . '/includes/header.php';
?>
<main id="top">

<!-- Hero -->
<section data-hero class="relative flex min-h-[100svh] items-end overflow-hidden bg-lagoon text-white">
  <img data-hero-img src="assets/img/hero.jpg" alt="Balsa raft on the cove at sunset" class="absolute inset-0 h-full w-full object-cover" fetchpriority="high">
  <div class="absolute inset-0 bg-gradient-to-b from-lagoon/60 via-lagoon/10 to-lagoon/90"></div>
  <div class="relative mx-auto w-full max-w-7xl px-5 pb-10 pt-40 lg:px-8 lg:pb-14">
    <h1 data-hero-title class="max-w-3xl font-display text-5xl leading-[1.05] sm:text-6xl lg:text-7xl">A quiet cove, two and a half hours from Manila.</h1>
    <p data-hero-sub class="mt-5 max-w-xl text-lg text-white/85">Beachfront casitas, two pools and a balsa raft at sunset, in San Juan, Batangas. Bring the family, and the dog.</p>

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
    <a href="#book" class="font-medium text-bronze underline underline-offset-4 hover:text-lagoon">Check dates for any room</a>
  </div>
  <div class="mt-10 grid gap-6 lg:grid-cols-5">
    <?php foreach ($data['rooms'] as $r): $feat = !empty($r['featured']); ?>
    <article data-reveal class="overflow-hidden rounded-3xl bg-white shadow-sm <?= $feat ? 'lg:col-span-3 lg:row-span-2' : 'lg:col-span-2' ?>">
      <div class="ph <?= $feat ? 'aspect-[4/3] lg:aspect-auto lg:h-[26rem]' : 'aspect-[16/9]' ?> bg-cover bg-center" style="background-image:url('assets/img/<?= e($r['img']) ?>'),linear-gradient(135deg,#14405F,#2E6C8A 55%,#F2A65A)" role="img" aria-label="<?= e($r['name']) ?>"></div>
      <div class="p-6">
        <div class="flex items-baseline justify-between gap-4">
          <h3 class="font-display text-2xl"><?= e($r['name']) ?></h3>
          <p class="whitespace-nowrap text-sm text-lagoon/70">from <strong class="text-lagoon"><?= peso($r['price']) ?></strong> a night</p>
        </div>
        <p class="mt-2 text-lagoon/75"><?= e($r['blurb']) ?> Sleeps up to <?= (int)$r['guests'] ?>.</p>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
</section>

<!-- Experience rail -->
<section id="experience" class="bg-lagoon py-20 text-white">
  <div data-reveal class="mx-auto max-w-7xl px-5 lg:px-8">
    <h2 class="max-w-2xl font-display text-4xl sm:text-5xl">Days that start on the water</h2>
    <p class="mt-4 max-w-xl text-white/75">Most activities are a few steps from your door. Ask the front desk to book boat tours the day before.</p>
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
  <img data-clip src="assets/img/dining.jpg" alt="Wood-fired pizza, pasta and wine at La Mensa" class="aspect-[5/4] w-full rounded-3xl object-cover" loading="lazy">
  <div data-reveal>
    <h2 class="font-display text-4xl sm:text-5xl">Filipino breakfasts, Italian dinners, seafood on the beach</h2>
    <p class="mt-5 max-w-lg text-lg text-lagoon/80">Start with Filipino breakfast, then settle in at La Mensa for pizza, pasta and wine. For groups, the seafood boodle fight is the one to book.</p>
    <a href="contact.php?type=other" class="mt-8 inline-block rounded-full bg-lagoon px-7 py-3 font-semibold text-white hover:bg-tide">Reserve a table</a>
  </div>
</section>

<!-- Events -->
<section id="events" class="mx-auto max-w-7xl px-5 pb-24 lg:px-8">
  <div class="grid gap-6 md:grid-cols-2">
    <?php foreach ([['Weddings','Ceremony on the sand, reception under the trees, and casitas for the whole party.','wedding'],['Corporate events','Retreats and team days with space for meetings, archery and boat tours.','corporate']] as [$t,$d,$k]): ?>
    <article data-reveal class="rounded-3xl bg-foam p-8 lg:p-10">
      <h3 class="font-display text-3xl"><?= $t ?></h3>
      <p class="mt-3 max-w-md text-lagoon/80"><?= $d ?></p>
      <a href="contact.php?type=<?= $k ?>" class="mt-6 inline-block font-semibold text-bronze underline underline-offset-4 hover:text-lagoon">Ask about <?= strtolower($t) ?></a>
    </article>
    <?php endforeach; ?>
  </div>
</section>

<!-- Reviews (AJAX carousel) -->
<section id="reviews" class="bg-foam py-20">
  <div class="mx-auto max-w-7xl px-5 lg:px-8">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <h2 data-reveal class="font-display text-4xl sm:text-5xl">What guests say</h2>
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
      <a href="<?= e(whatsapp_url()) ?>" rel="noopener" class="rounded-full border border-white/30 px-7 py-3 font-semibold hover:bg-white hover:text-lagoon">Message on WhatsApp</a>
    </div>
  </div>
</section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>

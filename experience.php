<?php
require __DIR__ . '/includes/bootstrap.php';
$data = require __DIR__ . '/includes/data.php';
$page_title = 'Experience | ' . cfg('site_name');
$page_desc = 'Balsa raft, pools, kayaking, snorkeling, boat tours, banana boat and kids activities at ' . cfg('site_name') . '.';
$active = 'experience';
$acts = $data['experiences'];
$featured = array_slice($acts, 0, 2); $more = array_slice($acts, 2);
$notes = [
  ['Balsa raft','Free for every guest. Sunset is the best time, so ask at the front desk when to go.'],
  ['Boat tours and motorized rides','Ask the front desk the day before. Availability depends on weather and sea conditions.'],
  ['Water safety','Follow the staff instructions and keep children supervised near the water.'],
  ['Pets','Pets are welcome on the grounds. Tell us when you book.'],
];
include __DIR__ . '/includes/header.php';
?>
<main>
<section class="relative overflow-hidden bg-lagoon pb-24 pt-40 text-white">
  <img src="assets/img/hero-raft.jpg" alt="" class="absolute inset-0 h-full w-full object-cover" data-hero-img>
  <div class="absolute inset-0 bg-gradient-to-b from-lagoon/80 to-lagoon/65"></div>
  <div class="relative mx-auto max-w-7xl px-5 lg:px-8">
    <h1 data-reveal class="max-w-3xl font-display text-5xl leading-tight sm:text-6xl">Days that start on the water</h1>
    <p data-reveal class="mt-5 max-w-xl text-lg text-white/85">Most activities are a few steps from your door. Pick one, or do nothing at all.</p>
  </div>
</section>

<?php foreach ($featured as $i => [$t, $d, $img]): ?>
<section class="mx-auto grid max-w-7xl items-center gap-12 px-5 py-16 lg:grid-cols-2 lg:px-8">
  <img data-clip src="assets/img/<?= e($img) ?>" alt="<?= e($t) ?>" class="aspect-[4/3] w-full rounded-3xl object-cover <?= $i % 2 ? 'lg:order-2' : '' ?>" loading="lazy">
  <div data-reveal>
    <h2 class="font-display text-4xl sm:text-5xl"><?= e($t) ?></h2>
    <p class="mt-4 max-w-lg text-lg text-lagoon/80"><?= e($d) ?></p>
    <a href="rooms.php#booking" class="mt-6 inline-block rounded-full bg-lagoon px-7 py-3 font-semibold text-white hover:bg-tide">Plan your stay</a>
  </div>
</section>
<?php endforeach; ?>

<section class="bg-foam py-20">
  <div class="mx-auto max-w-7xl px-5 lg:px-8">
    <h2 data-reveal class="font-display text-4xl sm:text-5xl">More to do</h2>
    <ul class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <?php foreach ($more as [$t, $d, $img]): ?>
      <li data-reveal class="overflow-hidden rounded-3xl bg-white shadow-sm">
        <div class="ph h-44 bg-cover bg-center" <?php if ($img): ?>style="background-image:url('assets/img/<?= e($img) ?>'),linear-gradient(135deg,#14405F,#F2A65A)" role="img" aria-label="<?= e($t) ?>"<?php endif; ?>></div>
        <div class="p-6"><h3 class="font-display text-2xl"><?= e($t) ?></h3><p class="mt-2 text-lagoon/75"><?= e($d) ?></p></div>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="mx-auto max-w-5xl px-5 py-20">
  <h2 data-reveal class="font-display text-4xl sm:text-5xl">Good to know</h2>
  <dl class="mt-10 grid gap-8 sm:grid-cols-2">
    <?php foreach ($notes as [$t, $d]): ?>
    <div data-reveal class="border-l-2 border-bronze pl-5"><dt class="font-display text-xl"><?= e($t) ?></dt><dd class="mt-1 text-lagoon/75"><?= e($d) ?></dd></div>
    <?php endforeach; ?>
  </dl>
  <div class="mt-12 flex flex-wrap gap-3">
    <a href="rooms.php#booking" class="rounded-full bg-bronze px-8 py-3.5 font-semibold text-white hover:bg-lagoon">Check availability</a>
    <a href="contact.php" class="rounded-full border border-lagoon/25 px-8 py-3.5 font-semibold hover:bg-lagoon hover:text-white">Ask about an activity</a>
  </div>
</section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>

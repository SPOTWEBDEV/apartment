<?php
require __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/gallery.php';
$page_title = 'Photo gallery | ' . cfg('site_name');
$page_desc = 'Photos of the rooms, pools, dining and the cove at ' . cfg('site_name') . '.';
$active = 'gallery';
$extra_scripts = ['assets/js/gallery.js'];

// Room photos come from the admin; the rest are site photos. Duplicates are removed by file name.
$photos = [];
foreach (rooms_all(true) as $r) foreach ($r['images'] ?? [] as $im)
  $photos[$im['file']] ??= ['file' => $im['file'], 'caption' => trim($im['caption'] ?: $r['name']), 'cat' => 'rooms'];
foreach ([['hero-raft.jpg', 'Balsa raft at sunset', 'outdoors'], ['pool.jpg', 'The pool and villas', 'outdoors'], ['dining.jpg', 'Dinner at La Mensa', 'dining'], ['casita.jpg', 'Casita with a roof terrace', 'rooms']] as [$f, $c, $cat])
  if (is_file(__DIR__ . '/assets/img/' . $f)) $photos[$f] ??= ['file' => $f, 'caption' => $c, 'cat' => $cat];
$cats = ['all' => 'All photos', 'rooms' => 'Rooms', 'dining' => 'Dining', 'outdoors' => 'Pools and water'];
include __DIR__ . '/includes/header.php';
?>
<main>
<section class="bg-lagoon pb-16 pt-36 text-white">
  <div class="mx-auto max-w-7xl px-5 lg:px-8">
    <h1 data-reveal class="font-display text-5xl sm:text-6xl">Photo gallery</h1>
    <p data-reveal class="mt-5 max-w-xl text-lg text-white/80">A look at the rooms, the pools, the table and the cove.</p>
  </div>
</section>

<section class="mx-auto max-w-7xl px-5 py-14 lg:px-8">
  <div class="flex flex-wrap gap-2" role="group" aria-label="Filter photos">
    <?php foreach ($cats as $k => $label): ?>
      <button type="button" data-filter="<?= $k ?>" aria-pressed="<?= $k === 'all' ? 'true' : 'false' ?>" class="rounded-full border border-lagoon/20 px-5 py-2 text-sm font-semibold transition hover:bg-lagoon hover:text-white aria-pressed:border-lagoon aria-pressed:bg-lagoon aria-pressed:text-white"><?= e($label) ?></button>
    <?php endforeach; ?>
  </div>
  <?php if ($photos): ?>
  <ul data-lightbox-group class="mt-8 columns-1 gap-4 sm:columns-2 lg:columns-3">
    <?php foreach ($photos as $ph): ?>
    <li data-cat="<?= e($ph['cat']) ?>" class="mb-4 break-inside-avoid">
      <a href="assets/img/<?= e($ph['file']) ?>" data-lb data-caption="<?= e($ph['caption']) ?>" class="group relative block overflow-hidden rounded-2xl" aria-label="View photo: <?= e($ph['caption']) ?>">
        <img src="assets/img/<?= e($ph['file']) ?>" alt="<?= e($ph['caption']) ?>" loading="lazy" decoding="async" class="w-full transition duration-500 group-hover:scale-[1.03]">
        <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-lagoon/80 to-transparent px-4 pb-3 pt-10 text-sm font-medium text-white opacity-0 transition group-hover:opacity-100 group-focus-visible:opacity-100"><?= e($ph['caption']) ?></span>
      </a>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
  <p id="gridEmpty" class="<?= $photos ? 'hidden' : '' ?> mt-10 text-center text-lagoon/60">No photos in this group yet.</p>
</section>
</main>
<?php render_lightbox(); include __DIR__ . '/includes/footer.php'; ?>

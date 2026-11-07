<?php
require __DIR__ . '/includes/bootstrap.php';

// Old links (rooms.php?room=...&checkin=...) forward to the single room page
if (!empty($_GET['room'])) { header('Location: room.php?' . http_build_query($_GET)); exit; }

$page_title = 'Rooms | ' . cfg('site_name');
$page_desc  = 'Browse the casitas, villas and rooms at ' . cfg('site_name') . '.';
$active = 'rooms';
include __DIR__ . '/includes/header.php';
$rooms = rooms_all(true);
?>
<main>
<section class="bg-lagoon pb-16 pt-36 text-white">
  <div class="mx-auto max-w-7xl px-5 lg:px-8">
    <h1 data-reveal class="font-display text-5xl sm:text-6xl">Our rooms</h1>
    <p data-reveal class="mt-5 max-w-xl text-lg text-white/80">Choose a room to see photos, amenities and book your dates.</p>
  </div>
</section>

<section class="mx-auto max-w-7xl px-5 py-16 lg:px-8">
  <?php if (!$rooms): ?>
    <p class="rounded-3xl bg-foam p-10 text-lagoon/80">Our rooms are being added. <a href="contact.php" class="font-semibold text-bronze underline underline-offset-4">Contact reservations</a> to book your stay.</p>
  <?php else: ?>
  <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($rooms as $r): ?>
    <a href="room.php?room=<?= e($r['id']) ?>" data-reveal class="group block overflow-hidden rounded-3xl bg-white shadow-sm transition hover:shadow-lg">
      <div class="overflow-hidden">
        <div class="ph aspect-[4/3] bg-cover bg-center transition duration-500 group-hover:scale-[1.03]"
             style="background-image:url('<?= e(room_img($r['img'])) ?>'),linear-gradient(135deg,#14405F,#2E6C8A 55%,#F2A65A)"
             role="img" aria-label="<?= e($r['name']) ?>"></div>
      </div>
      <div class="p-6">
        <h2 class="font-display text-2xl"><?= e($r['name']) ?></h2>
        <p class="mt-1 text-sm text-lagoon/70"><?= e($r['beds']) ?> · Sleeps up to <?= (int)$r['guests'] ?></p>
        <p class="mt-3 line-clamp-2 text-lagoon/75"><?= e($r['blurb']) ?></p>
        <div class="mt-5 flex items-center justify-between gap-3">
          <p class="text-sm text-lagoon/70">from <strong class="text-lagoon"><?= peso($r['price']) ?></strong> a night</p>
          <span class="font-semibold text-bronze underline-offset-4 group-hover:underline">View and book</span>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
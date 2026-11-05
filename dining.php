<?php
require __DIR__ . '/includes/bootstrap.php';
$page_title = 'Dining | ' . cfg('site_name');
$page_desc = 'Filipino breakfast, Asian dishes, Italian dinners at La Mensa and seafood boodle fights at ' . cfg('site_name') . '.';
$active = 'dining';
include __DIR__ . '/includes/header.php';
$hours = cfg('dining_hours', []);
$blurbs = ['Breakfast'=>'Filipino breakfast to start the day, with plenty of choice.','Lunch'=>'Asian dishes, grilled seafood and pizza, served close to the pool.','Dinner at La Mensa'=>'Our Italian chophouse: pizza, pasta and wine.'];
$dishes = [
  ['Four-cheese pizza','Thin, wood-fired crust and a favorite with families.'],
  ['Pasta with shrimp','Garlic bread on the side, made for sharing.'],
  ['Spaghetti bolognese','A simple one the kids will finish.'],
  ['Lomi','A thick, warming Filipino noodle soup.'],
  ['Seafood boodle fight','Served on banana leaves for the table. Order ahead.'],
  ['Tamarind welcome juice','Cold and tangy, offered when you arrive.'],
];
?>
<main>
<section class="relative overflow-hidden bg-lagoon pb-24 pt-40 text-white">
  <img src="assets/img/dining.jpg" alt="" class="absolute inset-0 h-full w-full object-cover" data-hero-img>
  <div class="absolute inset-0 bg-gradient-to-b from-lagoon/80 to-lagoon/70"></div>
  <div class="relative mx-auto max-w-7xl px-5 lg:px-8">
    <h1 data-reveal class="max-w-3xl font-display text-5xl leading-tight sm:text-6xl">Eat well by the water</h1>
    <p data-reveal class="mt-5 max-w-xl text-lg text-white/85">Filipino breakfasts, Asian dishes, Italian dinners at La Mensa, and seafood laid out on the table for the whole group.</p>
  </div>
</section>

<section class="mx-auto max-w-7xl px-5 py-20 lg:px-8">
  <div class="grid gap-8 md:grid-cols-3">
    <?php foreach ($hours as $name => $time): ?>
    <div data-reveal class="border-l-2 border-bronze pl-5">
      <h2 class="font-display text-2xl"><?= e($name) ?></h2>
      <p class="mt-1 font-medium text-bronze"><?= e($time) ?></p>
      <p class="mt-2 text-lagoon/75"><?= e($blurbs[$name] ?? '') ?></p>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="mx-auto grid max-w-7xl items-center gap-12 px-5 pb-24 lg:grid-cols-2 lg:px-8">
  <img data-clip src="assets/img/dining.jpg" alt="Pizza, pasta and wine on a table at La Mensa" class="aspect-[4/3] w-full rounded-3xl object-cover" loading="lazy">
  <div>
    <h2 data-reveal class="font-display text-4xl sm:text-5xl">From the kitchen</h2>
    <ul class="mt-8 divide-y divide-lagoon/10">
      <?php foreach ($dishes as [$t,$d]): ?>
      <li data-reveal class="py-4"><h3 class="font-display text-xl"><?= e($t) ?></h3><p class="text-lagoon/70"><?= e($d) ?></p></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="bg-foam py-20">
  <div data-reveal class="mx-auto max-w-3xl px-5 text-center">
    <h2 class="font-display text-4xl sm:text-5xl">Booking a boodle fight or a group dinner?</h2>
    <p class="mt-4 text-lg text-lagoon/80">Tell us the date and how many are coming. We will confirm the menu and the table.</p>
    <a href="contact.php?type=other&topic=table" class="mt-8 inline-block rounded-full bg-bronze px-8 py-3.5 font-semibold text-white hover:bg-lagoon">Reserve a table</a>
  </div>
</section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>

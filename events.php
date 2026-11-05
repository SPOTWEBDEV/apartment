<?php
require __DIR__ . '/includes/bootstrap.php';
$page_title = 'Weddings and corporate events | ' . cfg('site_name');
$page_desc = 'Hold a beach wedding or a company retreat at ' . cfg('site_name') . ' in San Juan, Batangas.';
$active = 'events';
$type = in_array($_GET['type'] ?? '', ['wedding','corporate'], true) ? $_GET['type'] : 'wedding';
$blocks = [
  ['id'=>'weddings','title'=>'Weddings','lead'=>'Say your vows on the sand or under the trees, then celebrate on open lawns with the water behind you.','img'=>'casita.jpg','alt'=>'Casita with a roof terrace',
   'items'=>['Ceremony on the beach or under the trees','Reception on the open lawns','Casitas and rooms for the wedding party','Dining from the resort kitchen and La Mensa']],
  ['id'=>'corporate','title'=>'Corporate events','lead'=>'Retreats and team days with room to meet in the morning and get on the water in the afternoon.','img'=>'pool.jpg','alt'=>'Pool and villas',
   'items'=>['Quiet spaces for meetings and workshops','Team activities: kayaking, archery, boat tours','Pools and lawns for downtime','Meals and overnight stays in one place']],
];
include __DIR__ . '/includes/header.php';
?>
<main>
<section class="relative overflow-hidden bg-lagoon pb-24 pt-40 text-white">
  <img src="assets/img/hero-raft.jpg" alt="" class="absolute inset-0 h-full w-full object-cover" data-hero-img>
  <div class="absolute inset-0 bg-gradient-to-b from-lagoon/80 to-lagoon/70"></div>
  <div class="relative mx-auto max-w-7xl px-5 lg:px-8">
    <h1 data-reveal class="max-w-3xl font-display text-5xl leading-tight sm:text-6xl">Weddings and corporate events</h1>
    <p data-reveal class="mt-5 max-w-xl text-lg text-white/85">A cove with wide lawns, two pools and rooms on site, two and a half hours from Manila.</p>
    <a data-reveal href="#inquire" class="mt-8 inline-block rounded-full bg-sunset px-8 py-3.5 font-semibold text-lagoon hover:bg-white">Start an inquiry</a>
  </div>
</section>

<?php foreach ($blocks as $i => $b): ?>
<section id="<?= $b['id'] ?>" class="mx-auto grid max-w-7xl scroll-mt-20 items-center gap-12 px-5 py-20 lg:grid-cols-2 lg:px-8">
  <img data-clip src="assets/img/<?= e($b['img']) ?>" alt="<?= e($b['alt']) ?>" class="aspect-[4/3] w-full rounded-3xl object-cover <?= $i % 2 ? 'lg:order-2' : '' ?>" loading="lazy">
  <div data-reveal>
    <h2 class="font-display text-4xl sm:text-5xl"><?= e($b['title']) ?></h2>
    <p class="mt-4 max-w-lg text-lg text-lagoon/80"><?= e($b['lead']) ?></p>
    <ul class="mt-6 space-y-3 text-lagoon/85">
      <?php foreach ($b['items'] as $it): ?><li class="border-l-2 border-bronze pl-4"><?= e($it) ?></li><?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endforeach; ?>

<section class="bg-foam py-20">
  <div class="mx-auto max-w-5xl px-5">
    <h2 data-reveal class="font-display text-4xl sm:text-5xl">How planning works</h2>
    <ol class="mt-10 grid gap-8 md:grid-cols-3">
      <?php foreach ([['Tell us the date and group size','Use the form below, or message us on WhatsApp.'],['We reply with options and a quote','Usually within a day, by email.'],['Visit if you like, then confirm','See the grounds first, then lock in your date.']] as $n => [$t,$d]): ?>
      <li data-reveal class="rounded-2xl bg-white p-6"><span class="font-display text-3xl text-bronze"><?= $n + 1 ?></span><h3 class="mt-3 font-display text-xl"><?= $t ?></h3><p class="mt-1 text-lagoon/70"><?= $d ?></p></li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<section id="inquire" class="mx-auto grid max-w-7xl scroll-mt-20 gap-12 px-5 py-24 lg:grid-cols-5 lg:px-8">
  <div data-reveal class="lg:col-span-2">
    <h2 class="font-display text-4xl sm:text-5xl">Send an inquiry</h2>
    <p class="mt-4 max-w-md text-lg text-lagoon/80">Share the date, the size of your group and what you have in mind.</p>
    <p class="mt-6 text-lagoon/80">Prefer to talk? <a class="font-semibold text-bronze underline underline-offset-4" href="<?= e(whatsapp_url('Hello, I would like to ask about an event.')) ?>" rel="noopener">Message us on WhatsApp</a> or call <a class="underline" href="tel:<?= e(cfg('phone_tel')) ?>"><?= e(cfg('phone')) ?></a>.</p>
  </div>
  <?php $form_class = 'lg:col-span-3'; $event_fields = true; $msg = ''; include __DIR__ . '/includes/inquiry-form.php'; ?>
</section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>

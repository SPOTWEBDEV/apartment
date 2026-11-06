<?php
require __DIR__ . '/includes/bootstrap.php';
$page_title = 'About | ' . cfg('site_name');
$page_desc = 'About ' . cfg('site_name') . ': a beachfront resort in a quiet cove in San Juan, Batangas, with casitas, two pools and open grounds.';
$active = 'about';
$faqs = [
  ['Is the resort pet friendly?', 'Yes. Guests bring their dogs and use the lawns and the sand. Tell us when you book so we can prepare.'],
  ['How far is it from Manila?', 'About two and a half hours south of Manila. The address is ' . implode(', ', cfg('address')) . '.'],
  ['Which activities are included?', 'The balsa raft is free for guests. Other activities such as kayaking, paddle boards, boat tours and banana boat rides can be arranged at the front desk.'],
  ['Can I hold a wedding or company event here?', 'Yes. Send us the date and your group size on the contact page and the reservations team will reply with options.'],
];
if (cfg('check_in') && cfg('check_out')) array_splice($faqs, 2, 0, [['What time are check-in and check-out?', 'Check-in is from ' . cfg('check_in') . ' and check-out is by ' . cfg('check_out') . '.']]);
include __DIR__ . '/includes/header.php';
?>
<main>
<section class="bg-lagoon pb-20 pt-36 text-white">
  <div class="mx-auto max-w-7xl px-5 lg:px-8">
    <h1 data-reveal class="max-w-3xl font-display text-5xl leading-tight sm:text-6xl">A cove that has been kept simple</h1>
    <p data-reveal class="mt-5 max-w-xl text-lg text-white/80">Cala Laiya is a beachfront resort in Laiya Ibabao, San Juan, Batangas.</p>
  </div>
</section>

<section class="mx-auto grid max-w-7xl items-center gap-12 px-5 py-24 lg:grid-cols-2 lg:px-8">
  <img data-clip src="assets/img/casita.jpg" alt="Two-storey casita with a glass-fenced roof terrace" class="aspect-[4/3] w-full rounded-3xl object-cover">
  <div data-reveal class="space-y-5 text-lg leading-relaxed text-lagoon/80">
    <h2 class="font-display text-4xl text-lagoon">Space to slow down</h2>
    <p>The resort sits in a cove about two and a half hours south of Manila. The grounds are wide and green, with room to run, play archery or just sit under the trees in front of the beach.</p>
    <p>You can stay in modern casitas with big windows and roof terraces, swim in two pools, take the balsa raft out at sunset, or paddle across the cove.</p>
    <p>Families, couples, friends traveling with dogs, wedding parties and company teams all use the same grounds, and there is enough space for each of them.</p>
  </div>
</section>

<section class="bg-foam py-20">
  <div class="mx-auto grid max-w-7xl gap-8 px-5 sm:grid-cols-2 lg:grid-cols-4 lg:px-8">
    <?php foreach ([['Casitas and rooms','Beachfront and poolside stays for 2 to 8 guests.'],['Two pools','Clean, new and open to every guest.'],['Water activities','Balsa raft, kayaks, paddle boards, boat tours.'],['Events','Weddings and corporate retreats on the grounds.']] as [$t,$d]): ?>
      <div data-reveal class="border-l-2 border-bronze pl-4"><h3 class="font-display text-xl"><?= $t ?></h3><p class="mt-1 text-lagoon/70"><?= $d ?></p></div>
    <?php endforeach; ?>
  </div>
</section>

<section id="faqs" class="mx-auto max-w-3xl px-5 py-24">
  <h2 data-reveal class="font-display text-4xl sm:text-5xl">Questions guests ask</h2>
  <div class="mt-10 divide-y divide-lagoon/15 border-y border-lagoon/15">
    <?php foreach ($faqs as [$q,$a]): ?>
    <details class="faq py-5">
      <summary class="flex items-center justify-between gap-4 font-display text-xl"><?= e($q) ?><span class="plus text-2xl text-bronze" aria-hidden="true">+</span></summary>
      <p class="mt-3 max-w-2xl text-lagoon/80"><?= e($a) ?></p>
    </details>
    <?php endforeach; ?>
  </div>
  <a href="contact.php" class="mt-10 inline-block rounded-full bg-lagoon px-7 py-3 font-semibold text-white hover:bg-tide">Ask us something else</a>
</section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>

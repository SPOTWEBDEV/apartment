<?php require_once __DIR__ . '/bootstrap.php'; ?>
<footer class="bg-lagoon text-white/75">
  <div class="mx-auto grid max-w-7xl gap-12 px-5 py-16 sm:grid-cols-2 lg:grid-cols-4 lg:px-8">
    <div>
      <img src="assets/img/logo.png" alt="<?= e(cfg('site_name')) ?>" class="h-12 w-auto" style="filter:brightness(0) invert(1)">
      <p class="mt-5 max-w-xs text-sm leading-relaxed">A tropical sanctuary in a quiet cove, 2.5 hours south of Manila. Cozy accommodations in a natural setting left unspoiled for you to discover.</p>
      <?php $soc = array_filter(['Facebook' => cfg('social.facebook'), 'Instagram' => cfg('social.instagram')], fn($u) => $u && $u !== '#'); ?>
      <?php if ($soc): ?><p class="mt-6 flex gap-3 text-sm"><?php foreach ($soc as $label => $url): ?><a href="<?= e($url) ?>" class="rounded-full border border-white/25 px-4 py-2 hover:bg-white hover:text-lagoon" rel="noopener"><?= $label ?></a><?php endforeach; ?></p><?php endif; ?>
    </div>
    <nav aria-label="Discover">
      <h2 class="font-display text-lg text-white">Discover</h2>
      <ul class="mt-4 space-y-2.5 text-sm">
        <li><a class="hover:text-white" href="about.php">About <?= e(cfg('site_name')) ?></a></li>
        <li><a class="hover:text-white" href="about.php#faqs">FAQs</a></li>
        <li><a class="hover:text-white" href="gallery.php">Gallery</a></li>
        <li><a class="hover:text-white" href="index.php#reviews">Guest reviews</a></li>
        <li><a class="hover:text-white" href="rooms.php#booking">Reserve a room</a></li>
        <li><a class="hover:text-white" href="contact.php">Contact</a></li>
      </ul>
    </nav>
    <nav aria-label="Experience">
      <h2 class="font-display text-lg text-white">Experience</h2>
      <ul class="mt-4 space-y-2.5 text-sm">
        <?php foreach (['Balsa raft','Kayaking and paddle board','Snorkeling and boat tours','Banana boat','Kids activities','Weddings and corporate'] as $x): ?>
          <li><a class="hover:text-white" href="<?= str_starts_with($x,'Weddings') ? 'events.php' : 'experience.php' ?>"><?= $x ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <div>
      <h2 class="font-display text-lg text-white">Get in touch</h2>
      <address class="mt-4 space-y-3 text-sm not-italic leading-relaxed">
        <p><?= implode('<br>', array_map('e', cfg('address', []))) ?></p>
        <p><a class="hover:text-white" href="tel:<?= e(cfg('phone_tel')) ?>"><?= e(cfg('phone')) ?></a></p>
        <p><a class="hover:text-white" href="mailto:<?= e(cfg('email')) ?>"><?= e(cfg('email')) ?></a></p>
        <?php if (messenger_url()): ?><p><a class="font-semibold text-sunset hover:text-white" href="<?= e(messenger_url()) ?>" rel="noopener">Message us on Messenger</a></p><?php endif; ?>
      </address>
    </div>
  </div>
  <div class="border-t border-white/10">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-5 py-6 text-xs lg:px-8">
      <p>&copy; <?= date('Y') ?> <?= e(cfg('site_name')) ?>. All rights reserved.</p>
      <p class="flex flex-wrap gap-x-5 gap-y-1"><a class="hover:text-white" href="privacy.php">Privacy</a><a class="hover:text-white" href="terms.php">Terms</a><a class="hover:text-white" href="<?= e(maps_url()) ?>" rel="noopener">Google Maps</a></p>
    </div>
  </div>
</footer>

<?php if (messenger_url()): ?><a href="<?= e(messenger_url()) ?>" rel="noopener" aria-label="Chat on Messenger"
   class="fixed bottom-5 right-5 z-40 flex items-center gap-2 rounded-full bg-bronze px-5 py-3 text-sm font-semibold text-white shadow-lg hover:bg-lagoon">
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M21 12a8 8 0 0 1-11.8 7L3 21l2-6A8 8 0 1 1 21 12z"/></svg>
  Chat with us
</a><?php endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js" defer></script>
<script src="assets/js/app.js" defer></script>
<?php foreach (($extra_scripts ?? []) as $src): ?><script src="<?= e($src) ?>" defer></script>
<?php endforeach; ?>
<script src="assets/js/motion.js" defer></script>
</body>
</html>

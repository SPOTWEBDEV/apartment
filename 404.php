<?php
require __DIR__ . '/includes/bootstrap.php';
http_response_code(404);
$page_title = 'Page not found | ' . cfg('site_name');
$robots = 'noindex,nofollow';
include __DIR__ . '/includes/header.php';
?>
<main class="mx-auto flex min-h-[70vh] max-w-3xl flex-col items-start justify-center px-5 pb-20 pt-40">
  <p class="font-display text-7xl text-bronze">404</p>
  <h1 class="mt-4 font-display text-4xl sm:text-5xl">We could not find that page</h1>
  <p class="mt-4 max-w-md text-lg text-lagoon/80">The link may be old or mistyped. Here are a few places to start.</p>
  <div class="mt-8 flex flex-wrap gap-3">
    <a href="index.php" class="rounded-full bg-lagoon px-7 py-3 font-semibold text-white hover:bg-tide">Home</a>
    <a href="rooms.php" class="rounded-full border border-lagoon/25 px-7 py-3 font-semibold hover:bg-lagoon hover:text-white">Rooms and booking</a>
    <a href="contact.php" class="rounded-full border border-lagoon/25 px-7 py-3 font-semibold hover:bg-lagoon hover:text-white">Contact us</a>
  </div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

/** Photo gallery for one room: big photo, arrows, captions, thumbnails. Clicking opens the lightbox (assets/js/gallery.js). */
function render_gallery(array $r, string $cls = ''): void {
  $imgs = array_map(fn($i) => ['src' => 'assets/img/' . $i['file'], 'caption' => $i['caption'] ?? ''], $r['images'] ?? []);
  if (!$imgs && !empty($r['img']) && is_file(__DIR__ . '/../assets/img/' . $r['img'])) $imgs = [['src' => 'assets/img/' . $r['img'], 'caption' => '']];
  $n = count($imgs);
  ?>
  <div class="<?= e($cls) ?>" data-gallery data-name="<?= e($r['name']) ?>">
  <?php if (!$n): ?>
    <div class="ph aspect-[4/3] rounded-3xl" role="img" aria-label="<?= e($r['name']) ?>"></div>
  <?php else: ?>
    <div class="relative overflow-hidden rounded-3xl bg-lagoon/5">
      <button type="button" data-open class="block w-full cursor-zoom-in" aria-label="Open the photo gallery for <?= e($r['name']) ?>">
        <img data-main src="<?= e($imgs[0]['src']) ?>" alt="<?= e($imgs[0]['caption'] ?: $r['name']) ?>" class="aspect-[4/3] w-full object-cover" decoding="async">
      </button>
      <?php if ($n > 1): ?>
        <button type="button" data-prev class="absolute left-3 top-1/2 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-xl text-lagoon shadow hover:bg-white" aria-label="Previous photo">&lsaquo;</button>
        <button type="button" data-next class="absolute right-3 top-1/2 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-xl text-lagoon shadow hover:bg-white" aria-label="Next photo">&rsaquo;</button>
        <p data-count class="pointer-events-none absolute right-3 top-3 rounded-full bg-lagoon/70 px-3 py-1 text-xs font-medium text-white backdrop-blur">1 / <?= $n ?></p>
      <?php endif; ?>
      <p data-cap class="pointer-events-none absolute bottom-3 left-3 rounded-full bg-lagoon/70 px-3 py-1 text-xs font-medium text-white backdrop-blur <?= $imgs[0]['caption'] ? '' : 'hidden' ?>"><?= e($imgs[0]['caption']) ?></p>
    </div>
    <?php if ($n > 1): ?>
    <div class="mt-3 flex gap-2 overflow-x-auto pb-1" role="list" aria-label="Photos of <?= e($r['name']) ?>">
      <?php foreach ($imgs as $k => $im): ?>
        <button type="button" role="listitem" data-thumb="<?= $k ?>" aria-current="<?= $k === 0 ? 'true' : 'false' ?>" aria-label="Show photo <?= $k + 1 ?><?= $im['caption'] ? ': ' . e($im['caption']) : '' ?>"
          class="shrink-0 overflow-hidden rounded-lg opacity-70 ring-offset-2 transition hover:opacity-100 aria-[current=true]:opacity-100 aria-[current=true]:ring-2 aria-[current=true]:ring-bronze">
          <img src="<?= e($im['src']) ?>" alt="" loading="lazy" decoding="async" class="h-16 w-24 object-cover">
        </button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <script type="application/json" data-images><?= json_encode($imgs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
  <?php endif; ?>
  </div>
<?php }

/** Full-screen photo viewer shared by every page that has photos. */
function render_lightbox(): void { ?>
<dialog id="lightbox" class="m-0 h-full max-h-none w-full max-w-none bg-transparent p-0 backdrop:bg-lagoon/90" aria-label="Photo viewer">
  <div data-lb-wrap class="relative flex h-full w-full flex-col items-center justify-center p-4">
    <button type="button" data-lb-close class="absolute right-4 top-4 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/20" aria-label="Close photo viewer">Close</button>
    <img data-lb-img alt="" class="max-h-[78vh] max-w-full rounded-lg object-contain">
    <p data-lb-cap class="mt-4 text-center font-display text-xl text-white"></p>
    <p data-lb-count class="mt-1 text-sm text-white/60"></p>
    <button type="button" data-lb-prev class="absolute left-3 top-1/2 grid h-12 w-12 -translate-y-1/2 place-items-center rounded-full bg-white/10 text-2xl text-white hover:bg-white/20" aria-label="Previous photo">&lsaquo;</button>
    <button type="button" data-lb-next class="absolute right-3 top-1/2 grid h-12 w-12 -translate-y-1/2 place-items-center rounded-full bg-white/10 text-2xl text-white hover:bg-white/20" aria-label="Next photo">&rsaquo;</button>
  </div>
</dialog>
<?php }

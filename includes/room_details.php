<?php
/** Shows a room's full description and its extra detail sections (Occupancy Limit, Pet Fee, Room Details...) on the room page. */
function render_room_details(string $roomId): void {
  try {
    $s = db()->prepare('SELECT description, details FROM rooms WHERE id=?'); $s->execute([$roomId]);
    $r = $s->fetch();
  } catch (Throwable $e) { return; } // the columns do not exist until the admin saves a room once
  if (!$r) return;
  $desc = trim((string)($r['description'] ?? ''));
  $sections = json_decode((string)($r['details'] ?? '[]'), true);
  if (!is_array($sections)) $sections = [];
  if ($desc === '' && !$sections) return; ?>
  <div class="mt-6 space-y-6 text-lagoon/80">
    <?php if ($desc !== ''): ?><p class="whitespace-pre-line leading-relaxed"><?= e($desc) ?></p><?php endif; ?>
    <?php foreach ($sections as $sec):
      $title = trim((string)($sec['title'] ?? '')); $items = (array)($sec['items'] ?? []);
      if ($title === '' && !$items) continue; ?>
    <div>
      <?php if ($title !== ''): ?><h2 class="font-semibold text-lagoon"><?= e(rtrim($title, ':')) ?>:</h2><?php endif; ?>
      <?php if ($items): ?><ul class="mt-2 list-disc space-y-1 pl-5"><?php foreach ($items as $it): ?><li><?= e((string)$it) ?></li><?php endforeach; ?></ul><?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
<?php }
<?php /* Expects: $type, $msg (optional), $form_class (optional) */
$type = $type ?? 'stay';
$needsDate = in_array($type, ['table', 'wedding', 'corporate'], true);
?>
<form id="inquiryForm" class="space-y-4 rounded-3xl bg-white p-6 shadow-sm sm:p-8 <?= e($form_class ?? '') ?>" novalidate>
  <div class="grid gap-4 sm:grid-cols-2">
    <div><label class="mb-1 block text-sm font-medium" for="name">Name</label><input class="field" id="name" name="name" autocomplete="name"><p class="err mt-1 text-sm text-red-700" data-for="name"></p></div>
    <div><label class="mb-1 block text-sm font-medium" for="email">Email</label><input class="field" id="email" name="email" type="email" autocomplete="email"><p class="err mt-1 text-sm text-red-700" data-for="email"></p></div>
  </div>
  <div class="grid gap-4 sm:grid-cols-2">
    <div><label class="mb-1 block text-sm font-medium" for="phone">Phone (optional)</label><input class="field" id="phone" name="phone" type="tel" autocomplete="tel"></div>
    <div><label class="mb-1 block text-sm font-medium" for="type">I am asking about</label>
      <select class="field" id="type" name="type">
        <?php foreach (['stay'=>'A stay','table'=>'A table reservation','wedding'=>'A wedding','corporate'=>'A corporate event','other'=>'Something else'] as $v=>$l): ?>
          <option value="<?= $v ?>"<?= $v === $type ? ' selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select></div>
  </div>
  <div id="extraFields" class="grid gap-4 sm:grid-cols-3 <?= $needsDate ? '' : 'hidden' ?>">
    <div><label class="mb-1 block text-sm font-medium" for="event_date">Date</label><input class="field" id="event_date" name="event_date" type="date" min="<?= date('Y-m-d') ?>"><p class="err mt-1 text-sm text-red-700" data-for="event_date"></p></div>
    <div id="timeWrap" class="<?= $type === 'table' ? '' : 'hidden' ?>"><label class="mb-1 block text-sm font-medium" for="reserve_time">Time</label><input class="field" id="reserve_time" name="reserve_time" type="time"></div>
    <div><label class="mb-1 block text-sm font-medium" for="guests">Guests</label><input class="field" id="guests" name="guests" type="number" min="1" max="5000"><p class="err mt-1 text-sm text-red-700" data-for="guests"></p></div>
  </div>
  <div><label class="mb-1 block text-sm font-medium" for="message">Message</label><textarea class="field" id="message" name="message" rows="5" placeholder="Tell us what you need. For a table, add anything we should know."><?= e($msg ?? '') ?></textarea><p class="err mt-1 text-sm text-red-700" data-for="message"></p></div>
  <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
  <button class="w-full rounded-full bg-bronze px-7 py-3.5 font-semibold text-white hover:bg-lagoon disabled:opacity-60">Send</button>
  <p class="text-xs text-lagoon/60">By sending this you agree to our <a class="underline" href="privacy.php">Privacy Policy</a>.</p>
  <p id="formMsg" class="min-h-6 text-sm" role="status" aria-live="polite"></p>
</form>

<?php
require_once __DIR__ . '/_layout.php';
admin_open('Rooms', 'rooms', 'Rates, capacity and what shows on the website.', '<button id="addRoom" class="btn-primary">Add room</button>');
?>
<div id="grid" class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3"><?php for ($i = 0; $i < 3; $i++): ?><div class="card skel h-80"></div><?php endfor; ?></div>
<?php admin_close(['assets/rooms.js']);

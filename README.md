# Cala Laiya website and admin

Pure PHP 8.1+, SQLite, Tailwind (CDN), vanilla JavaScript, GSAP. No Composer or build step needed.

## Requirements
- PHP 8.1 or newer with the `pdo_sqlite`, `mbstring` and `openssl` extensions (all standard).
- Apache (the included `.htaccess` protects private folders) or Nginx (see below).

## Install
1. Upload the project folder and point your domain at it.
2. Copy `.env.example` to `.env` and fill it in. The safest place for `.env` is one folder **above** the website folder; the project folder also works.
   - `ADMIN_EMAIL` and `ADMIN_PASSWORD` create the first admin when the database is first created.
   - `APP_URL` is the public address, used for links in emails.
   - `MAIL_*` are your SMTP details (leave `MAIL_HOST` empty to use PHP `mail()`).
3. Make these writable by PHP: `storage/` (database, inquiries) and `assets/img/` (room photo uploads).
4. Open the site once so the database is created, then sign in at `/admin/login.php`. The admin starts completely empty: add your rooms in Admin > Rooms, and add your bank, e-wallet and crypto accounts, your Messenger page, check-in and check-out times and social links in Admin > Settings. Anything you leave empty is simply hidden on the website.
5. Check email works: `php tools/test-mail.php you@example.com`.

Forgot the password, or created the database before setting `.env`? Run `php tools/create-admin.php you@example.com "new long password"`.

## Where things are
| What | Where |
|---|---|
| Public pages | `index.php`, `about.php`, `rooms.php` (gallery + booking flow), `experience.php`, `dining.php`, `events.php`, `gallery.php`, `contact.php`, `privacy.php`, `terms.php`, `404.php` |
| Search engines | `robots.txt`, `sitemap.php` |
| Receipts | `receipt.php` (PDF, secret link), `assets/js/receipt-image.js` (PNG on the booking page) |
| Admin | `admin/` (dashboard, bookings, calendar, rooms, payments, messages, discounts, settings) |
| Payment step | The booking flow ends with a **Payment** step (shown when you have added accounts) listing your bank, e-wallet and crypto accounts, with a form for the guest to send their payment details and a screenshot. Add the accounts in Admin > Settings (Bank and e-wallet accounts, Crypto payments). Payments wait in Admin > Payments as "Needs review" until staff approve or decline them. Guests can also pay later from the private link in their booking email (`pay.php`). Screenshots are stored in `storage/proofs/` and only signed-in admins can open them |
| Messages | Contact form and table reservations land in Admin > Messages (`inquiries` table) and are also emailed |
| Defaults for site name, contact, hours | `includes/config.php` (Admin > Settings overrides these) |
| Secrets | `.env` only. Never commit it |
| Database | `storage/app.sqlite` (or `DB_PATH`) |
| Start over | `php tools/reset-data.php --yes` clears rooms, bookings, payments, messages and discount codes (keeps admin accounts and settings) |

## Room photos
In Admin > Rooms, add or edit a room and choose several photos at once (up to 10 by default, change `max_room_images` in `includes/config.php`). Give each photo a caption such as "Parlor" or "Master bedroom", and use the arrows to set the order. The first photo is the cover. Photos are resized in the browser before upload and stored in `assets/img/rooms/`.

## Before going live
- Have a lawyer review `privacy.php` and `terms.php`; they are sensible starting points, not legal advice.
- For a friendly 404 page on Apache, uncomment the `ErrorDocument` line in `.htaccess`.
- Replace placeholder content: room names, prices, amenities, dining hours, Messenger page, social links, and the sample reviews in `includes/data.php`.
- Tailwind runs from the CDN for convenience. For production speed, compile it with the Tailwind CLI and link the CSS instead of the CDN `<script>`.
- Serve over HTTPS. Back up `storage/app.sqlite` regularly.
- The crypto wallets in Admin > Settings are shown to guests. Double-check every address.

## Nginx
Add rules that deny `/includes/`, `/tools/`, `/storage/`, `/.env` and `*.sqlite`, for example:

    location ~ ^/(includes|tools|storage)/ { deny all; }
    location ~ /\.(env|git) { deny all; }
    location ~ \.(sqlite|sqlite-wal|sqlite-shm|jsonl)$ { deny all; }

# Dreamm Framme Event — Wedding Photography & Cinematography Website + PHP/MySQL CMS

A luxury wedding photography / cinematography website with a full-screen **hero video slider**, portfolio albums with masonry galleries and a lightbox, a films page, an 11-service catalogue and a **“Check your date” booking form**. Everything is managed from a **Core PHP 8.2+ / MySQL 8** admin panel at `/admin`.

**Stack:** HTML5 · CSS3 · Vanilla JS · Bootstrap 5 grid · Font Awesome · Google Fonts (Cormorant Garamond + Poppins) · PHP 8.2+ (PDO, sessions, AJAX) · MySQL 8 / MariaDB 10.4+ · Apache (XAMPP / MAMP / cPanel).
No Firebase, Laravel, React, Vue, Angular, Node backend, MongoDB, Supabase or external CMS.

---

## 1. What's included

**Public site**
- **Home**: hero video slider, brand introduction, featured wedding stories (from MySQL), services, film showcase (YouTube/Vimeo), why choose us, statistics, testimonials, Instagram grid and the final “Let's capture your beautiful beginning” call to action.
- **About**: hero, story, who we are, philosophy, mission & vision, creative approach, team, equipment, why choose us and a call to action.
- **Services**: all 11 services, each with a cover, gallery (opens in the lightbox), features, details, price note and an “Enquire” link that pre-selects the service on the booking form.
- **Portfolio**: category filter, album grid and a “favourite frames” masonry with a lightbox.
- **gallery.php?album=slug**: one wedding story with details, story text, a masonry gallery (thumbnails load first, full size only in the lightbox) and previous/next stories. Clean URL `/album/slug` works on Apache.
- **Films**: featured film, filters and a film grid, with a video modal for YouTube (privacy-enhanced), Vimeo or MP4 links.
- **Contact**: the booking form, studio details, booking note and map.
- **Privacy policy** and **Terms**, plus `sitemap.xml` and `robots.txt`.
- Preloader, transparent sticky navbar, mobile menu, scroll reveals, parallax, page transitions, WhatsApp button and back-to-top button. `prefers-reduced-motion` is respected.

**Hero video slider**
- Full-screen (100vh) slider with crossfades and a single timer.
- Animated text per slide: eyebrow, heading, subtitle and two buttons. Text can be aligned left, centre or right.
- Controls: arrows, dots with a progress bar, slide counter, pause/play, swipe, and keyboard arrows.
- Videos are always muted and `playsinline`.
- Only the current and next slide's video is downloaded. Hidden or off-screen sliders pause.
- MP4 (H.264) is the primary format; WebM and a portrait phone version are optional.
- The poster image is shown when:
  - a video fails or autoplay is blocked,
  - the visitor is on Save-Data or a 2G connection,
  - the visitor prefers reduced motion.

**Admin CMS**
- Dashboard with these figures:
  - total and new enquiries, confirmed bookings
  - albums, photos, services, films and active slides
  - upcoming event dates, a 12-month enquiry chart and recent activity
- Pages & Sections lets you edit, hide or show, and reorder every section, and set page SEO.
- **Hero Slider**:
  - add, edit and delete slides
  - upload MP4/WebM videos and posters, or use an external video URL
  - set the duration, overlay strength and text alignment
  - turn slides on or off, drag to reorder, and preview a slide before publishing
- Services, Films, Testimonials and Team support CRUD, search, sort, a publish switch and drag-to-reorder.
- **Portfolio Albums**: title, couple names, slug, category, date, location, cover, description, featured flag, order and publish status.
- **Album photo manager** (`albums.php?id=`):
  - drag-and-drop multi-upload with a progress bar
  - automatic WebP conversion and thumbnails
  - captions, alt text, set cover, replace and drag-to-reorder
  - bulk select, then move or delete
- **Album Photos** (`gallery.php`) shows every photo from every album, with a filter by album, bulk move and bulk delete.
- **Enquiries**:
  - search by name, phone, email or reference
  - filter by status, event type, service and event-date range
  - 7 statuses: New, Contacted, Follow-up, Quotation Sent, Confirmed, Completed, Cancelled
  - a detail page with internal notes, WhatsApp and email reply buttons, delete and CSV export
- Media Library and Settings:
  - **Settings** covers logo, favicon, contacts, WhatsApp, socials, footer and SEO.
  - **Booking options** sets event types, services, **budget ranges**, portfolio categories and maximum event days.
  - **Administrators** has two roles: admin and editor.
- Profile and logout.

## 2. Folder structure

```
dreamm-framme-event/
├── index.php about.php services.php portfolio.php films.php contact.php gallery.php
├── privacy-policy.php terms.php sitemap.php robots.php      public pages
├── actions/booking-submit.php                               booking form endpoint (AJAX)
├── admin/
│   ├── dashboard, pages, hero-slider, services, portfolio, albums, gallery, films,
│   │   testimonials, team, enquiries, media, settings, profile, login, setup, logout (.php)
│   ├── actions/   collection-save/delete/order · album-photo · video-upload · media-* ·
│   │              enquiry-update/delete/note/export · settings-save · page-save · profile-save …
│   ├── css/admin.css   js/admin.js · media.js
├── config/        config.php · database.php                               ← web access denied
├── includes/      bootstrap · db · auth · csrf · form · uploads · schema · defaults · functions
│                  header · navbar · footer · admin-* · sections/*.php      ← web access denied
├── assets/        css/ · js/ (main, animations, hero-slider, contact) · images/ (sample placeholders)
│                  videos/ (sample hero clips) · uploads/ (your uploads, PHP execution disabled)
├── database/dreamm_framme_event.sql                                        ← web access denied
└── storage/logs/                                                           ← web access denied
```

**Database (16 tables):**
- **Content**: `admins`, `pages`, `page_sections`, `hero_slides`, `services`, `portfolio_albums`, `portfolio_images`, `films`, `testimonials`, `team_members`.
- **Bookings**: `enquiries`, `enquiry_notes`.
- **System**: `media`, `settings`, `activity_logs`, `login_attempts`.
- Foreign keys:
  - Deleting an album deletes its photos (`ON DELETE CASCADE`); the files are removed from disk too.
  - Deleting an enquiry deletes its notes.
  - Deleting an admin keeps their media and log entries (`SET NULL`).

## 3. Install locally — XAMPP or MAMP

1. **Start the servers.**
   - XAMPP: start **Apache** and **MySQL**.
   - MAMP: start the servers. MySQL is `root` / `root` on port **8889**, and phpMyAdmin is at `http://localhost:8888/phpMyAdmin5/`.
2. **Copy the files.** Put the `dreamm-framme-event` folder into `htdocs` (XAMPP: `C:\xampp\htdocs\`; MAMP: `/Applications/MAMP/htdocs/`).
3. **Create the database.** In **phpMyAdmin**, click **New**, name it `dreamm_framme_event` with collation `utf8mb4_unicode_ci`, and click **Create**.
4. **Import the data.** Select the database, open **Import**, choose `database/dreamm_framme_event.sql` and click **Import**. You should see **16 tables**.
5. **Set the database credentials** in `config/database.php`:
   - XAMPP: host `127.0.0.1`, user `root`, password empty, port `3306`.
   - MAMP: user `root`, password `root`, port `8889`.
6. **Set your secrets** in `config/config.php`: change `SETUP_KEY` and `APP_SECRET` to your own long random phrases.
7. **Open the website** at `http://localhost/dreamm-framme-event/` (MAMP: `http://localhost:8888/dreamm-framme-event/`).
8. **Create the first administrator.** Open `…/admin/setup.php` and enter the `SETUP_KEY`, your name, email and a password (10+ characters, with letters and numbers). The page locks itself afterwards. You may delete `admin/setup.php`.
9. **Log in** at `…/admin/`.

> There is **no default username or password**. Add more admins or editors in **Settings → Administrators**.
>
> *Alternative to setup.php (phpMyAdmin only):*
> 1. Run `php -r "echo password_hash('YourPassword123', PASSWORD_DEFAULT);"` to get a password hash.
> 2. In the `admins` table, run:
>    `INSERT INTO admins (name, email, password_hash, role, status) VALUES ('Your Name', 'you@example.com', '<the hash>', 'admin', 'active');`

## 4. Hero videos — recommended settings

- **Length and size**:
  - Use 8–20 s silent clips at 1920×1080 (or 1280×720).
  - Aim for **under 8 MB**; the upload limit is 60 MB.
  - Export as **MP4 / H.264, “Web optimized / fast start”** (for example with HandBrake).
- **WebM** (optional) is often 30–50 % smaller.
- **Portrait phone clip** (optional): MP4, 9:16, about 720×1280. Without it, phones use the normal video or the poster.
- **Always upload a poster photo.** It appears instantly and replaces the video on slow connections.
- **External video URLs** must link directly to an `.mp4` or `.webm` file (for example on a CDN). YouTube and Vimeo pages cannot be silent background videos. Use them on the **Films** page instead.
- **Server limits** for video uploads:
  - `.htaccess` sets `upload_max_filesize 64M` and `post_max_size 70M`.
  - On cPanel, set the same values in **MultiPHP INI Editor**.
  - On XAMPP/MAMP, edit `php.ini` and restart Apache.

## 5. Deploy on cPanel with your domain and HTTPS

1. **Domain and SSL.** Point the domain's nameservers or A record to your hosting. In cPanel, run **SSL/TLS Status → AutoSSL** (Let's Encrypt).
2. **Database.** In **MySQL Databases**:
   - create a database and a user with a strong password,
   - add the user to the database with **ALL PRIVILEGES**.
3. **Import.** In **phpMyAdmin**, import `database/dreamm_framme_event.sql`.
4. **Upload the files.** In **File Manager**, upload the zip to `public_html/` and **Extract**. Make sure `.htaccess` files are included (Settings → show hidden files).
5. **Database credentials.** Edit `config/database.php` with the cPanel database name, user and password.
6. **App settings.** Edit `config/config.php`:
   - `APP_ENV = 'production'`
   - new `SETUP_KEY` and `APP_SECRET`
   - `BASE_URL_OVERRIDE = 'https://www.yourdomain.com/'`
7. **PHP.** In **MultiPHP Manager**, choose PHP **8.2+**. Enable `pdo_mysql`, `gd` (with WebP), `fileinfo` and `mbstring`, and set the upload limits from §4.
8. **Permissions.** Use folders `755` and files `644`. `assets/uploads/` and `storage/logs/` must be writable.
9. **Create the admin and lock down setup.** Open `https://yourdomain.com/admin/setup.php`, create the admin, then delete `setup.php`.
10. **Force HTTPS.** In `.htaccess`, uncomment the **force HTTPS** rule and the `Strict-Transport-Security` header.
11. **Search Console.** Submit `https://yourdomain.com/sitemap.xml` in Google Search Console.

## 6. Security

| Area | Implementation |
|---|---|
| SQL injection | PDO prepared statements everywhere with real prepares. Table and column names come only from code. |
| XSS | All output is escaped with `e()`. Headings only allow `*word*` → `<em>`. `javascript:` links are rejected and icon classes are whitelisted. |
| CSRF | Every form and AJAX call carries a token. Destructive actions are POST-only (otherwise 405), and a missing token returns 419. |
| Passwords | Stored with `password_hash()` (never plain text) and rehashed automatically. The session ID is regenerated on login. |
| Sessions | Cookies are HttpOnly, SameSite and Secure on HTTPS. Sessions also use a browser fingerprint, a 2-hour idle timeout and a POST logout. |
| Brute force | Logins lock after 5 failures per email or 10 per IP within 15 minutes. |
| Access | Admin pages and actions require a session; signed-out requests get 401 or a redirect. The `editor` role cannot change settings or accounts. There is no public registration. |
| Image uploads | **Checks:** JPG, PNG and WebP only, real MIME check, `getimagesize`, size and pixel limits, and double extensions (`.php.jpg`) blocked. **Storage:** files are re-encoded to WebP with GD and saved under random names. PHP is disabled in `assets/uploads/`. |
| Video uploads | Accepted: MP4 or WebM only, up to 60 MB. Checked: MIME type plus the container signature (`ftyp` / EBML). Stored under random names. |
| Booking spam | CSRF, honeypot, signed minimum fill time, 3 enquiries per IP per hour, duplicate detection over 24 h, and server-side validation against the options in Settings. |
| Private files | `.htaccess` denies `config/`, `includes/`, `database/`, `storage/`, `*.sql`, `*.md` and dotfiles. Every include also exits when opened directly. |

## 7. Before launch — replace the placeholders

The project ships with **sample content only**:
- **Photos and videos**: abstract bokeh photos and generated sample hero clips. Upload real work.
- **Album stories, team and testimonials**: placeholder text marked “Couple Name” or “replace this”. Publish only real testimonials, with the couple's permission.
- **Statistics**: example numbers. Edit or hide them in Pages → Home → Statistics; do not publish figures that aren't true.
- **Film links**: sample YouTube/Vimeo links.
- **Contacts**: `hello@yourdomain.com`, `+91 00000 00000` and the address in Settings.
- **Legal text**: the privacy policy and terms (have them reviewed).
- **SEO**: page SEO titles and descriptions, the default share image and the favicon.

## 8. Customising

- **New field on a section**: add it to `includes/schema.php`, then print it in `includes/sections/<template>.php`. No database change is needed; extra fields live in the JSON `extra` column.
- **Booking dropdowns**: change them in **Settings → Booking**. The server only accepts the options listed there.
- **Colours and fonts**: edit the `:root` variables at the top of `assets/css/style.css`.

## 9. Troubleshooting

| Problem | Fix |
|---|---|
| “Can't connect to the database” | MySQL is stopped, `config/database.php` has wrong credentials (MAMP uses port 8889 and `root`/`root`), or the SQL was not imported. |
| Video upload fails | Raise `upload_max_filesize` and `post_max_size` (see §4) or compress the clip. |
| Hero shows the photo but no video | The browser blocked autoplay, the visitor is on Save-Data or 2G, reduced motion is on, or the file isn't H.264 MP4. The poster is the intended fallback. |
| Photos stay JPG/PNG | The server's GD extension has no WebP support. Photos still work. |
| Broken CSS or links in a subfolder | Set `BASE_URL_OVERRIDE` to the full URL ending with `/`. |
| 500 error on cPanel | Your host doesn't allow `php_value` in `.htaccess`. Remove the `<IfModule mod_php.c>` block and set the limits in MultiPHP INI Editor. |
| `/album/slug` gives 404 | `mod_rewrite` is off. `gallery.php?album=slug` always works. |

## Home: "Recent work" + "Made for your phone" (reels)
- **Recent work** — mosaic of your latest films (featured first; one large tile + smaller tiles). Films come from **Admin → Films**. Heading, number of films and button: **Admin → Pages & Sections → Home → Recent work**.
- **Made for your phone** — swipeable strip of vertical 9:16 reels. Manage them in **Admin → Reels** (upload an MP4, or paste a YouTube Shorts / Vimeo / .mp4 link, plus a portrait cover photo). Uploaded MP4s play silently while on screen; a tap opens the reel with sound.
- Existing database? Run `database/update-reels.sql` once in phpMyAdmin (creates the `reels` table, adds 6 sample reels and the two Home sections). It never deletes or overwrites your content.
- **Films can be uploaded as MP4** (Admin → Films → "Upload film"), or added as a YouTube/Vimeo link as before. Uploaded films preview silently on hover in "Recent work" (with a progress line and automatic length) and open full-screen with sound. Existing database? Run `database/update-films-upload.sql` once.

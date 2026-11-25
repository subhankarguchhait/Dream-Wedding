-- =============================================================================
--  DREAMM FRAMME EVENT — MySQL 8 / MariaDB 10.4+ database
--  1. In phpMyAdmin create a database named  dreamm_framme_event  (collation utf8mb4_unicode_ci)
--  2. Select it, open the Import tab, choose this file and click Import.
--  The first administrator is created afterwards with admin/setup.php (no default password).
-- =============================================================================

SET NAMES utf8mb4;
SET time_zone = '+05:30';
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS enquiry_notes;
DROP TABLE IF EXISTS enquiries;
DROP TABLE IF EXISTS media;
DROP TABLE IF EXISTS portfolio_images;
DROP TABLE IF EXISTS portfolio_albums;
DROP TABLE IF EXISTS reels;
DROP TABLE IF EXISTS films;
DROP TABLE IF EXISTS hero_slides;
DROP TABLE IF EXISTS team_members;
DROP TABLE IF EXISTS testimonials;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS page_sections;
DROP TABLE IF EXISTS pages;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS admins;

SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- Administrators (passwords are bcrypt/argon hashes from password_hash())
-- -----------------------------------------------------------------------------
CREATE TABLE admins (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name            VARCHAR(120) NOT NULL,
  email           VARCHAR(190) NOT NULL,
  password_hash   VARCHAR(255) NOT NULL,
  role            ENUM('admin','editor') NOT NULL DEFAULT 'editor',
  status          ENUM('active','disabled') NOT NULL DEFAULT 'active',
  last_login      DATETIME NULL DEFAULT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_admins_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Failed/successful logins for rate limiting (no foreign key: attempts may use unknown emails)
CREATE TABLE login_attempts (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email           VARCHAR(190) NOT NULL,
  ip_address      VARCHAR(45) NOT NULL,
  success         TINYINT(1) NOT NULL DEFAULT 0,
  attempted_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_login_ip_time (ip_address, attempted_at),
  KEY idx_login_email_time (email, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Pages & their sections
--   Common fields live in columns; section-specific fields (lists, extra buttons…)
--   live in the JSON "extra" column, so new section types need no schema change.
-- -----------------------------------------------------------------------------
CREATE TABLE pages (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  page_name         VARCHAR(100) NOT NULL,
  page_slug         VARCHAR(100) NOT NULL,
  meta_title        VARCHAR(160) NOT NULL DEFAULT '',
  meta_description  VARCHAR(320) NOT NULL DEFAULT '',
  status            ENUM('published','draft') NOT NULL DEFAULT 'published',
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pages_slug (page_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE page_sections (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  page_id           INT UNSIGNED NOT NULL,
  section_key       VARCHAR(60) NOT NULL,
  section_title     VARCHAR(255) NOT NULL DEFAULT '',
  section_subtitle  VARCHAR(255) NOT NULL DEFAULT '',
  section_content   TEXT NULL,
  image             VARCHAR(500) NOT NULL DEFAULT '',
  button_text       VARCHAR(100) NOT NULL DEFAULT '',
  button_url        VARCHAR(500) NOT NULL DEFAULT '',
  extra             LONGTEXT NULL CHECK (extra IS NULL OR JSON_VALID(extra)),
  display_order     INT NOT NULL DEFAULT 0,
  is_visible        TINYINT(1) NOT NULL DEFAULT 1,
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_section_page_key (page_id, section_key),
  KEY idx_section_order (page_id, display_order),
  CONSTRAINT fk_sections_page FOREIGN KEY (page_id) REFERENCES pages (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Home page hero video slider (Admin → Hero Slider)
-- -----------------------------------------------------------------------------
CREATE TABLE hero_slides (
  id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  eyebrow               VARCHAR(80) NOT NULL DEFAULT '',
  heading               VARCHAR(255) NOT NULL,
  subtitle              VARCHAR(300) NOT NULL DEFAULT '',
  video_path            VARCHAR(500) NOT NULL DEFAULT '',
  video_webm_path       VARCHAR(500) NOT NULL DEFAULT '',
  mobile_video_path     VARCHAR(500) NOT NULL DEFAULT '',
  video_url             VARCHAR(500) NOT NULL DEFAULT '',
  poster_image          VARCHAR(500) NOT NULL DEFAULT '',
  primary_button_text   VARCHAR(60) NOT NULL DEFAULT '',
  primary_button_url    VARCHAR(500) NOT NULL DEFAULT '',
  secondary_button_text VARCHAR(60) NOT NULL DEFAULT '',
  secondary_button_url  VARCHAR(500) NOT NULL DEFAULT '',
  duration              TINYINT UNSIGNED NOT NULL DEFAULT 8,
  overlay_opacity       TINYINT UNSIGNED NOT NULL DEFAULT 45,
  text_alignment        ENUM('left','center','right') NOT NULL DEFAULT 'left',
  display_order         INT NOT NULL DEFAULT 0,
  status                ENUM('published','draft') NOT NULL DEFAULT 'published',
  created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_slides_public (status, display_order),
  CONSTRAINT chk_slides_duration CHECK (duration BETWEEN 3 AND 60),
  CONSTRAINT chk_slides_overlay CHECK (overlay_opacity <= 90)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Services
-- -----------------------------------------------------------------------------
CREATE TABLE services (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title           VARCHAR(150) NOT NULL,
  slug            VARCHAR(160) NOT NULL,
  tagline         VARCHAR(160) NOT NULL DEFAULT '',
  icon            VARCHAR(80) NOT NULL DEFAULT '',
  cover_image     VARCHAR(500) NOT NULL DEFAULT '',
  description     TEXT NULL,
  details         TEXT NULL,
  features        LONGTEXT NULL CHECK (features IS NULL OR JSON_VALID(features)),
  price_note      VARCHAR(200) NOT NULL DEFAULT '',
  gallery         LONGTEXT NULL CHECK (gallery IS NULL OR JSON_VALID(gallery)),
  is_featured     TINYINT(1) NOT NULL DEFAULT 0,
  display_order   INT NOT NULL DEFAULT 0,
  status          ENUM('published','draft') NOT NULL DEFAULT 'published',
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_services_slug (slug),
  KEY idx_services_public (status, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Portfolio albums and their photos (photos are deleted together with the album)
-- -----------------------------------------------------------------------------
CREATE TABLE portfolio_albums (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title           VARCHAR(150) NOT NULL,
  couple_names    VARCHAR(150) NOT NULL DEFAULT '',
  slug            VARCHAR(160) NOT NULL,
  category        VARCHAR(80) NOT NULL DEFAULT 'Wedding',
  event_date      DATE NULL DEFAULT NULL,
  location        VARCHAR(150) NOT NULL DEFAULT '',
  cover_image     VARCHAR(500) NOT NULL DEFAULT '',
  description     TEXT NULL,
  is_featured     TINYINT(1) NOT NULL DEFAULT 0,
  display_order   INT NOT NULL DEFAULT 0,
  status          ENUM('published','draft') NOT NULL DEFAULT 'published',
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_albums_slug (slug),
  KEY idx_albums_public (status, is_featured, display_order),
  KEY idx_albums_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE portfolio_images (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  album_id        INT UNSIGNED NOT NULL,
  file_path       VARCHAR(500) NOT NULL,
  thumb_path      VARCHAR(500) NOT NULL DEFAULT '',
  width           INT UNSIGNED NULL DEFAULT NULL,
  height          INT UNSIGNED NULL DEFAULT NULL,
  caption         VARCHAR(255) NOT NULL DEFAULT '',
  alt_text        VARCHAR(190) NOT NULL DEFAULT '',
  display_order   INT NOT NULL DEFAULT 0,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_images_album (album_id, display_order),
  KEY idx_images_created (created_at),
  CONSTRAINT fk_images_album FOREIGN KEY (album_id) REFERENCES portfolio_albums (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Films (YouTube / Vimeo / external MP4 links — large videos are not stored on the server)
-- -----------------------------------------------------------------------------
CREATE TABLE films (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title           VARCHAR(150) NOT NULL,
  slug            VARCHAR(160) NOT NULL,
  couple_names    VARCHAR(150) NOT NULL DEFAULT '',
  category        VARCHAR(60) NOT NULL DEFAULT 'Wedding Film',
  video_url       VARCHAR(500) NOT NULL,
  thumbnail       VARCHAR(500) NOT NULL DEFAULT '',
  description     TEXT NULL,
  location        VARCHAR(150) NOT NULL DEFAULT '',
  event_date      DATE NULL DEFAULT NULL,
  duration_label  VARCHAR(20) NOT NULL DEFAULT '',
  is_featured     TINYINT(1) NOT NULL DEFAULT 0,
  display_order   INT NOT NULL DEFAULT 0,
  status          ENUM('published','draft') NOT NULL DEFAULT 'published',
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_films_slug (slug),
  KEY idx_films_public (status, is_featured, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vertical 9:16 reels for the Home page "Made for your phone" strip (Admin → Reels)
CREATE TABLE reels (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title           VARCHAR(120) NOT NULL,
  couple_names    VARCHAR(150) NOT NULL DEFAULT '',
  category        VARCHAR(60) NOT NULL DEFAULT 'Reel',
  video_path      VARCHAR(500) NOT NULL DEFAULT '',
  video_url       VARCHAR(500) NOT NULL DEFAULT '',
  poster_image    VARCHAR(500) NOT NULL DEFAULT '',
  display_order   INT NOT NULL DEFAULT 0,
  status          ENUM('published','draft') NOT NULL DEFAULT 'published',
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_reels_public (status, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Testimonials & team
-- -----------------------------------------------------------------------------
CREATE TABLE testimonials (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  client_name     VARCHAR(120) NOT NULL,
  event_type      VARCHAR(80) NOT NULL DEFAULT '',
  location        VARCHAR(120) NOT NULL DEFAULT '',
  testimonial     TEXT NOT NULL,
  client_image    VARCHAR(500) NOT NULL DEFAULT '',
  rating          TINYINT UNSIGNED NOT NULL DEFAULT 5,
  display_order   INT NOT NULL DEFAULT 0,
  status          ENUM('published','draft') NOT NULL DEFAULT 'published',
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_testimonials_public (status, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE team_members (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name            VARCHAR(120) NOT NULL,
  designation     VARCHAR(120) NOT NULL DEFAULT '',
  bio             TEXT NULL,
  profile_image   VARCHAR(500) NOT NULL DEFAULT '',
  social_links    LONGTEXT NULL CHECK (social_links IS NULL OR JSON_VALID(social_links)),
  display_order   INT NOT NULL DEFAULT 0,
  status          ENUM('published','draft') NOT NULL DEFAULT 'published',
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_team_public (status, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Media library (images in assets/uploads/YYYY/MM/, hero videos in assets/uploads/videos/YYYY/MM/)
-- -----------------------------------------------------------------------------
CREATE TABLE media (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  file_name       VARCHAR(190) NOT NULL,
  original_name   VARCHAR(190) NOT NULL DEFAULT '',
  file_path       VARCHAR(500) NOT NULL,
  file_type       VARCHAR(60) NOT NULL,
  file_size       INT UNSIGNED NOT NULL DEFAULT 0,
  thumb_path      VARCHAR(500) NOT NULL DEFAULT '',
  width           INT UNSIGNED NULL DEFAULT NULL,
  height          INT UNSIGNED NULL DEFAULT NULL,
  alt_text        VARCHAR(255) NOT NULL DEFAULT '',
  folder          VARCHAR(40) NOT NULL DEFAULT 'general',
  uploaded_by     INT UNSIGNED NULL DEFAULT NULL,
  uploaded_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_media_path (file_path(255)),
  KEY idx_media_folder (folder, uploaded_at),
  CONSTRAINT fk_media_admin FOREIGN KEY (uploaded_by) REFERENCES admins (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Booking enquiries (“Check your date”) and internal notes
-- -----------------------------------------------------------------------------
CREATE TABLE enquiries (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  enquiry_id      VARCHAR(30) NOT NULL,
  name            VARCHAR(100) NOT NULL,
  partner_name    VARCHAR(100) NOT NULL DEFAULT '',
  email           VARCHAR(150) NOT NULL,
  phone           VARCHAR(20) NOT NULL DEFAULT '',
  event_type      VARCHAR(60) NOT NULL DEFAULT '',
  event_date      DATE NULL DEFAULT NULL,
  event_location  VARCHAR(150) NOT NULL DEFAULT '',
  service         VARCHAR(100) NOT NULL DEFAULT '',
  budget          VARCHAR(80) NOT NULL DEFAULT '',
  event_days      TINYINT UNSIGNED NOT NULL DEFAULT 1,
  message         TEXT NOT NULL,
  status          ENUM('new','contacted','follow_up','quotation_sent','confirmed','completed','cancelled') NOT NULL DEFAULT 'new',
  message_hash    CHAR(64) NOT NULL,
  ip_address      VARCHAR(45) NOT NULL DEFAULT '',
  user_agent      VARCHAR(255) NOT NULL DEFAULT '',
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_enquiries_ref (enquiry_id),
  KEY idx_enquiries_status (status, created_at),
  KEY idx_enquiries_created (created_at),
  KEY idx_enquiries_event (event_date),
  KEY idx_enquiries_type (event_type),
  KEY idx_enquiries_email (email),
  KEY idx_enquiries_ip (ip_address, created_at),
  KEY idx_enquiries_hash (message_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE enquiry_notes (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  enquiry_id      INT UNSIGNED NOT NULL,
  admin_id        INT UNSIGNED NULL DEFAULT NULL,
  note            TEXT NOT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_notes_enquiry (enquiry_id, created_at),
  CONSTRAINT fk_notes_enquiry FOREIGN KEY (enquiry_id) REFERENCES enquiries (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_notes_admin FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Site-wide settings (key/value)
-- -----------------------------------------------------------------------------
CREATE TABLE settings (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  setting_key     VARCHAR(100) NOT NULL,
  setting_value   TEXT NULL,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_settings_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Audit trail of admin actions
-- -----------------------------------------------------------------------------
CREATE TABLE activity_logs (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  admin_id        INT UNSIGNED NULL DEFAULT NULL,
  action          VARCHAR(100) NOT NULL,
  description     VARCHAR(500) NOT NULL DEFAULT '',
  ip_address      VARCHAR(45) NOT NULL DEFAULT '',
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_activity_created (created_at),
  CONSTRAINT fk_activity_admin FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- Starter content (replace placeholders from the admin panel before launch)
-- =============================================================================

INSERT INTO settings (setting_key, setting_value, updated_at) VALUES
  ('site_name', 'Dreamm Weddings', '2026-11-04 10:00:00'),
  ('tagline', 'Wedding Photography & Films', '2026-11-04 10:00:00'),
  ('logo', 'assets/images/brand/dreamm-weddings-logo-horizontal.webp', '2026-11-04 10:00:00'),
  ('logo_stacked', 'assets/images/brand/dreamm-weddings-logo-web.webp', '2026-11-04 10:00:00'),
  ('favicon', 'assets/images/brand/favicon-192.png', '2026-11-04 10:00:00'),
  ('nav_cta_text', 'Book Your Date', '2026-11-04 10:00:00'),
  ('nav_cta_link', 'contact.php', '2026-11-04 10:00:00'),
  ('email', 'hello@yourdomain.com', '2026-11-04 10:00:00'),
  ('phone', '+91 00000 00000', '2026-11-04 10:00:00'),
  ('whatsapp', '+91 00000 00000', '2026-11-04 10:00:00'),
  ('hours', 'Mon – Sun · 10:00 AM – 8:00 PM (by appointment)', '2026-11-04 10:00:00'),
  ('address', 'Kolkata, West Bengal, India', '2026-11-04 10:00:00'),
  ('social_instagram', 'https://instagram.com/', '2026-11-04 10:00:00'),
  ('social_facebook', 'https://facebook.com/', '2026-11-04 10:00:00'),
  ('social_youtube', 'https://youtube.com/', '2026-11-04 10:00:00'),
  ('social_vimeo', '', '2026-11-04 10:00:00'),
  ('social_pinterest', '', '2026-11-04 10:00:00'),
  ('event_types', '["Wedding","Pre-Wedding","Engagement","Haldi","Mehendi","Reception","Birthday","Corporate Event","Other"]', '2026-11-04 10:00:00'),
  ('service_options', '["Wedding Photography","Wedding Cinematography","Pre-Wedding","Wedding Films","Complete Wedding Package","Event Photography","Other"]', '2026-11-04 10:00:00'),
  ('budget_options', '["Under ₹50,000","₹50,000 – ₹1,00,000","₹1,00,000 – ₹2,00,000","₹2,00,000 – ₹4,00,000","Above ₹4,00,000","Not sure yet"]', '2026-11-04 10:00:00'),
  ('portfolio_categories', '["Wedding","Pre-Wedding","Engagement","Haldi","Mehendi","Reception","Couple Portraits","Traditional Wedding","Candid Moments"]', '2026-11-04 10:00:00'),
  ('max_event_days', '7', '2026-11-04 10:00:00'),
  ('booking_note', 'Popular wedding dates are booked 6–12 months in advance. Share your date and we will confirm availability within 24 hours.', '2026-11-04 10:00:00'),
  ('booking_success', 'Thank you! We have received your enquiry and will confirm availability within 24 hours.', '2026-11-04 10:00:00'),
  ('footer_headline', 'Let''s tell *your* love story.', '2026-11-04 10:00:00'),
  ('footer_cta_text', 'Check Your Date', '2026-11-04 10:00:00'),
  ('footer_cta_link', 'contact.php', '2026-11-04 10:00:00'),
  ('footer_about', 'Dreamm Weddings is a wedding photography and cinematography studio capturing emotions, rituals and timeless moments with a cinematic eye.', '2026-11-04 10:00:00'),
  ('footer_copyright', '© {year} Dreamm Weddings. All rights reserved.', '2026-11-04 10:00:00'),
  ('seo_title', 'Dreamm Weddings', '2026-11-04 10:00:00'),
  ('seo_description', 'Luxury wedding photography, cinematic wedding films, pre-wedding shoots and event photography by Dreamm Weddings.', '2026-11-04 10:00:00'),
  ('seo_keywords', 'wedding photographer, wedding cinematography, pre-wedding shoot, candid wedding photography, wedding films, Kolkata', '2026-11-04 10:00:00'),
  ('og_image', '', '2026-11-04 10:00:00');

INSERT INTO pages (id, page_name, page_slug, meta_title, meta_description, status, created_at, updated_at) VALUES
  (1, 'Home', 'home', 'Wedding Photography & Cinematic Films', '', 'published', '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (2, 'About Us', 'about', 'About Us', 'The story, philosophy and team behind Dreamm Weddings.', 'published', '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (3, 'Services', 'services', 'Wedding Photography & Film Services', 'Wedding photography, cinematography, pre-wedding, haldi, mehendi, reception and event coverage.', 'published', '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (4, 'Portfolio', 'portfolio', 'Wedding Portfolio', 'Wedding, pre-wedding, haldi, mehendi and reception photo albums by Dreamm Weddings.', 'published', '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (5, 'Films', 'films', 'Cinematic Wedding Films', 'Cinematic wedding films, highlights and pre-wedding films by Dreamm Weddings.', 'published', '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (6, 'Contact', 'contact', 'Contact & Booking Enquiry', 'Check availability and book Dreamm Weddings for your wedding or event.', 'published', '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (7, 'Privacy Policy', 'privacy', 'Privacy Policy', 'How Dreamm Weddings handles your information.', 'published', '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (8, 'Terms', 'terms', 'Terms & Conditions', 'Terms for booking Dreamm Weddings.', 'published', '2026-11-04 10:00:00', '2026-11-04 10:00:00');

INSERT INTO page_sections (page_id, section_key, section_title, section_subtitle, section_content, image, button_text, button_url, extra, display_order, is_visible, created_at, updated_at) VALUES
  (1, 'hero', '', '', '', '', '', '', '{"scroll_text":"Scroll"}', 1, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (1, 'intro', 'We don''t just take photos — we keep *feelings*.', 'Welcome to Dreamm Weddings', 'Dreamm Weddings is a wedding photography and cinematography studio for couples who want their story told with honesty and elegance.

We blend candid moments, timeless portraits and cinematic films so that years from now you can feel your wedding day all over again.', 'assets/images/sections/intro.jpg', 'Our Story', 'about.php', '{"signature":"Team Dreamm Weddings","image2":"assets/images/albums/rohan-meera/03.jpg"}', 2, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (1, 'stories', 'Love stories we''ve *framed*', 'Featured wedding stories', '', '', 'Explore All Stories', 'portfolio.php', '{"description":"A few of the celebrations we have been honoured to capture.","limit":4}', 3, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (1, 'services', 'Our *services*', 'What we do', '', '', 'View All Services', 'services.php', '{"description":"Photography and films for every ritual, from the first look to the last dance.","limit":8}', 4, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (1, 'recent', 'Recent *work*', 'Selected films', '', '', 'All Films', 'films.php', '{"description":"","limit":5}', 5, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (1, 'reels', 'Made for *your phone*', 'Highlights & reels', '', '', '', '', '{"description":"Short vertical edits of the moments everyone asks for — tap any reel to watch with sound.","limit":10}', 6, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (1, 'film', 'Your wedding, as a *film*', 'Cinematic films', '', 'assets/images/sections/film-bg.jpg', 'Watch More Films', 'films.php', '{"description":"Feel the emotion of the day again with a cinematic wedding film."}', 7, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (1, 'why', 'Crafted with *heart*', 'Why choose us', '', '', '', '', '{"description":"Six promises we make to every couple.","items":[{"icon":"fa-solid fa-feather-pointed","title":"Creative storytelling","text":"Every frame is planned around your story, rituals and personalities."},{"icon":"fa-solid fa-camera","title":"Professional equipment","text":"Full-frame cameras, prime lenses, gimbals, drones and cinema lighting."},{"icon":"fa-solid fa-user-group","title":"Experienced team","text":"Photographers and filmmakers who know Indian weddings inside out."},{"icon":"fa-solid fa-film","title":"Cinematic editing","text":"Colour grading, sound design and storytelling edits that move people."},{"icon":"fa-solid fa-magnifying-glass","title":"Attention to detail","text":"From the mehendi patterns to the last pheras, nothing is missed."},{"icon":"fa-solid fa-heart","title":"Personalised experience","text":"A dedicated coordinator and a plan made just for your celebration."}]}', 8, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (1, 'stats', '', '', '', '', '', '', '{"items":[{"value":"250","suffix":"+","label":"Weddings captured"},{"value":"180","suffix":"+","label":"Films created"},{"value":"500","suffix":"+","label":"Happy couples"},{"value":"8","suffix":"","label":"Years of experience"}]}', 9, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (1, 'testimonials', 'Love *notes*', 'Kind words', '', '', '', '', '{"description":""}', 10, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (1, 'social', 'On *Instagram*', 'Follow our journey', '', '', '', '', '{"description":"Daily moments from weddings, rituals and behind the scenes.","handle":"@dreammframmeevent","images":["assets/images/albums/aarav-isha/02.jpg","assets/images/albums/kabir-ananya/03.jpg","assets/images/albums/vivaan-diya/01.jpg","assets/images/albums/arjun-sneha/05.jpg","assets/images/albums/rohan-meera/06.jpg","assets/images/albums/dev-tara/02.jpg"]}', 11, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (1, 'cta', 'Let''s capture your *beautiful beginning.*', 'Now booking', 'Tell us about your celebration and we will confirm availability for your date within 24 hours.', 'assets/images/sections/cta.jpg', 'Check Your Date', 'contact.php', '{"cta2_text":"Chat on WhatsApp","cta2_url":"#whatsapp"}', 12, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (2, 'hero', 'Storytellers of *love*', 'About us', 'A small team of photographers and filmmakers devoted to weddings.', 'assets/images/pages/about.jpg', '', '', '{}', 1, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (2, 'story', 'It began with one *frame*', 'Our story', 'Dreamm Weddings started with a single camera and a belief that wedding photographs should feel, not just look, beautiful.

Today our team captures weddings across India, but every couple still gets the same care as our very first wedding.', 'assets/images/sections/story.jpg', '', '', '{}', 2, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (2, 'who', 'Photographers, filmmakers, *friends*', 'Who we are', 'We are a close-knit team of candid photographers, traditional photographers, cinematographers and editors who work together on every wedding.', 'assets/images/albums/kabir-ananya/02.jpg', '', '', '{"points":["Candid & traditional photographers","Cinematographers & drone operators","Editors & colourists","A dedicated wedding coordinator"]}', 3, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (2, 'philosophy', 'Real moments, *beautifully* told', 'Our philosophy', 'We believe the most beautiful photographs happen when people forget the camera. Our job is to be present, invisible and ready.', 'assets/images/sections/philosophy.jpg', '', '', '{"signature":"Dreamm Weddings"}', 4, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (2, 'mission_vision', 'Mission & *Vision*', 'Purpose', '', 'assets/images/sections/mission.jpg', '', '', '{"description":"","mission_title":"Our Mission","mission":"To preserve the emotions of every wedding with honest photography and cinematic films that families treasure for generations.","vision_title":"Our Vision","vision":"To be the most loved wedding storytelling studio in India, known for warmth, artistry and trust."}', 5, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (2, 'approach', 'How we *work*', 'Creative approach', '', '', '', '', '{"description":"","steps":[{"title":"Meet & listen","text":"We learn your story, traditions and the moments that matter most to you."},{"title":"Plan the story","text":"A shot list, timeline and team plan built around your events."},{"title":"Capture","text":"Candid, traditional and cinematic coverage — calm, unobtrusive and on time."},{"title":"Craft & deliver","text":"Hand-edited photos, albums and films delivered in a private online gallery."}]}', 6, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (2, 'team', 'The people behind the *lens*', 'Our team', '', '', '', '', '{"description":""}', 7, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (2, 'equipment', 'Tools of the *craft*', 'Our equipment', '', '', '', '', '{"description":"Professional gear chosen for low light, long days and cinematic results.","items":[{"icon":"fa-solid fa-camera","title":"Full-frame cameras","text":"Mirrorless bodies with dual card slots for safe backups."},{"icon":"fa-solid fa-circle-dot","title":"Prime & zoom lenses","text":"Fast primes for portraits and zooms for rituals."},{"icon":"fa-solid fa-video","title":"Cinema cameras & gimbals","text":"Smooth, stabilised movement for films."},{"icon":"fa-solid fa-helicopter","title":"Drones","text":"Aerial shots of venues and processions (where permitted)."},{"icon":"fa-solid fa-lightbulb","title":"Lighting","text":"Soft, flattering light for evening ceremonies."},{"icon":"fa-solid fa-microphone","title":"Audio","text":"Clear vows and speeches for your film."}]}', 8, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (2, 'why', 'Why couples choose *us*', 'Why choose us', '', '', '', '', '{"description":"","items":[{"icon":"fa-solid fa-feather-pointed","title":"Creative storytelling","text":"Every frame is planned around your story, rituals and personalities."},{"icon":"fa-solid fa-camera","title":"Professional equipment","text":"Full-frame cameras, prime lenses, gimbals, drones and cinema lighting."},{"icon":"fa-solid fa-user-group","title":"Experienced team","text":"Photographers and filmmakers who know Indian weddings inside out."},{"icon":"fa-solid fa-film","title":"Cinematic editing","text":"Colour grading, sound design and storytelling edits that move people."}]}', 9, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (2, 'cta', 'Let''s write your story *together.*', 'Now booking', 'Share your plans and we will craft a coverage plan for your celebration.', 'assets/images/sections/cta.jpg', 'Check Your Date', 'contact.php', '{"cta2_text":"Chat on WhatsApp","cta2_url":"#whatsapp"}', 10, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (3, 'hero', 'Every ritual, *beautifully* covered', 'Services', 'Photography and films for weddings, pre-weddings and celebrations.', 'assets/images/pages/services.jpg', '', '', '{}', 1, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (3, 'list', 'Our *services*', 'What we offer', '', '', '', '', '{"description":"Choose individual services or combine them into a complete wedding package."}', 2, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (3, 'process', 'Simple, *stress-free* booking', 'Booking process', '', '', '', '', '{"description":"","steps":[{"title":"Enquire","text":"Send your date, venue and the events you would like covered."},{"title":"Consult","text":"We plan coverage together on a call or at our studio."},{"title":"Confirm","text":"Reserve your date with a signed agreement and advance."},{"title":"Celebrate","text":"Enjoy your day — we take care of the memories."}]}', 3, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (3, 'cta', 'Not sure what you *need?*', 'Now booking', 'Tell us about your events and we will recommend the right coverage.', 'assets/images/sections/cta.jpg', 'Check Your Date', 'contact.php', '{"cta2_text":"Chat on WhatsApp","cta2_url":"#whatsapp"}', 4, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (4, 'hero', 'Stories in *frames*', 'Portfolio', 'Browse complete wedding albums and our favourite moments.', 'assets/images/pages/portfolio.jpg', '', '', '{}', 1, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (4, 'albums', 'Real weddings, real *emotions*', 'Wedding albums', '', '', '', '', '{"description":"Choose a category or open an album to see the full story."}', 2, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (4, 'moments', 'Favourite *frames*', 'Moments', '', '', '', '', '{"description":"Tap any photo to view it full screen.","limit":24}', 3, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (4, 'cta', 'Imagine *your* album here.', 'Now booking', 'Let us capture your celebration with the same care.', 'assets/images/sections/cta.jpg', 'Check Your Date', 'contact.php', '{"cta2_text":"Chat on WhatsApp","cta2_url":"#whatsapp"}', 4, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (5, 'hero', 'Love, in *motion*', 'Cinematic films', 'Wedding films that let you hear the laughter and feel the tears again.', 'assets/images/pages/films.jpg', '', '', '{}', 1, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (5, 'featured', 'This week''s *favourite*', 'Featured film', '', '', '', '', '{"description":""}', 2, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (5, 'grid', 'Wedding films & *highlights*', 'Film gallery', '', '', '', '', '{"description":"Press play on any film."}', 3, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (5, 'cta', 'Want a film like *this?*', 'Now booking', 'Ask us about wedding films, highlights and teasers.', 'assets/images/sections/cta.jpg', 'Book a Wedding Film', 'contact.php', '{"cta2_text":"Chat on WhatsApp","cta2_url":"#whatsapp"}', 4, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (6, 'hero', 'Let''s plan your *story*', 'Contact', 'Share a few details and we will confirm availability for your date.', 'assets/images/pages/contact.jpg', '', '', '{}', 1, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (6, 'form', 'We''d love to *hear* from you', 'Get in touch', 'Fields marked * are required.', '', '', '', '{"description":"Call, WhatsApp or visit our studio by appointment.","form_title":"Booking *enquiry*"}', 2, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (6, 'map', '', '', '', '', '', '', '{"embed_url":"https://www.google.com/maps?q=Kolkata%2C%20West%20Bengal&output=embed"}', 3, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (6, 'cta', 'Prefer to *talk?*', 'Now booking', 'Message us on WhatsApp and we usually reply within the hour.', 'assets/images/sections/cta.jpg', 'Chat on WhatsApp', '#whatsapp', '{"cta2_text":"","cta2_url":""}', 4, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (7, 'hero', 'Privacy *Policy*', 'Legal', 'How we handle the information you share with us.', 'assets/images/pages/legal.jpg', '', '', '{}', 1, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (7, 'body', 'Privacy *Policy*', '', '## Placeholder text

This page is a starting template. Replace it with your own wording, reviewed by a qualified professional, before publishing your website.

## Information we collect

When you send a booking enquiry we receive the details you enter in the form, such as your name, email, phone number, event date and message. We use them only to reply to your enquiry and to plan your event.

## Your choices

You can ask us to update or delete your details at any time by contacting us using the details on our Contact page.', '', '', '', '{"description":"Last updated: November 2026"}', 2, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (8, 'hero', 'Terms & *Conditions*', 'Legal', 'The terms that apply to bookings and use of this website.', 'assets/images/pages/legal.jpg', '', '', '{}', 1, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (8, 'body', 'Terms & *Conditions*', '', '## Placeholder text

This page is a starting template. Replace it with your own wording, reviewed by a qualified professional, before publishing your website.

## Information we collect

When you send a booking enquiry we receive the details you enter in the form, such as your name, email, phone number, event date and message. We use them only to reply to your enquiry and to plan your event.

## Your choices

You can ask us to update or delete your details at any time by contacting us using the details on our Contact page.', '', '', '', '{"description":"Last updated: November 2026"}', 2, 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00');

INSERT INTO hero_slides (id, eyebrow, heading, subtitle, video_path, video_webm_path, mobile_video_path, video_url, poster_image, primary_button_text, primary_button_url, secondary_button_text, secondary_button_url, duration, overlay_opacity, text_alignment, status, display_order, created_at, updated_at) VALUES
  (1, 'Dreamm Weddings', 'Every Love Story Deserves to Be *Remembered.*', 'Capturing timeless moments through cinematic wedding photography.', 'assets/videos/slide-1.mp4', 'assets/videos/slide-1.webm', 'assets/videos/slide-1-mobile.mp4', '', 'assets/images/hero/hero-1.jpg', 'Explore Our Stories', 'portfolio.php', 'Book Your Date', 'contact.php', 8, 45, 'left', 'published', 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (2, 'Wedding Films', 'Your Beautiful Beginning, Our *Cinematic* Story.', 'Wedding films created with emotion, artistry and elegance.', 'assets/videos/slide-2.mp4', 'assets/videos/slide-2.webm', 'assets/videos/slide-2-mobile.mp4', '', 'assets/images/hero/hero-2.jpg', 'Watch Our Films', 'films.php', 'Book Your Date', 'contact.php', 8, 45, 'center', 'published', 2, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (3, 'Signature Photography', 'Moments That Become Memories *Forever.*', 'Discover our signature wedding photography experience.', 'assets/videos/slide-3.mp4', 'assets/videos/slide-3.webm', 'assets/videos/slide-3-mobile.mp4', '', 'assets/images/hero/hero-3.jpg', 'View Portfolio', 'portfolio.php', 'Book Your Date', 'contact.php', 8, 50, 'left', 'published', 3, '2026-11-04 10:00:00', '2026-11-04 10:00:00');

INSERT INTO services (id, title, slug, tagline, icon, cover_image, description, details, features, price_note, gallery, is_featured, status, display_order, created_at, updated_at) VALUES
  (1, 'Wedding Photography', 'wedding-photography', 'Candid and traditional coverage of your big day', 'fa-solid fa-camera', 'assets/images/services/wedding-photography.jpg', 'Complete wedding-day coverage blending candid emotion with beautiful family portraits.', 'Complete wedding-day coverage blending candid emotion with beautiful family portraits.

Every package is tailored to your events, venue and guest count. Share your plans and we will recommend the ideal coverage.', '["Candid + traditional photographers","All rituals covered","Hand-edited high-resolution photos","Private online gallery"]', 'Packages from ₹75,000', '["assets/images/albums/aarav-isha/01.jpg","assets/images/albums/aarav-isha/02.jpg","assets/images/albums/aarav-isha/03.jpg","assets/images/albums/aarav-isha/04.jpg"]', 1, 'published', 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (2, 'Wedding Cinematography', 'wedding-cinematography', 'Cinematic coverage of every ritual', 'fa-solid fa-video', 'assets/images/services/wedding-cinematography.jpg', 'Multi-camera cinematic coverage with drone, gimbal and professional audio.', 'Multi-camera cinematic coverage with drone, gimbal and professional audio.

Every package is tailored to your events, venue and guest count. Share your plans and we will recommend the ideal coverage.', '["2–3 cinematographers","Drone coverage (where permitted)","Professional audio for vows","Colour graded footage"]', 'Packages from ₹90,000', '["assets/images/albums/rohan-meera/01.jpg","assets/images/albums/rohan-meera/02.jpg","assets/images/albums/rohan-meera/03.jpg","assets/images/albums/rohan-meera/04.jpg"]', 1, 'published', 2, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (3, 'Pre-Wedding Photography', 'pre-wedding', 'A story before the story', 'fa-solid fa-heart', 'assets/images/services/pre-wedding.jpg', 'A relaxed, styled shoot at a location that means something to you.', 'A relaxed, styled shoot at a location that means something to you.

Every package is tailored to your events, venue and guest count. Share your plans and we will recommend the ideal coverage.', '["Location scouting","Styling guidance","Photo + teaser film options","2–3 outfit changes"]', 'Packages from ₹35,000', '["assets/images/albums/kabir-ananya/01.jpg","assets/images/albums/kabir-ananya/02.jpg","assets/images/albums/kabir-ananya/03.jpg","assets/images/albums/kabir-ananya/04.jpg"]', 1, 'published', 3, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (4, 'Wedding Films', 'wedding-films', 'Your wedding as a feature film', 'fa-solid fa-film', 'assets/images/services/wedding-films.jpg', 'Story-driven wedding films with music, vows and the moments you missed.', 'Story-driven wedding films with music, vows and the moments you missed.

Every package is tailored to your events, venue and guest count. Share your plans and we will recommend the ideal coverage.', '["Feature film (20–40 min)","Highlights (3–5 min)","Licensed music","Social media teaser"]', 'Packages from ₹60,000', '["assets/images/albums/vivaan-diya/01.jpg","assets/images/albums/vivaan-diya/02.jpg","assets/images/albums/vivaan-diya/03.jpg","assets/images/albums/vivaan-diya/04.jpg"]', 1, 'published', 4, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (5, 'Engagement Photography', 'engagement', 'Ring ceremonies and roka', 'fa-solid fa-ring', 'assets/images/services/engagement.jpg', 'Elegant coverage of your engagement, ring ceremony or roka.', 'Elegant coverage of your engagement, ring ceremony or roka.

Every package is tailored to your events, venue and guest count. Share your plans and we will recommend the ideal coverage.', '["Ceremony coverage","Couple portraits","Family photos","Quick previews in 48 hours"]', 'Packages from ₹25,000', '["assets/images/albums/arjun-sneha/01.jpg","assets/images/albums/arjun-sneha/02.jpg","assets/images/albums/arjun-sneha/03.jpg","assets/images/albums/arjun-sneha/04.jpg"]', 1, 'published', 5, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (6, 'Haldi Photography', 'haldi', 'Colour, laughter and turmeric', 'fa-solid fa-sun', 'assets/images/services/haldi.jpg', 'Bright, joyful coverage of the haldi ceremony with plenty of candid fun.', 'Bright, joyful coverage of the haldi ceremony with plenty of candid fun.

Every package is tailored to your events, venue and guest count. Share your plans and we will recommend the ideal coverage.', '["Candid coverage","Detail shots","Family & friends moments"]', 'Included in wedding packages', '["assets/images/albums/dev-tara/01.jpg","assets/images/albums/dev-tara/02.jpg","assets/images/albums/dev-tara/03.jpg","assets/images/albums/dev-tara/04.jpg"]', 1, 'published', 6, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (7, 'Mehendi Photography', 'mehendi', 'Henna, music and celebration', 'fa-solid fa-hand-sparkles', 'assets/images/services/mehendi.jpg', 'Intricate henna details, dances and the energy of your mehendi night.', 'Intricate henna details, dances and the energy of your mehendi night.

Every package is tailored to your events, venue and guest count. Share your plans and we will recommend the ideal coverage.', '["Macro detail shots","Dance & sangeet coverage","Evening lighting"]', 'Included in wedding packages', '["assets/images/albums/aarav-isha/01.jpg","assets/images/albums/aarav-isha/02.jpg","assets/images/albums/aarav-isha/03.jpg","assets/images/albums/aarav-isha/04.jpg"]', 1, 'published', 7, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (8, 'Reception Photography', 'reception', 'Glamour, guests and celebration', 'fa-solid fa-champagne-glasses', 'assets/images/services/reception.jpg', 'Stylish couple portraits and candid guest moments at your reception.', 'Stylish couple portraits and candid guest moments at your reception.

Every package is tailored to your events, venue and guest count. Share your plans and we will recommend the ideal coverage.', '["Stage & guest photos","Couple portraits","Décor details"]', 'Packages from ₹30,000', '["assets/images/albums/rohan-meera/01.jpg","assets/images/albums/rohan-meera/02.jpg","assets/images/albums/rohan-meera/03.jpg","assets/images/albums/rohan-meera/04.jpg"]', 1, 'published', 8, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (9, 'Candid Photography', 'candid', 'Unposed, natural emotion', 'fa-solid fa-face-smile', 'assets/images/services/candid.jpg', 'Natural, unposed photographs of genuine emotions and in-between moments.', 'Natural, unposed photographs of genuine emotions and in-between moments.

Every package is tailored to your events, venue and guest count. Share your plans and we will recommend the ideal coverage.', '["Dedicated candid photographer","Storytelling edit"]', 'Add-on from ₹20,000', '["assets/images/albums/kabir-ananya/01.jpg","assets/images/albums/kabir-ananya/02.jpg","assets/images/albums/kabir-ananya/03.jpg","assets/images/albums/kabir-ananya/04.jpg"]', 0, 'published', 9, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (10, 'Traditional Photography', 'traditional', 'Every ritual and every relative', 'fa-solid fa-users', 'assets/images/services/traditional.jpg', 'Classic coverage of every ritual and family group for your albums.', 'Classic coverage of every ritual and family group for your albums.

Every package is tailored to your events, venue and guest count. Share your plans and we will recommend the ideal coverage.', '["Ritual coverage","Family group photos","Printed album options"]', 'Add-on from ₹15,000', '["assets/images/albums/vivaan-diya/01.jpg","assets/images/albums/vivaan-diya/02.jpg","assets/images/albums/vivaan-diya/03.jpg","assets/images/albums/vivaan-diya/04.jpg"]', 0, 'published', 10, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (11, 'Event Photography', 'event', 'Birthdays, anniversaries and corporate events', 'fa-solid fa-calendar-days', 'assets/images/services/event.jpg', 'Professional photography and films for birthdays, anniversaries and corporate events.', 'Professional photography and films for birthdays, anniversaries and corporate events.

Every package is tailored to your events, venue and guest count. Share your plans and we will recommend the ideal coverage.', '["Photo & video options","Quick delivery","Branded highlights"]', 'Packages from ₹15,000', '["assets/images/albums/arjun-sneha/01.jpg","assets/images/albums/arjun-sneha/02.jpg","assets/images/albums/arjun-sneha/03.jpg","assets/images/albums/arjun-sneha/04.jpg"]', 0, 'published', 11, '2026-11-04 10:00:00', '2026-11-04 10:00:00');

INSERT INTO portfolio_albums (id, title, couple_names, slug, category, event_date, location, cover_image, description, is_featured, status, display_order, created_at, updated_at) VALUES
  (1, 'A Royal Red Wedding', 'Aarav & Isha', 'aarav-isha', 'Wedding', '2025-12-08', 'Kolkata, West Bengal', 'assets/images/albums/aarav-isha/cover.jpg', 'A placeholder story for Aarav & Isha''s celebration. Replace this text with the real story of the day — the rituals, the venue and the moments that made it special.', 1, 'published', 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (2, 'Golden Hour Vows', 'Rohan & Meera', 'rohan-meera', 'Traditional Wedding', '2025-11-21', 'Udaipur, Rajasthan', 'assets/images/albums/rohan-meera/cover.jpg', 'A placeholder story for Rohan & Meera''s celebration. Replace this text with the real story of the day — the rituals, the venue and the moments that made it special.', 1, 'published', 2, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (3, 'Blush & Bloom Pre-Wedding', 'Kabir & Ananya', 'kabir-ananya', 'Pre-Wedding', '2025-10-12', 'Darjeeling, West Bengal', 'assets/images/albums/kabir-ananya/cover.jpg', 'A placeholder story for Kabir & Ananya''s celebration. Replace this text with the real story of the day — the rituals, the venue and the moments that made it special.', 1, 'published', 3, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (4, 'Sunshine Haldi', 'Vivaan & Diya', 'vivaan-diya', 'Haldi', '2025-02-14', 'Shantiniketan, West Bengal', 'assets/images/albums/vivaan-diya/cover.jpg', 'A placeholder story for Vivaan & Diya''s celebration. Replace this text with the real story of the day — the rituals, the venue and the moments that made it special.', 1, 'published', 4, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (5, 'Henna Nights', 'Arjun & Sneha', 'arjun-sneha', 'Mehendi', '2025-01-26', 'Jaipur, Rajasthan', 'assets/images/albums/arjun-sneha/cover.jpg', 'A placeholder story for Arjun & Sneha''s celebration. Replace this text with the real story of the day — the rituals, the venue and the moments that made it special.', 0, 'published', 5, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (6, 'Midnight Reception', 'Dev & Tara', 'dev-tara', 'Reception', '2024-12-15', 'Goa', 'assets/images/albums/dev-tara/cover.jpg', 'A placeholder story for Dev & Tara''s celebration. Replace this text with the real story of the day — the rituals, the venue and the moments that made it special.', 0, 'published', 6, '2026-11-04 10:00:00', '2026-11-04 10:00:00');

INSERT INTO films (id, title, slug, couple_names, category, video_url, thumbnail, description, location, event_date, duration_label, is_featured, status, display_order, created_at, updated_at) VALUES
  (1, 'A Wedding in Red & Gold', 'a-wedding-in-red-gold', 'Aarav & Isha', 'Wedding Film', 'https://www.youtube.com/watch?v=aqz-KE-bpKQ', 'assets/images/films/film-1.jpg', 'Sample video link — replace with your own wedding film.', 'Kolkata', '2025-12-08', '9:56', 1, 'published', 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (2, 'Golden Hour Highlights', 'golden-hour-highlights', 'Rohan & Meera', 'Highlights', 'https://vimeo.com/76979871', 'assets/images/films/film-2.jpg', 'Sample video link — replace with your own highlights film.', 'Udaipur', '2025-11-21', '3:12', 0, 'published', 2, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (3, 'Mountain Love — Pre-Wedding', 'mountain-love-pre-wedding', 'Kabir & Ananya', 'Pre-Wedding', 'https://www.youtube.com/watch?v=aqz-KE-bpKQ', 'assets/images/films/film-3.jpg', 'Sample video link — replace with your own pre-wedding film.', 'Darjeeling', '2025-10-12', '2:45', 0, 'published', 3, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (4, 'Haldi Teaser', 'haldi-teaser', 'Vivaan & Diya', 'Teaser', 'https://vimeo.com/76979871', 'assets/images/films/film-4.jpg', 'Sample video link — replace with your own teaser.', 'Shantiniketan', '2025-02-14', '1:05', 0, 'published', 4, '2026-11-04 10:00:00', '2026-11-04 10:00:00');

INSERT INTO reels (id, title, couple_names, category, video_path, video_url, poster_image, status, display_order, created_at, updated_at) VALUES
  (1, 'Mehendi Details', 'Vivaan & Diya', 'Reel', 'assets/videos/reels/reel-1.mp4', '', 'assets/images/reels/reel-1.webp', 'published', 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (2, 'The Bridal Entry', 'Aarav & Isha', 'Highlights', 'assets/videos/reels/reel-2.mp4', '', 'assets/images/reels/reel-2.webp', 'published', 2, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (3, 'Sindoor Daan', 'Rohan & Meera', 'Reel', 'assets/videos/reels/reel-3.mp4', '', 'assets/images/reels/reel-3.webp', 'published', 3, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (4, 'The Bride Arrives', 'Kabir & Ananya', 'Behind the scenes', 'assets/videos/reels/reel-4.mp4', '', 'assets/images/reels/reel-4.webp', 'published', 4, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (5, 'Vows Under Fairy Lights', 'Arjun & Sneha', 'Teaser', 'assets/videos/reels/reel-5.mp4', '', 'assets/images/reels/reel-5.webp', 'published', 5, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (6, 'Reception Night', 'Dev & Tara', 'Highlights', 'assets/videos/reels/reel-6.mp4', '', 'assets/images/reels/reel-6.webp', 'published', 6, '2026-11-04 10:00:00', '2026-11-04 10:00:00');

INSERT INTO testimonials (id, client_name, event_type, location, testimonial, client_image, rating, status, display_order, created_at, updated_at) VALUES
  (1, 'Couple Name', 'Wedding', 'City', 'Replace this with a real message from a couple you have photographed. Ask their permission before publishing their names.', '', 5, 'published', 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (2, 'Couple Name', 'Pre-Wedding', 'City', 'Testimonials are managed from Admin → Testimonials. You can hide any testimonial without deleting it.', '', 5, 'published', 2, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (3, 'Couple Name', 'Reception', 'City', 'Short, specific words about how the photos and films made a couple feel work best here.', '', 5, 'published', 3, '2026-11-04 10:00:00', '2026-11-04 10:00:00');

INSERT INTO team_members (id, name, designation, bio, profile_image, social_links, status, display_order, created_at, updated_at) VALUES
  (1, 'Founder Name', 'Founder & Lead Photographer', 'Leads every wedding with a calm, candid eye.', 'assets/images/team/member-1.jpg', '{"instagram":"","facebook":"","youtube":""}', 'published', 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (2, 'Team Member', 'Lead Cinematographer', 'Turns wedding days into cinematic films.', 'assets/images/team/member-2.jpg', '{"instagram":"","facebook":"","youtube":""}', 'published', 2, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (3, 'Team Member', 'Candid Photographer', 'Finds the unposed moments everyone remembers.', 'assets/images/team/member-3.jpg', '{"instagram":"","facebook":"","youtube":""}', 'published', 3, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (4, 'Team Member', 'Editor & Colourist', 'Crafts the look and feel of every album and film.', 'assets/images/team/member-4.jpg', '{"instagram":"","facebook":"","youtube":""}', 'published', 4, '2026-11-04 10:00:00', '2026-11-04 10:00:00');

INSERT INTO portfolio_images (album_id, file_path, thumb_path, width, height, caption, alt_text, display_order, created_at, updated_at) VALUES
  (1, 'assets/images/albums/aarav-isha/01.jpg', 'assets/images/albums/aarav-isha/thumbs/01.jpg', 1200, 800, '', 'Aarav & Isha — Wedding photo 1', 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (1, 'assets/images/albums/aarav-isha/02.jpg', 'assets/images/albums/aarav-isha/thumbs/02.jpg', 900, 1200, '', 'Aarav & Isha — Wedding photo 2', 2, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (1, 'assets/images/albums/aarav-isha/03.jpg', 'assets/images/albums/aarav-isha/thumbs/03.jpg', 1000, 1000, '', 'Aarav & Isha — Wedding photo 3', 3, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (1, 'assets/images/albums/aarav-isha/04.jpg', 'assets/images/albums/aarav-isha/thumbs/04.jpg', 1200, 800, '', 'Aarav & Isha — Wedding photo 4', 4, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (1, 'assets/images/albums/aarav-isha/05.jpg', 'assets/images/albums/aarav-isha/thumbs/05.jpg', 900, 1300, '', 'Aarav & Isha — Wedding photo 5', 5, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (1, 'assets/images/albums/aarav-isha/06.jpg', 'assets/images/albums/aarav-isha/thumbs/06.jpg', 1200, 900, '', 'Aarav & Isha — Wedding photo 6', 6, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (1, 'assets/images/albums/aarav-isha/07.jpg', 'assets/images/albums/aarav-isha/thumbs/07.jpg', 1000, 1250, '', 'Aarav & Isha — Wedding photo 7', 7, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (1, 'assets/images/albums/aarav-isha/08.jpg', 'assets/images/albums/aarav-isha/thumbs/08.jpg', 1200, 800, '', 'Aarav & Isha — Wedding photo 8', 8, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (2, 'assets/images/albums/rohan-meera/01.jpg', 'assets/images/albums/rohan-meera/thumbs/01.jpg', 1200, 800, '', 'Rohan & Meera — Traditional Wedding photo 1', 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (2, 'assets/images/albums/rohan-meera/02.jpg', 'assets/images/albums/rohan-meera/thumbs/02.jpg', 900, 1200, '', 'Rohan & Meera — Traditional Wedding photo 2', 2, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (2, 'assets/images/albums/rohan-meera/03.jpg', 'assets/images/albums/rohan-meera/thumbs/03.jpg', 1000, 1000, '', 'Rohan & Meera — Traditional Wedding photo 3', 3, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (2, 'assets/images/albums/rohan-meera/04.jpg', 'assets/images/albums/rohan-meera/thumbs/04.jpg', 1200, 800, '', 'Rohan & Meera — Traditional Wedding photo 4', 4, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (2, 'assets/images/albums/rohan-meera/05.jpg', 'assets/images/albums/rohan-meera/thumbs/05.jpg', 900, 1300, '', 'Rohan & Meera — Traditional Wedding photo 5', 5, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (2, 'assets/images/albums/rohan-meera/06.jpg', 'assets/images/albums/rohan-meera/thumbs/06.jpg', 1200, 900, '', 'Rohan & Meera — Traditional Wedding photo 6', 6, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (2, 'assets/images/albums/rohan-meera/07.jpg', 'assets/images/albums/rohan-meera/thumbs/07.jpg', 1000, 1250, '', 'Rohan & Meera — Traditional Wedding photo 7', 7, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (2, 'assets/images/albums/rohan-meera/08.jpg', 'assets/images/albums/rohan-meera/thumbs/08.jpg', 1200, 800, '', 'Rohan & Meera — Traditional Wedding photo 8', 8, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (3, 'assets/images/albums/kabir-ananya/01.jpg', 'assets/images/albums/kabir-ananya/thumbs/01.jpg', 1200, 800, '', 'Kabir & Ananya — Pre-Wedding photo 1', 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (3, 'assets/images/albums/kabir-ananya/02.jpg', 'assets/images/albums/kabir-ananya/thumbs/02.jpg', 900, 1200, '', 'Kabir & Ananya — Pre-Wedding photo 2', 2, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (3, 'assets/images/albums/kabir-ananya/03.jpg', 'assets/images/albums/kabir-ananya/thumbs/03.jpg', 1000, 1000, '', 'Kabir & Ananya — Pre-Wedding photo 3', 3, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (3, 'assets/images/albums/kabir-ananya/04.jpg', 'assets/images/albums/kabir-ananya/thumbs/04.jpg', 1200, 800, '', 'Kabir & Ananya — Pre-Wedding photo 4', 4, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (3, 'assets/images/albums/kabir-ananya/05.jpg', 'assets/images/albums/kabir-ananya/thumbs/05.jpg', 900, 1300, '', 'Kabir & Ananya — Pre-Wedding photo 5', 5, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (3, 'assets/images/albums/kabir-ananya/06.jpg', 'assets/images/albums/kabir-ananya/thumbs/06.jpg', 1200, 900, '', 'Kabir & Ananya — Pre-Wedding photo 6', 6, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (3, 'assets/images/albums/kabir-ananya/07.jpg', 'assets/images/albums/kabir-ananya/thumbs/07.jpg', 1000, 1250, '', 'Kabir & Ananya — Pre-Wedding photo 7', 7, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (3, 'assets/images/albums/kabir-ananya/08.jpg', 'assets/images/albums/kabir-ananya/thumbs/08.jpg', 1200, 800, '', 'Kabir & Ananya — Pre-Wedding photo 8', 8, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (4, 'assets/images/albums/vivaan-diya/01.jpg', 'assets/images/albums/vivaan-diya/thumbs/01.jpg', 1200, 800, '', 'Vivaan & Diya — Haldi photo 1', 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (4, 'assets/images/albums/vivaan-diya/02.jpg', 'assets/images/albums/vivaan-diya/thumbs/02.jpg', 900, 1200, '', 'Vivaan & Diya — Haldi photo 2', 2, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (4, 'assets/images/albums/vivaan-diya/03.jpg', 'assets/images/albums/vivaan-diya/thumbs/03.jpg', 1000, 1000, '', 'Vivaan & Diya — Haldi photo 3', 3, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (4, 'assets/images/albums/vivaan-diya/04.jpg', 'assets/images/albums/vivaan-diya/thumbs/04.jpg', 1200, 800, '', 'Vivaan & Diya — Haldi photo 4', 4, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (4, 'assets/images/albums/vivaan-diya/05.jpg', 'assets/images/albums/vivaan-diya/thumbs/05.jpg', 900, 1300, '', 'Vivaan & Diya — Haldi photo 5', 5, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (4, 'assets/images/albums/vivaan-diya/06.jpg', 'assets/images/albums/vivaan-diya/thumbs/06.jpg', 1200, 900, '', 'Vivaan & Diya — Haldi photo 6', 6, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (4, 'assets/images/albums/vivaan-diya/07.jpg', 'assets/images/albums/vivaan-diya/thumbs/07.jpg', 1000, 1250, '', 'Vivaan & Diya — Haldi photo 7', 7, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (4, 'assets/images/albums/vivaan-diya/08.jpg', 'assets/images/albums/vivaan-diya/thumbs/08.jpg', 1200, 800, '', 'Vivaan & Diya — Haldi photo 8', 8, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (5, 'assets/images/albums/arjun-sneha/01.jpg', 'assets/images/albums/arjun-sneha/thumbs/01.jpg', 1200, 800, '', 'Arjun & Sneha — Mehendi photo 1', 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (5, 'assets/images/albums/arjun-sneha/02.jpg', 'assets/images/albums/arjun-sneha/thumbs/02.jpg', 900, 1200, '', 'Arjun & Sneha — Mehendi photo 2', 2, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (5, 'assets/images/albums/arjun-sneha/03.jpg', 'assets/images/albums/arjun-sneha/thumbs/03.jpg', 1000, 1000, '', 'Arjun & Sneha — Mehendi photo 3', 3, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (5, 'assets/images/albums/arjun-sneha/04.jpg', 'assets/images/albums/arjun-sneha/thumbs/04.jpg', 1200, 800, '', 'Arjun & Sneha — Mehendi photo 4', 4, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (5, 'assets/images/albums/arjun-sneha/05.jpg', 'assets/images/albums/arjun-sneha/thumbs/05.jpg', 900, 1300, '', 'Arjun & Sneha — Mehendi photo 5', 5, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (5, 'assets/images/albums/arjun-sneha/06.jpg', 'assets/images/albums/arjun-sneha/thumbs/06.jpg', 1200, 900, '', 'Arjun & Sneha — Mehendi photo 6', 6, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (5, 'assets/images/albums/arjun-sneha/07.jpg', 'assets/images/albums/arjun-sneha/thumbs/07.jpg', 1000, 1250, '', 'Arjun & Sneha — Mehendi photo 7', 7, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (5, 'assets/images/albums/arjun-sneha/08.jpg', 'assets/images/albums/arjun-sneha/thumbs/08.jpg', 1200, 800, '', 'Arjun & Sneha — Mehendi photo 8', 8, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (6, 'assets/images/albums/dev-tara/01.jpg', 'assets/images/albums/dev-tara/thumbs/01.jpg', 1200, 800, '', 'Dev & Tara — Reception photo 1', 1, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (6, 'assets/images/albums/dev-tara/02.jpg', 'assets/images/albums/dev-tara/thumbs/02.jpg', 900, 1200, '', 'Dev & Tara — Reception photo 2', 2, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (6, 'assets/images/albums/dev-tara/03.jpg', 'assets/images/albums/dev-tara/thumbs/03.jpg', 1000, 1000, '', 'Dev & Tara — Reception photo 3', 3, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (6, 'assets/images/albums/dev-tara/04.jpg', 'assets/images/albums/dev-tara/thumbs/04.jpg', 1200, 800, '', 'Dev & Tara — Reception photo 4', 4, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (6, 'assets/images/albums/dev-tara/05.jpg', 'assets/images/albums/dev-tara/thumbs/05.jpg', 900, 1300, '', 'Dev & Tara — Reception photo 5', 5, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (6, 'assets/images/albums/dev-tara/06.jpg', 'assets/images/albums/dev-tara/thumbs/06.jpg', 1200, 900, '', 'Dev & Tara — Reception photo 6', 6, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (6, 'assets/images/albums/dev-tara/07.jpg', 'assets/images/albums/dev-tara/thumbs/07.jpg', 1000, 1250, '', 'Dev & Tara — Reception photo 7', 7, '2026-11-04 10:00:00', '2026-11-04 10:00:00'),
  (6, 'assets/images/albums/dev-tara/08.jpg', 'assets/images/albums/dev-tara/thumbs/08.jpg', 1200, 800, '', 'Dev & Tara — Reception photo 8', 8, '2026-11-04 10:00:00', '2026-11-04 10:00:00');

-- Done. Next: open admin/setup.php to create your administrator account.

-- Dreamm Weddings — add "Recent work" + "Made for your phone" (Reels) to an EXISTING database.
-- phpMyAdmin → select your database → SQL tab → paste this whole file → Go.
-- Safe to run more than once: it never deletes or overwrites your content.

-- 1) New table for reels (Admin → Reels)
CREATE TABLE IF NOT EXISTS reels (
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

-- 2) Six sample reels (only if the table is empty) — replace them with your own from Admin → Reels
INSERT INTO reels (title, couple_names, category, video_path, video_url, poster_image, display_order)
SELECT * FROM (
  SELECT 'Mehendi Details', 'Vivaan & Diya', 'Reel', 'assets/videos/reels/reel-1.mp4', '', 'assets/images/reels/reel-1.webp', 1
  UNION ALL
  SELECT 'The Bridal Entry', 'Aarav & Isha', 'Highlights', 'assets/videos/reels/reel-2.mp4', '', 'assets/images/reels/reel-2.webp', 2
  UNION ALL
  SELECT 'Sindoor Daan', 'Rohan & Meera', 'Reel', 'assets/videos/reels/reel-3.mp4', '', 'assets/images/reels/reel-3.webp', 3
  UNION ALL
  SELECT 'The Bride Arrives', 'Kabir & Ananya', 'Behind the scenes', 'assets/videos/reels/reel-4.mp4', '', 'assets/images/reels/reel-4.webp', 4
  UNION ALL
  SELECT 'Vows Under Fairy Lights', 'Arjun & Sneha', 'Teaser', 'assets/videos/reels/reel-5.mp4', '', 'assets/images/reels/reel-5.webp', 5
  UNION ALL
  SELECT 'Reception Night', 'Dev & Tara', 'Highlights', 'assets/videos/reels/reel-6.mp4', '', 'assets/images/reels/reel-6.webp', 6
) AS sample
WHERE NOT EXISTS (SELECT 1 FROM reels);

-- 3) Two new Home page sections, placed just before "Cinematic film showcase"
SET @home := (SELECT id FROM pages WHERE page_slug = 'home');
SET @has  := (SELECT COUNT(*) FROM page_sections WHERE page_id = @home AND section_key IN ('recent', 'reels'));
SET @pos  := (SELECT display_order FROM page_sections WHERE page_id = @home AND section_key = 'film');
SET @pos  := COALESCE(@pos, (SELECT COALESCE(MAX(display_order), 0) + 1 FROM page_sections WHERE page_id = @home));
UPDATE page_sections SET display_order = display_order + 2 WHERE page_id = @home AND display_order >= @pos AND @has = 0;
INSERT IGNORE INTO page_sections (page_id, section_key, section_title, section_subtitle, section_content, image, button_text, button_url, extra, display_order, is_visible, created_at, updated_at) VALUES
  (@home, 'recent', 'Recent *work*', 'Selected films', '', '', 'All Films', 'films.php', '{"description":"","limit":5}', @pos + 0, 1, NOW(), NOW()),
  (@home, 'reels', 'Made for *your phone*', 'Highlights & reels', '', '', '', '', '{"description":"Short vertical edits of the moments everyone asks for — tap any reel to watch with sound.","limit":10}', @pos + 1, 1, NOW(), NOW());

-- Dreamm Weddings — set the new transparent logo on your EXISTING database.
-- phpMyAdmin → select your database → SQL tab → paste → Go.

-- 1) Logo + favicon (required)
INSERT INTO settings (setting_key, setting_value) VALUES
  ('logo',         'assets/images/brand/dreamm-weddings-logo-horizontal.webp'),
  ('logo_stacked', 'assets/images/brand/dreamm-weddings-logo-web.webp'),
  ('favicon',      'assets/images/brand/favicon-192.png')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW();

-- 2) OPTIONAL: rename "Dreamm Framme Event" to "Dreamm Weddings" everywhere in the content.
--    Run only if you want the new name on every page.
UPDATE settings      SET setting_value   = REPLACE(setting_value,   'Dreamm Framme Event', 'Dreamm Weddings') WHERE setting_value   LIKE '%Dreamm Framme Event%';
UPDATE pages         SET meta_title      = REPLACE(meta_title,      'Dreamm Framme Event', 'Dreamm Weddings'),
                         meta_description = REPLACE(meta_description, 'Dreamm Framme Event', 'Dreamm Weddings');
UPDATE page_sections SET section_title   = REPLACE(section_title,   'Dreamm Framme Event', 'Dreamm Weddings'),
                         section_subtitle = REPLACE(section_subtitle, 'Dreamm Framme Event', 'Dreamm Weddings'),
                         section_content = REPLACE(section_content, 'Dreamm Framme Event', 'Dreamm Weddings'),
                         extra           = REPLACE(extra,           'Dreamm Framme Event', 'Dreamm Weddings');

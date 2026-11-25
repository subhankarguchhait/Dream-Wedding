<?php
/**
 * Built-in starter content.
 *  - database/dreamm_framme_event.sql seeds exactly this content.
 *  - Public pages fall back to it if a database record is missing (e.g. before the SQL is imported).
 * After installation, everything is edited in the admin panel — not here.
 * Photos and videos in assets/images and assets/videos are abstract placeholders; contact details,
 * testimonials, team names, statistics and sample albums are PLACEHOLDERS to replace before launch.
 */
declare(strict_types=1);
defined('APP_NAME') || exit; // no direct access

function j(array $a): string { return json_encode($a, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); }

function default_settings(): array
{
    return [
        'site_name' => 'Dreamm Weddings',
        'tagline' => 'Wedding Photography & Films',
        'logo' => 'assets/images/brand/dreamm-weddings-logo-horizontal.webp',
        'logo_stacked' => 'assets/images/brand/dreamm-weddings-logo-web.webp',
        'favicon' => 'assets/images/brand/favicon-192.png',
        'nav_cta_text' => 'Book Your Date',
        'nav_cta_link' => 'contact.php',
        'email' => 'hello@yourdomain.com',
        'phone' => '+91 00000 00000',
        'whatsapp' => '+91 00000 00000',
        'hours' => 'Mon – Sun · 10:00 AM – 8:00 PM (by appointment)',
        'address' => 'Kolkata, West Bengal, India',
        'social_instagram' => 'https://instagram.com/',
        'social_facebook' => 'https://facebook.com/',
        'social_youtube' => 'https://youtube.com/',
        'social_vimeo' => '',
        'social_pinterest' => '',
        'event_types' => j(['Wedding', 'Pre-Wedding', 'Engagement', 'Haldi', 'Mehendi', 'Reception', 'Birthday', 'Corporate Event', 'Other']),
        'service_options' => j(['Wedding Photography', 'Wedding Cinematography', 'Pre-Wedding', 'Wedding Films', 'Complete Wedding Package', 'Event Photography', 'Other']),
        'budget_options' => j(['Under ₹50,000', '₹50,000 – ₹1,00,000', '₹1,00,000 – ₹2,00,000', '₹2,00,000 – ₹4,00,000', 'Above ₹4,00,000', 'Not sure yet']),
        'portfolio_categories' => j(['Wedding', 'Pre-Wedding', 'Engagement', 'Haldi', 'Mehendi', 'Reception', 'Couple Portraits', 'Traditional Wedding', 'Candid Moments']),
        'max_event_days' => '7',
        'booking_note' => 'Popular wedding dates are booked 6–12 months in advance. Share your date and we will confirm availability within 24 hours.',
        'booking_success' => 'Thank you! We have received your enquiry and will confirm availability within 24 hours.',
        'footer_headline' => 'Let\'s tell *your* love story.',
        'footer_cta_text' => 'Check Your Date',
        'footer_cta_link' => 'contact.php',
        'footer_about' => 'Dreamm Weddings is a wedding photography and cinematography studio capturing emotions, rituals and timeless moments with a cinematic eye.',
        'footer_copyright' => '© {year} Dreamm Weddings. All rights reserved.',
        'seo_title' => 'Dreamm Weddings',
        'seo_description' => 'Luxury wedding photography, cinematic wedding films, pre-wedding shoots and event photography by Dreamm Weddings.',
        'seo_keywords' => 'wedding photographer, wedding cinematography, pre-wedding shoot, candid wedding photography, wedding films, Kolkata',
        'og_image' => '',
    ];
}

function default_pages(): array
{
    $I = 'assets/images/';
    $cta = fn(string $t, string $c, array $x = []) => array_merge([
        'eyebrow' => 'Now booking', 'title' => $t, 'content' => $c, 'button_text' => 'Check Your Date', 'button_url' => 'contact.php',
        'cta2_text' => 'Chat on WhatsApp', 'cta2_url' => '#whatsapp', 'image' => $I . 'sections/cta.jpg',
    ], $x);
    $why = [
        ['icon' => 'fa-solid fa-feather-pointed', 'title' => 'Creative storytelling', 'text' => 'Every frame is planned around your story, rituals and personalities.'],
        ['icon' => 'fa-solid fa-camera', 'title' => 'Professional equipment', 'text' => 'Full-frame cameras, prime lenses, gimbals, drones and cinema lighting.'],
        ['icon' => 'fa-solid fa-user-group', 'title' => 'Experienced team', 'text' => 'Photographers and filmmakers who know Indian weddings inside out.'],
        ['icon' => 'fa-solid fa-film', 'title' => 'Cinematic editing', 'text' => 'Colour grading, sound design and storytelling edits that move people.'],
        ['icon' => 'fa-solid fa-magnifying-glass', 'title' => 'Attention to detail', 'text' => 'From the mehendi patterns to the last pheras, nothing is missed.'],
        ['icon' => 'fa-solid fa-heart', 'title' => 'Personalised experience', 'text' => 'A dedicated coordinator and a plan made just for your celebration.'],
    ];
    $legal = fn(string $title, string $intro) => [
        'hero' => ['eyebrow' => 'Legal', 'title' => $title, 'content' => $intro, 'image' => $I . 'pages/legal.jpg'],
        'body' => ['title' => $title, 'description' => 'Last updated: ' . date('F Y'), 'content' => "## Placeholder text\n\nThis page is a starting template. Replace it with your own wording, reviewed by a qualified professional, before publishing your website.\n\n## Information we collect\n\nWhen you send a booking enquiry we receive the details you enter in the form, such as your name, email, phone number, event date and message. We use them only to reply to your enquiry and to plan your event.\n\n## Your choices\n\nYou can ask us to update or delete your details at any time by contacting us using the details on our Contact page."],
    ];
    return [
        'home' => [
            'meta' => ['name' => 'Home', 'title' => 'Wedding Photography & Cinematic Films', 'description' => ''],
            'sections' => [
                'hero' => ['scroll_text' => 'Scroll'],
                'intro' => [
                    'eyebrow' => 'Welcome to Dreamm Weddings', 'title' => 'We don\'t just take photos — we keep *feelings*.',
                    'content' => "Dreamm Weddings is a wedding photography and cinematography studio for couples who want their story told with honesty and elegance.\n\nWe blend candid moments, timeless portraits and cinematic films so that years from now you can feel your wedding day all over again.",
                    'signature' => 'Team Dreamm Weddings', 'image' => $I . 'sections/intro.jpg', 'image2' => $I . 'albums/rohan-meera/03.jpg',
                    'button_text' => 'Our Story', 'button_url' => 'about.php',
                ],
                'stories' => ['eyebrow' => 'Featured wedding stories', 'title' => 'Love stories we\'ve *framed*', 'description' => 'A few of the celebrations we have been honoured to capture.', 'limit' => 4, 'button_text' => 'Explore All Stories', 'button_url' => 'portfolio.php'],
                'services' => ['eyebrow' => 'What we do', 'title' => 'Our *services*', 'description' => 'Photography and films for every ritual, from the first look to the last dance.', 'limit' => 8, 'button_text' => 'View All Services', 'button_url' => 'services.php'],
                'recent' => ['eyebrow' => 'Selected films', 'title' => 'Recent *work*', 'description' => '', 'limit' => 5, 'button_text' => 'All Films', 'button_url' => 'films.php'],
                'reels' => ['eyebrow' => 'Highlights & reels', 'title' => 'Made for *your phone*', 'description' => 'Short vertical edits of the moments everyone asks for — tap any reel to watch with sound.', 'limit' => 10, 'button_text' => '', 'button_url' => ''],
                'film' => ['eyebrow' => 'Cinematic films', 'title' => 'Your wedding, as a *film*', 'description' => 'Feel the emotion of the day again with a cinematic wedding film.', 'button_text' => 'Watch More Films', 'button_url' => 'films.php', 'image' => $I . 'sections/film-bg.jpg'],
                'why' => ['eyebrow' => 'Why choose us', 'title' => 'Crafted with *heart*', 'description' => 'Six promises we make to every couple.', 'items' => $why],
                'stats' => ['items' => [
                    ['value' => '250', 'suffix' => '+', 'label' => 'Weddings captured'],
                    ['value' => '180', 'suffix' => '+', 'label' => 'Films created'],
                    ['value' => '500', 'suffix' => '+', 'label' => 'Happy couples'],
                    ['value' => '8', 'suffix' => '', 'label' => 'Years of experience'],
                ]],
                'testimonials' => ['eyebrow' => 'Kind words', 'title' => 'Love *notes*', 'description' => ''],
                'social' => [
                    'eyebrow' => 'Follow our journey', 'title' => 'On *Instagram*', 'description' => 'Daily moments from weddings, rituals and behind the scenes.',
                    'handle' => '@dreammframmeevent',
                    'images' => [$I . 'albums/aarav-isha/02.jpg', $I . 'albums/kabir-ananya/03.jpg', $I . 'albums/vivaan-diya/01.jpg', $I . 'albums/arjun-sneha/05.jpg', $I . 'albums/rohan-meera/06.jpg', $I . 'albums/dev-tara/02.jpg'],
                ],
                'cta' => $cta('Let\'s capture your *beautiful beginning.*', 'Tell us about your celebration and we will confirm availability for your date within 24 hours.'),
            ],
        ],
        'about' => [
            'meta' => ['name' => 'About Us', 'title' => 'About Us', 'description' => 'The story, philosophy and team behind Dreamm Weddings.'],
            'sections' => [
                'hero' => ['eyebrow' => 'About us', 'title' => 'Storytellers of *love*', 'content' => 'A small team of photographers and filmmakers devoted to weddings.', 'image' => $I . 'pages/about.jpg'],
                'story' => ['eyebrow' => 'Our story', 'title' => 'It began with one *frame*', 'content' => "Dreamm Weddings started with a single camera and a belief that wedding photographs should feel, not just look, beautiful.\n\nToday our team captures weddings across India, but every couple still gets the same care as our very first wedding.", 'image' => $I . 'sections/story.jpg'],
                'who' => ['eyebrow' => 'Who we are', 'title' => 'Photographers, filmmakers, *friends*', 'content' => 'We are a close-knit team of candid photographers, traditional photographers, cinematographers and editors who work together on every wedding.', 'points' => ['Candid & traditional photographers', 'Cinematographers & drone operators', 'Editors & colourists', 'A dedicated wedding coordinator'], 'image' => $I . 'albums/kabir-ananya/02.jpg'],
                'philosophy' => ['eyebrow' => 'Our philosophy', 'title' => 'Real moments, *beautifully* told', 'content' => 'We believe the most beautiful photographs happen when people forget the camera. Our job is to be present, invisible and ready.', 'signature' => 'Dreamm Weddings', 'image' => $I . 'sections/philosophy.jpg'],
                'mission_vision' => ['eyebrow' => 'Purpose', 'title' => 'Mission & *Vision*', 'description' => '', 'mission_title' => 'Our Mission', 'mission' => 'To preserve the emotions of every wedding with honest photography and cinematic films that families treasure for generations.', 'vision_title' => 'Our Vision', 'vision' => 'To be the most loved wedding storytelling studio in India, known for warmth, artistry and trust.', 'image' => $I . 'sections/mission.jpg'],
                'approach' => ['eyebrow' => 'Creative approach', 'title' => 'How we *work*', 'description' => '', 'steps' => [
                    ['title' => 'Meet & listen', 'text' => 'We learn your story, traditions and the moments that matter most to you.'],
                    ['title' => 'Plan the story', 'text' => 'A shot list, timeline and team plan built around your events.'],
                    ['title' => 'Capture', 'text' => 'Candid, traditional and cinematic coverage — calm, unobtrusive and on time.'],
                    ['title' => 'Craft & deliver', 'text' => 'Hand-edited photos, albums and films delivered in a private online gallery.'],
                ]],
                'team' => ['eyebrow' => 'Our team', 'title' => 'The people behind the *lens*', 'description' => ''],
                'equipment' => ['eyebrow' => 'Our equipment', 'title' => 'Tools of the *craft*', 'description' => 'Professional gear chosen for low light, long days and cinematic results.', 'items' => [
                    ['icon' => 'fa-solid fa-camera', 'title' => 'Full-frame cameras', 'text' => 'Mirrorless bodies with dual card slots for safe backups.'],
                    ['icon' => 'fa-solid fa-circle-dot', 'title' => 'Prime & zoom lenses', 'text' => 'Fast primes for portraits and zooms for rituals.'],
                    ['icon' => 'fa-solid fa-video', 'title' => 'Cinema cameras & gimbals', 'text' => 'Smooth, stabilised movement for films.'],
                    ['icon' => 'fa-solid fa-helicopter', 'title' => 'Drones', 'text' => 'Aerial shots of venues and processions (where permitted).'],
                    ['icon' => 'fa-solid fa-lightbulb', 'title' => 'Lighting', 'text' => 'Soft, flattering light for evening ceremonies.'],
                    ['icon' => 'fa-solid fa-microphone', 'title' => 'Audio', 'text' => 'Clear vows and speeches for your film.'],
                ]],
                'why' => ['eyebrow' => 'Why choose us', 'title' => 'Why couples choose *us*', 'description' => '', 'items' => array_slice($why, 0, 4)],
                'cta' => $cta('Let\'s write your story *together.*', 'Share your plans and we will craft a coverage plan for your celebration.'),
            ],
        ],
        'services' => [
            'meta' => ['name' => 'Services', 'title' => 'Wedding Photography & Film Services', 'description' => 'Wedding photography, cinematography, pre-wedding, haldi, mehendi, reception and event coverage.'],
            'sections' => [
                'hero' => ['eyebrow' => 'Services', 'title' => 'Every ritual, *beautifully* covered', 'content' => 'Photography and films for weddings, pre-weddings and celebrations.', 'image' => $I . 'pages/services.jpg'],
                'list' => ['eyebrow' => 'What we offer', 'title' => 'Our *services*', 'description' => 'Choose individual services or combine them into a complete wedding package.'],
                'process' => ['eyebrow' => 'Booking process', 'title' => 'Simple, *stress-free* booking', 'description' => '', 'steps' => [
                    ['title' => 'Enquire', 'text' => 'Send your date, venue and the events you would like covered.'],
                    ['title' => 'Consult', 'text' => 'We plan coverage together on a call or at our studio.'],
                    ['title' => 'Confirm', 'text' => 'Reserve your date with a signed agreement and advance.'],
                    ['title' => 'Celebrate', 'text' => 'Enjoy your day — we take care of the memories.'],
                ]],
                'cta' => $cta('Not sure what you *need?*', 'Tell us about your events and we will recommend the right coverage.'),
            ],
        ],
        'portfolio' => [
            'meta' => ['name' => 'Portfolio', 'title' => 'Wedding Portfolio', 'description' => 'Wedding, pre-wedding, haldi, mehendi and reception photo albums by Dreamm Weddings.'],
            'sections' => [
                'hero' => ['eyebrow' => 'Portfolio', 'title' => 'Stories in *frames*', 'content' => 'Browse complete wedding albums and our favourite moments.', 'image' => $I . 'pages/portfolio.jpg'],
                'albums' => ['eyebrow' => 'Wedding albums', 'title' => 'Real weddings, real *emotions*', 'description' => 'Choose a category or open an album to see the full story.'],
                'moments' => ['eyebrow' => 'Moments', 'title' => 'Favourite *frames*', 'description' => 'Tap any photo to view it full screen.', 'limit' => 24],
                'cta' => $cta('Imagine *your* album here.', 'Let us capture your celebration with the same care.'),
            ],
        ],
        'films' => [
            'meta' => ['name' => 'Films', 'title' => 'Cinematic Wedding Films', 'description' => 'Cinematic wedding films, highlights and pre-wedding films by Dreamm Weddings.'],
            'sections' => [
                'hero' => ['eyebrow' => 'Cinematic films', 'title' => 'Love, in *motion*', 'content' => 'Wedding films that let you hear the laughter and feel the tears again.', 'image' => $I . 'pages/films.jpg'],
                'featured' => ['eyebrow' => 'Featured film', 'title' => 'This week\'s *favourite*', 'description' => ''],
                'grid' => ['eyebrow' => 'Film gallery', 'title' => 'Wedding films & *highlights*', 'description' => 'Press play on any film.'],
                'cta' => $cta('Want a film like *this?*', 'Ask us about wedding films, highlights and teasers.', ['button_text' => 'Book a Wedding Film']),
            ],
        ],
        'contact' => [
            'meta' => ['name' => 'Contact', 'title' => 'Contact & Booking Enquiry', 'description' => 'Check availability and book Dreamm Weddings for your wedding or event.'],
            'sections' => [
                'hero' => ['eyebrow' => 'Contact', 'title' => 'Let\'s plan your *story*', 'content' => 'Share a few details and we will confirm availability for your date.', 'image' => $I . 'pages/contact.jpg'],
                'form' => ['eyebrow' => 'Get in touch', 'title' => 'We\'d love to *hear* from you', 'description' => 'Call, WhatsApp or visit our studio by appointment.', 'form_title' => 'Booking *enquiry*', 'content' => 'Fields marked * are required.'],
                'map' => ['embed_url' => 'https://www.google.com/maps?q=Kolkata%2C%20West%20Bengal&output=embed'],
                'cta' => $cta('Prefer to *talk?*', 'Message us on WhatsApp and we usually reply within the hour.', ['button_text' => 'Chat on WhatsApp', 'button_url' => '#whatsapp', 'cta2_text' => '', 'cta2_url' => '']),
            ],
        ],
        'privacy' => ['meta' => ['name' => 'Privacy Policy', 'title' => 'Privacy Policy', 'description' => 'How Dreamm Weddings handles your information.'], 'sections' => $legal('Privacy *Policy*', 'How we handle the information you share with us.')],
        'terms' => ['meta' => ['name' => 'Terms', 'title' => 'Terms & Conditions', 'description' => 'Terms for booking Dreamm Weddings.'], 'sections' => $legal('Terms & *Conditions*', 'The terms that apply to bookings and use of this website.')],
    ];
}

function default_collections(): array
{
    $I = 'assets/images/';
    $V = 'assets/videos/';
    // Sample service galleries reuse the placeholder album photos (replace with real work in Admin → Services)
    $gal = function (string $slug): array {
        static $n = 0;
        $sets = ['aarav-isha', 'rohan-meera', 'kabir-ananya', 'vivaan-diya', 'arjun-sneha', 'dev-tara'];
        $album = $sets[$n++ % count($sets)];
        return array_map(fn($i) => "assets/images/albums/$album/0$i.jpg", [1, 2, 3, 4]);
    };
    $svc = fn(string $t, string $slug, string $icon, string $tag, string $d, array $f, string $price, bool $home) => [
        'title' => $t, 'slug' => $slug, 'tagline' => $tag, 'icon' => $icon, 'cover_image' => $I . "services/$slug.jpg",
        'description' => $d, 'details' => "$d\n\nEvery package is tailored to your events, venue and guest count. Share your plans and we will recommend the ideal coverage.",
        'features' => $f, 'price_note' => $price, 'gallery' => $gal($slug), 'is_featured' => $home ? 1 : 0, 'status' => 'published',
    ];
    $albums = [
        ['A Royal Red Wedding', 'Aarav & Isha', 'aarav-isha', 'Wedding', '2025-12-08', 'Kolkata, West Bengal', 1],
        ['Golden Hour Vows', 'Rohan & Meera', 'rohan-meera', 'Traditional Wedding', '2025-11-21', 'Udaipur, Rajasthan', 1],
        ['Blush & Bloom Pre-Wedding', 'Kabir & Ananya', 'kabir-ananya', 'Pre-Wedding', '2025-10-12', 'Darjeeling, West Bengal', 1],
        ['Sunshine Haldi', 'Vivaan & Diya', 'vivaan-diya', 'Haldi', '2025-02-14', 'Shantiniketan, West Bengal', 1],
        ['Henna Nights', 'Arjun & Sneha', 'arjun-sneha', 'Mehendi', '2025-01-26', 'Jaipur, Rajasthan', 0],
        ['Midnight Reception', 'Dev & Tara', 'dev-tara', 'Reception', '2024-12-15', 'Goa', 0],
    ];
    return [
        'slides' => [
            ['eyebrow' => 'Dreamm Weddings', 'heading' => 'Every Love Story Deserves to Be *Remembered.*', 'subtitle' => 'Capturing timeless moments through cinematic wedding photography.', 'video_path' => $V . 'slide-1.mp4', 'video_webm_path' => $V . 'slide-1.webm', 'mobile_video_path' => $V . 'slide-1-mobile.mp4', 'video_url' => '', 'poster_image' => $I . 'hero/hero-1.jpg', 'primary_button_text' => 'Explore Our Stories', 'primary_button_url' => 'portfolio.php', 'secondary_button_text' => 'Book Your Date', 'secondary_button_url' => 'contact.php', 'duration' => 8, 'overlay_opacity' => 45, 'text_alignment' => 'left', 'status' => 'published'],
            ['eyebrow' => 'Wedding Films', 'heading' => 'Your Beautiful Beginning, Our *Cinematic* Story.', 'subtitle' => 'Wedding films created with emotion, artistry and elegance.', 'video_path' => $V . 'slide-2.mp4', 'video_webm_path' => $V . 'slide-2.webm', 'mobile_video_path' => $V . 'slide-2-mobile.mp4', 'video_url' => '', 'poster_image' => $I . 'hero/hero-2.jpg', 'primary_button_text' => 'Watch Our Films', 'primary_button_url' => 'films.php', 'secondary_button_text' => 'Book Your Date', 'secondary_button_url' => 'contact.php', 'duration' => 8, 'overlay_opacity' => 45, 'text_alignment' => 'center', 'status' => 'published'],
            ['eyebrow' => 'Signature Photography', 'heading' => 'Moments That Become Memories *Forever.*', 'subtitle' => 'Discover our signature wedding photography experience.', 'video_path' => $V . 'slide-3.mp4', 'video_webm_path' => $V . 'slide-3.webm', 'mobile_video_path' => $V . 'slide-3-mobile.mp4', 'video_url' => '', 'poster_image' => $I . 'hero/hero-3.jpg', 'primary_button_text' => 'View Portfolio', 'primary_button_url' => 'portfolio.php', 'secondary_button_text' => 'Book Your Date', 'secondary_button_url' => 'contact.php', 'duration' => 8, 'overlay_opacity' => 50, 'text_alignment' => 'left', 'status' => 'published'],
        ],
        'services' => [
            $svc('Wedding Photography', 'wedding-photography', 'fa-solid fa-camera', 'Candid and traditional coverage of your big day', 'Complete wedding-day coverage blending candid emotion with beautiful family portraits.', ['Candid + traditional photographers', 'All rituals covered', 'Hand-edited high-resolution photos', 'Private online gallery'], 'Packages from ₹75,000', true),
            $svc('Wedding Cinematography', 'wedding-cinematography', 'fa-solid fa-video', 'Cinematic coverage of every ritual', 'Multi-camera cinematic coverage with drone, gimbal and professional audio.', ['2–3 cinematographers', 'Drone coverage (where permitted)', 'Professional audio for vows', 'Colour graded footage'], 'Packages from ₹90,000', true),
            $svc('Pre-Wedding Photography', 'pre-wedding', 'fa-solid fa-heart', 'A story before the story', 'A relaxed, styled shoot at a location that means something to you.', ['Location scouting', 'Styling guidance', 'Photo + teaser film options', '2–3 outfit changes'], 'Packages from ₹35,000', true),
            $svc('Wedding Films', 'wedding-films', 'fa-solid fa-film', 'Your wedding as a feature film', 'Story-driven wedding films with music, vows and the moments you missed.', ['Feature film (20–40 min)', 'Highlights (3–5 min)', 'Licensed music', 'Social media teaser'], 'Packages from ₹60,000', true),
            $svc('Engagement Photography', 'engagement', 'fa-solid fa-ring', 'Ring ceremonies and roka', 'Elegant coverage of your engagement, ring ceremony or roka.', ['Ceremony coverage', 'Couple portraits', 'Family photos', 'Quick previews in 48 hours'], 'Packages from ₹25,000', true),
            $svc('Haldi Photography', 'haldi', 'fa-solid fa-sun', 'Colour, laughter and turmeric', 'Bright, joyful coverage of the haldi ceremony with plenty of candid fun.', ['Candid coverage', 'Detail shots', 'Family & friends moments'], 'Included in wedding packages', true),
            $svc('Mehendi Photography', 'mehendi', 'fa-solid fa-hand-sparkles', 'Henna, music and celebration', 'Intricate henna details, dances and the energy of your mehendi night.', ['Macro detail shots', 'Dance & sangeet coverage', 'Evening lighting'], 'Included in wedding packages', true),
            $svc('Reception Photography', 'reception', 'fa-solid fa-champagne-glasses', 'Glamour, guests and celebration', 'Stylish couple portraits and candid guest moments at your reception.', ['Stage & guest photos', 'Couple portraits', 'Décor details'], 'Packages from ₹30,000', true),
            $svc('Candid Photography', 'candid', 'fa-solid fa-face-smile', 'Unposed, natural emotion', 'Natural, unposed photographs of genuine emotions and in-between moments.', ['Dedicated candid photographer', 'Storytelling edit'], 'Add-on from ₹20,000', false),
            $svc('Traditional Photography', 'traditional', 'fa-solid fa-users', 'Every ritual and every relative', 'Classic coverage of every ritual and family group for your albums.', ['Ritual coverage', 'Family group photos', 'Printed album options'], 'Add-on from ₹15,000', false),
            $svc('Event Photography', 'event', 'fa-solid fa-calendar-days', 'Birthdays, anniversaries and corporate events', 'Professional photography and films for birthdays, anniversaries and corporate events.', ['Photo & video options', 'Quick delivery', 'Branded highlights'], 'Packages from ₹15,000', false),
        ],
        'albums' => array_map(fn($a) => [
            'title' => $a[0], 'couple_names' => $a[1], 'slug' => $a[2], 'category' => $a[3], 'event_date' => $a[4], 'location' => $a[5],
            'cover_image' => $I . "albums/{$a[2]}/cover.jpg", 'description' => "A placeholder story for {$a[1]}'s celebration. Replace this text with the real story of the day — the rituals, the venue and the moments that made it special.",
            'is_featured' => $a[6], 'status' => 'published',
        ], $albums),
        'films' => [
            ['title' => 'A Wedding in Red & Gold', 'slug' => 'a-wedding-in-red-gold', 'couple_names' => 'Aarav & Isha', 'category' => 'Wedding Film', 'video_path' => '', 'video_url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ', 'thumbnail' => $I . 'films/film-1.jpg', 'description' => 'Sample video link — replace with your own wedding film.', 'location' => 'Kolkata', 'event_date' => '2025-12-08', 'duration_label' => '9:56', 'is_featured' => 1, 'status' => 'published'],
            ['title' => 'Wedding Day Highlights', 'slug' => 'wedding-day-highlights', 'couple_names' => 'Sample couple', 'category' => 'Highlights', 'video_path' => $V . 'films/wedding-highlights.mp4', 'video_url' => '', 'thumbnail' => $I . 'films/wedding-highlights.webp', 'description' => 'Sample uploaded MP4 — replace it with your own film in Admin → Films.', 'location' => 'Kolkata', 'event_date' => '2026-01-18', 'duration_label' => '', 'is_featured' => 0, 'status' => 'published'],
            ['title' => 'Golden Hour Highlights', 'slug' => 'golden-hour-highlights', 'couple_names' => 'Rohan & Meera', 'category' => 'Highlights', 'video_path' => '', 'video_url' => 'https://vimeo.com/76979871', 'thumbnail' => $I . 'films/film-2.jpg', 'description' => 'Sample video link — replace with your own highlights film.', 'location' => 'Udaipur', 'event_date' => '2025-11-21', 'duration_label' => '3:12', 'is_featured' => 0, 'status' => 'published'],
            ['title' => 'Mountain Love — Pre-Wedding', 'slug' => 'mountain-love-pre-wedding', 'couple_names' => 'Kabir & Ananya', 'category' => 'Pre-Wedding', 'video_path' => '', 'video_url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ', 'thumbnail' => $I . 'films/film-3.jpg', 'description' => 'Sample video link — replace with your own pre-wedding film.', 'location' => 'Darjeeling', 'event_date' => '2025-10-12', 'duration_label' => '2:45', 'is_featured' => 0, 'status' => 'published'],
            ['title' => 'Haldi Teaser', 'slug' => 'haldi-teaser', 'couple_names' => 'Vivaan & Diya', 'category' => 'Teaser', 'video_path' => '', 'video_url' => 'https://vimeo.com/76979871', 'thumbnail' => $I . 'films/film-4.jpg', 'description' => 'Sample video link — replace with your own teaser.', 'location' => 'Shantiniketan', 'event_date' => '2025-02-14', 'duration_label' => '1:05', 'is_featured' => 0, 'status' => 'published'],
        ],
        'reels' => [
            ['title' => 'Mehendi Details', 'couple_names' => 'Vivaan & Diya', 'category' => 'Reel', 'video_path' => $V . 'reels/reel-1.mp4', 'video_url' => '', 'poster_image' => $I . 'reels/reel-1.webp', 'status' => 'published'],
            ['title' => 'The Bridal Entry', 'couple_names' => 'Aarav & Isha', 'category' => 'Highlights', 'video_path' => $V . 'reels/reel-2.mp4', 'video_url' => '', 'poster_image' => $I . 'reels/reel-2.webp', 'status' => 'published'],
            ['title' => 'Sindoor Daan', 'couple_names' => 'Rohan & Meera', 'category' => 'Reel', 'video_path' => $V . 'reels/reel-3.mp4', 'video_url' => '', 'poster_image' => $I . 'reels/reel-3.webp', 'status' => 'published'],
            ['title' => 'The Bride Arrives', 'couple_names' => 'Kabir & Ananya', 'category' => 'Behind the scenes', 'video_path' => $V . 'reels/reel-4.mp4', 'video_url' => '', 'poster_image' => $I . 'reels/reel-4.webp', 'status' => 'published'],
            ['title' => 'Vows Under Fairy Lights', 'couple_names' => 'Arjun & Sneha', 'category' => 'Teaser', 'video_path' => $V . 'reels/reel-5.mp4', 'video_url' => '', 'poster_image' => $I . 'reels/reel-5.webp', 'status' => 'published'],
            ['title' => 'Reception Night', 'couple_names' => 'Dev & Tara', 'category' => 'Highlights', 'video_path' => $V . 'reels/reel-6.mp4', 'video_url' => '', 'poster_image' => $I . 'reels/reel-6.webp', 'status' => 'published'],
        ],
        'testimonials' => [
            ['client_name' => 'Couple Name', 'event_type' => 'Wedding', 'location' => 'City', 'testimonial' => 'Replace this with a real message from a couple you have photographed. Ask their permission before publishing their names.', 'client_image' => '', 'rating' => 5, 'status' => 'published'],
            ['client_name' => 'Couple Name', 'event_type' => 'Pre-Wedding', 'location' => 'City', 'testimonial' => 'Testimonials are managed from Admin → Testimonials. You can hide any testimonial without deleting it.', 'client_image' => '', 'rating' => 5, 'status' => 'published'],
            ['client_name' => 'Couple Name', 'event_type' => 'Reception', 'location' => 'City', 'testimonial' => 'Short, specific words about how the photos and films made a couple feel work best here.', 'client_image' => '', 'rating' => 5, 'status' => 'published'],
        ],
        'team' => [
            ['name' => 'Founder Name', 'designation' => 'Founder & Lead Photographer', 'bio' => 'Leads every wedding with a calm, candid eye.', 'profile_image' => $I . 'team/member-1.jpg', 'social_links' => ['instagram' => '', 'facebook' => '', 'youtube' => ''], 'status' => 'published'],
            ['name' => 'Team Member', 'designation' => 'Lead Cinematographer', 'bio' => 'Turns wedding days into cinematic films.', 'profile_image' => $I . 'team/member-2.jpg', 'social_links' => ['instagram' => '', 'facebook' => '', 'youtube' => ''], 'status' => 'published'],
            ['name' => 'Team Member', 'designation' => 'Candid Photographer', 'bio' => 'Finds the unposed moments everyone remembers.', 'profile_image' => $I . 'team/member-3.jpg', 'social_links' => ['instagram' => '', 'facebook' => '', 'youtube' => ''], 'status' => 'published'],
            ['name' => 'Team Member', 'designation' => 'Editor & Colourist', 'bio' => 'Crafts the look and feel of every album and film.', 'profile_image' => $I . 'team/member-4.jpg', 'social_links' => ['instagram' => '', 'facebook' => '', 'youtube' => ''], 'status' => 'published'],
        ],
    ];
}

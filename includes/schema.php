<?php
/**
 * Content schemas — every editable field of the CMS is described here once.
 * Admin forms, the server-side validator and the public templates all read these definitions.
 *
 * Field keys:  k · label · t · required · help · placeholder · max · min · maxlen · rows · options · fields · singular · item_label · folder · full · default
 * Types:       text textarea email url link video_url number date toggle status select tags icon image image_list video repeater group
 *
 * Page-section fields named title / eyebrow / content / image / button_text / button_url are stored in
 * dedicated page_sections columns; every other field goes into the section's JSON "extra" column.
 */
declare(strict_types=1);
defined('APP_NAME') || exit; // no direct access

const RICH_HELP = 'Wrap a word in *asterisks* to show it in gold italics.';

/* ------------------------------------------------------------- field kits */

function f_heading(bool $description = true): array
{
    $f = [
        ['k' => 'eyebrow', 'label' => 'Small label above the title', 'placeholder' => 'e.g. Our Services', 'maxlen' => 80],
        ['k' => 'title', 'label' => 'Section title', 'required' => true, 'help' => RICH_HELP, 'full' => true, 'maxlen' => 160],
    ];
    if ($description) $f[] = ['k' => 'description', 'label' => 'Short description', 't' => 'textarea', 'rows' => 2, 'maxlen' => 320];
    return $f;
}

function f_button(string $label = 'Button'): array
{
    return [['k' => 'button_text', 'label' => "$label text", 'maxlen' => 60], ['k' => 'button_url', 'label' => "$label link", 't' => 'link']];
}

function f_button2(): array
{
    return [['k' => 'cta2_text', 'label' => 'Second button text', 'help' => 'Leave empty to hide.', 'maxlen' => 60], ['k' => 'cta2_url', 'label' => 'Second button link', 't' => 'link']];
}

function f_icon_items(string $singular = 'item', int $max = 12): array
{
    return ['k' => 'items', 'label' => 'Items', 't' => 'repeater', 'singular' => $singular, 'item_label' => 'title', 'max' => $max, 'fields' => [
        ['k' => 'icon', 'label' => 'Icon', 't' => 'icon'],
        ['k' => 'title', 'label' => 'Title', 'required' => true, 'maxlen' => 80],
        ['k' => 'text', 'label' => 'Text', 't' => 'textarea', 'rows' => 2, 'maxlen' => 260],
    ]];
}

function s_page_hero(string $folder): array
{
    return ['label' => 'Hero banner', 'icon' => 'fa-solid fa-panorama', 'template' => 'page-hero', 'fields' => [
        ['k' => 'eyebrow', 'label' => 'Small label', 'maxlen' => 80],
        ['k' => 'title', 'label' => 'Heading', 'required' => true, 'help' => RICH_HELP, 'full' => true, 'maxlen' => 160],
        ['k' => 'content', 'label' => 'Subtitle', 't' => 'textarea', 'rows' => 2, 'maxlen' => 300],
        ['k' => 'image', 'label' => 'Background photo', 't' => 'image', 'folder' => $folder],
    ]];
}

function s_cta(): array
{
    return ['label' => 'Call-to-action banner', 'icon' => 'fa-solid fa-heart', 'template' => 'cta', 'fields' => array_merge(
        [['k' => 'eyebrow', 'label' => 'Small label', 'maxlen' => 80],
         ['k' => 'title', 'label' => 'Heading', 'required' => true, 'help' => RICH_HELP, 'full' => true, 'maxlen' => 160],
         ['k' => 'content', 'label' => 'Text', 't' => 'textarea', 'rows' => 2, 'maxlen' => 300]],
        f_button('Main button'), f_button2(),
        [['k' => 'image', 'label' => 'Background photo', 't' => 'image', 'folder' => 'general']]
    )];
}

function s_steps(string $label = 'Our process'): array
{
    return ['label' => $label, 'icon' => 'fa-solid fa-route', 'template' => 'process', 'fields' => array_merge(f_heading(), [
        ['k' => 'steps', 'label' => 'Steps', 't' => 'repeater', 'singular' => 'step', 'item_label' => 'title', 'max' => 6, 'fields' => [
            ['k' => 'title', 'label' => 'Step title', 'required' => true, 'maxlen' => 60],
            ['k' => 'text', 'label' => 'Description', 't' => 'textarea', 'rows' => 2, 'maxlen' => 260],
        ]],
    ])];
}

function s_legal(string $folder): array
{
    return [
        'hero' => s_page_hero($folder),
        'body' => ['label' => 'Page text', 'icon' => 'fa-solid fa-file-lines', 'template' => 'legal', 'fields' => [
            ['k' => 'title', 'label' => 'Heading', 'required' => true, 'maxlen' => 120, 'full' => true],
            ['k' => 'description', 'label' => '“Last updated” line', 'maxlen' => 80],
            ['k' => 'content', 'label' => 'Text', 't' => 'textarea', 'rows' => 18, 'maxlen' => 30000, 'help' => 'Leave a blank line between paragraphs. Start a line with "## " for a sub-heading.'],
        ]],
    ];
}

/* ------------------------------------------------------------------ pages */

function page_schemas(): array
{
    static $s = null;
    if ($s) return $s;
    return $s = [
        'home' => ['label' => 'Home', 'file' => 'index.php', 'icon' => 'fa-solid fa-house', 'sections' => [
            'hero' => ['label' => 'Hero video slider', 'icon' => 'fa-solid fa-film', 'template' => 'hero-slider', 'note' => 'Slides (videos, headings, buttons) are managed under Hero Slider.', 'fields' => [
                ['k' => 'scroll_text', 'label' => 'Scroll indicator text', 'maxlen' => 30],
            ]],
            'intro' => ['label' => 'Brand introduction', 'icon' => 'fa-solid fa-feather', 'template' => 'intro', 'fields' => array_merge(f_heading(false), [
                ['k' => 'content', 'label' => 'Text', 't' => 'textarea', 'rows' => 6, 'maxlen' => 3000, 'help' => 'Leave a blank line between paragraphs.'],
                ['k' => 'signature', 'label' => 'Signature', 'maxlen' => 60],
                ['k' => 'image', 'label' => 'Large photo', 't' => 'image', 'folder' => 'home'],
                ['k' => 'image2', 'label' => 'Small overlapping photo', 't' => 'image', 'folder' => 'home'],
            ], f_button())],
            'stories' => ['label' => 'Featured wedding stories', 'icon' => 'fa-solid fa-book-open', 'template' => 'stories', 'note' => 'Shows albums marked “Featured” under Portfolio.', 'fields' => array_merge(f_heading(), [
                ['k' => 'limit', 'label' => 'Number of stories', 't' => 'number', 'min' => 1, 'max' => 9, 'default' => 4],
            ], f_button())],
            'services' => ['label' => 'Our services', 'icon' => 'fa-solid fa-camera-retro', 'template' => 'services-grid', 'note' => 'Service cards are managed under Services.', 'fields' => array_merge(f_heading(), [
                ['k' => 'limit', 'label' => 'Number of services', 't' => 'number', 'min' => 1, 'max' => 12, 'default' => 8],
            ], f_button())],
            'recent' => ['label' => 'Recent work', 'icon' => 'fa-solid fa-table-cells-large', 'template' => 'recent-work', 'note' => 'Shows your latest published films (featured first) in a mosaic. Films are managed under Films.', 'fields' => array_merge(f_heading(), [
                ['k' => 'limit', 'label' => 'Number of films', 't' => 'number', 'min' => 1, 'max' => 9, 'default' => 5, 'help' => '5 gives the best layout (one large + four small).'],
            ], f_button())],
            'reels' => ['label' => 'Made for your phone (reels)', 'icon' => 'fa-solid fa-mobile-screen-button', 'template' => 'reels-strip', 'note' => 'Vertical 9:16 reels are managed under Reels.', 'fields' => array_merge(f_heading(), [
                ['k' => 'limit', 'label' => 'Number of reels', 't' => 'number', 'min' => 1, 'max' => 20, 'default' => 10],
            ], f_button())],
            'film' => ['label' => 'Cinematic film showcase', 'icon' => 'fa-solid fa-clapperboard', 'template' => 'film-showcase', 'note' => 'Plays the film marked “Featured” under Films.', 'fields' => array_merge(f_heading(), f_button(), [
                ['k' => 'image', 'label' => 'Background photo', 't' => 'image', 'folder' => 'home'],
            ])],
            'why' => ['label' => 'Why choose us', 'icon' => 'fa-solid fa-award', 'template' => 'why', 'fields' => array_merge(f_heading(), [f_icon_items('reason')])],
            'stats' => ['label' => 'Statistics', 'icon' => 'fa-solid fa-chart-simple', 'template' => 'stats', 'fields' => [
                ['k' => 'items', 'label' => 'Counters', 't' => 'repeater', 'singular' => 'counter', 'item_label' => 'label', 'max' => 6, 'fields' => [
                    ['k' => 'value', 'label' => 'Number', 'required' => true, 'maxlen' => 12, 'placeholder' => '250'],
                    ['k' => 'suffix', 'label' => 'Suffix', 'maxlen' => 6, 'placeholder' => '+'],
                    ['k' => 'label', 'label' => 'Label', 'required' => true, 'maxlen' => 60, 'full' => true],
                ]],
            ]],
            'testimonials' => ['label' => 'Testimonials', 'icon' => 'fa-solid fa-quote-left', 'template' => 'testimonials', 'note' => 'Testimonials are managed under Testimonials.', 'fields' => f_heading()],
            'social' => ['label' => 'Instagram / social gallery', 'icon' => 'fa-brands fa-instagram', 'template' => 'social', 'fields' => array_merge(f_heading(), [
                ['k' => 'handle', 'label' => 'Handle shown on the button', 'placeholder' => '@dreammframmeevent', 'maxlen' => 60],
                ['k' => 'images', 'label' => 'Photos', 't' => 'image_list', 'folder' => 'home', 'max' => 12, 'help' => 'Choose 6 or 12 photos. They link to your Instagram (set in Settings → Social).'],
            ])],
            'cta' => s_cta(),
        ]],
        'about' => ['label' => 'About', 'file' => 'about.php', 'icon' => 'fa-solid fa-users', 'sections' => [
            'hero' => s_page_hero('about'),
            'story' => ['label' => 'Our story', 'icon' => 'fa-solid fa-book-open', 'template' => 'split', 'fields' => array_merge(f_heading(false), [
                ['k' => 'content', 'label' => 'Text', 't' => 'textarea', 'rows' => 6, 'maxlen' => 3000, 'help' => 'Leave a blank line between paragraphs.'],
                ['k' => 'image', 'label' => 'Photo', 't' => 'image', 'folder' => 'about'],
            ])],
            'who' => ['label' => 'Who we are', 'icon' => 'fa-solid fa-user-group', 'template' => 'split-reverse', 'fields' => array_merge(f_heading(false), [
                ['k' => 'content', 'label' => 'Text', 't' => 'textarea', 'rows' => 5, 'maxlen' => 3000],
                ['k' => 'points', 'label' => 'Highlights (one per line)', 't' => 'tags'],
                ['k' => 'image', 'label' => 'Photo', 't' => 'image', 'folder' => 'about'],
            ])],
            'philosophy' => ['label' => 'Photography philosophy', 'icon' => 'fa-solid fa-camera', 'template' => 'philosophy', 'fields' => array_merge(f_heading(false), [
                ['k' => 'content', 'label' => 'Philosophy quote', 't' => 'textarea', 'rows' => 4, 'maxlen' => 800],
                ['k' => 'signature', 'label' => 'Quote author', 'maxlen' => 80],
                ['k' => 'image', 'label' => 'Background photo', 't' => 'image', 'folder' => 'about'],
            ])],
            'mission_vision' => ['label' => 'Mission & vision', 'icon' => 'fa-solid fa-bullseye', 'template' => 'mission-vision', 'fields' => array_merge(f_heading(), [
                ['k' => 'mission_title', 'label' => 'Mission title', 'maxlen' => 60], ['k' => 'vision_title', 'label' => 'Vision title', 'maxlen' => 60],
                ['k' => 'mission', 'label' => 'Our mission', 't' => 'textarea', 'rows' => 3, 'required' => true, 'maxlen' => 600],
                ['k' => 'vision', 'label' => 'Our vision', 't' => 'textarea', 'rows' => 3, 'required' => true, 'maxlen' => 600],
                ['k' => 'image', 'label' => 'Centre photo', 't' => 'image', 'folder' => 'about'],
            ])],
            'approach' => s_steps('Creative approach'),
            'team' => ['label' => 'Our team', 'icon' => 'fa-solid fa-user-group', 'template' => 'team', 'note' => 'Team members are managed under Team.', 'fields' => f_heading()],
            'equipment' => ['label' => 'Our equipment', 'icon' => 'fa-solid fa-camera-retro', 'template' => 'equipment', 'fields' => array_merge(f_heading(), [f_icon_items('item')])],
            'why' => ['label' => 'Why choose us', 'icon' => 'fa-solid fa-award', 'template' => 'why', 'fields' => array_merge(f_heading(), [f_icon_items('reason')])],
            'cta' => s_cta(),
        ]],
        'services' => ['label' => 'Services', 'file' => 'services.php', 'icon' => 'fa-solid fa-camera-retro', 'sections' => [
            'hero' => s_page_hero('services'),
            'list' => ['label' => 'Services list', 'icon' => 'fa-solid fa-list', 'template' => 'services-detail', 'note' => 'Each service (cover, gallery, features) is managed under Services.', 'fields' => f_heading()],
            'process' => s_steps('Booking process'),
            'cta' => s_cta(),
        ]],
        'portfolio' => ['label' => 'Portfolio', 'file' => 'portfolio.php', 'icon' => 'fa-solid fa-images', 'sections' => [
            'hero' => s_page_hero('portfolio'),
            'albums' => ['label' => 'Wedding albums', 'icon' => 'fa-solid fa-book-open', 'template' => 'albums-grid', 'note' => 'Albums and photos are managed under Portfolio and Albums.', 'fields' => f_heading()],
            'moments' => ['label' => 'Moments gallery (masonry + lightbox)', 'icon' => 'fa-solid fa-border-all', 'template' => 'moments', 'fields' => array_merge(f_heading(), [
                ['k' => 'limit', 'label' => 'Number of photos', 't' => 'number', 'min' => 6, 'max' => 60, 'default' => 24],
            ])],
            'cta' => s_cta(),
        ]],
        'films' => ['label' => 'Films', 'file' => 'films.php', 'icon' => 'fa-solid fa-film', 'sections' => [
            'hero' => s_page_hero('films'),
            'featured' => ['label' => 'Featured film', 'icon' => 'fa-solid fa-star', 'template' => 'film-featured', 'note' => 'Uses the film marked “Featured” under Films.', 'fields' => f_heading()],
            'grid' => ['label' => 'Film gallery', 'icon' => 'fa-solid fa-table-cells-large', 'template' => 'films-grid', 'fields' => f_heading()],
            'cta' => s_cta(),
        ]],
        'contact' => ['label' => 'Contact', 'file' => 'contact.php', 'icon' => 'fa-solid fa-envelope', 'sections' => [
            'hero' => s_page_hero('general'),
            'form' => ['label' => 'Contact details & booking form', 'icon' => 'fa-solid fa-calendar-check', 'template' => 'booking-form', 'note' => 'Phone, email, address and the form’s option lists are in Settings → Contact / Booking form.', 'fields' => array_merge(f_heading(), [
                ['k' => 'form_title', 'label' => 'Form title', 'required' => true, 'help' => RICH_HELP, 'maxlen' => 120],
                ['k' => 'content', 'label' => 'Form intro', 't' => 'textarea', 'rows' => 2, 'maxlen' => 300],
            ])],
            'map' => ['label' => 'Google map', 'icon' => 'fa-solid fa-map-location-dot', 'template' => 'map', 'fields' => [
                ['k' => 'embed_url', 'label' => 'Google Maps embed URL', 't' => 'url', 'full' => true, 'help' => 'Google Maps → Share → Embed a map → copy only the link inside src="…".'],
            ]],
            'cta' => s_cta(),
        ]],
        'privacy' => ['label' => 'Privacy Policy', 'file' => 'privacy-policy.php', 'icon' => 'fa-solid fa-shield-halved', 'sections' => s_legal('general')],
        'terms' => ['label' => 'Terms', 'file' => 'terms.php', 'icon' => 'fa-solid fa-file-contract', 'sections' => s_legal('general')],
    ];
}

/* -------------------------------------------------------------- collections */

function f_status(string $label = 'Published (visible on the website)'): array { return ['k' => 'status', 'label' => $label, 't' => 'status', 'default' => 'published']; }

function option_list(string $settingKey): array
{
    $list = json_dec(setting($settingKey), []);
    if (!$list) $list = json_dec(default_settings()[$settingKey] ?? '[]', []);
    return array_combine($list, $list);
}

function collection_schemas(): array
{
    static $c = null;
    if ($c) return $c;
    return $c = [
        'slides' => [
            'table' => 'hero_slides', 'label' => 'Hero Slider', 'singular' => 'slide', 'icon' => 'fa-solid fa-film', 'folder' => 'hero', 'admin' => 'hero-slider.php', 'public' => 'index.php',
            'title' => 'heading', 'subtitle' => 'subtitle', 'thumb' => 'poster_image', 'search' => ['heading', 'subtitle', 'eyebrow'], 'preview' => true,
            'fields' => [
                ['k' => 'eyebrow', 'label' => 'Small eyebrow text', 'maxlen' => 80],
                ['k' => 'heading', 'label' => 'Main heading', 'required' => true, 'help' => RICH_HELP, 'full' => true, 'maxlen' => 140],
                ['k' => 'subtitle', 'label' => 'Subtitle', 't' => 'textarea', 'rows' => 2, 'maxlen' => 260],
                ['k' => 'video_path', 'label' => 'Background video — MP4 (H.264)', 't' => 'video', 'accept' => 'mp4', 'help' => 'Upload a short, compressed MP4 (8–20 s, 1920×1080 or 1280×720, under ' . (VIDEO_MAX_BYTES / 1048576) . ' MB, no sound needed).'],
                ['k' => 'video_webm_path', 'label' => 'Same video as WebM (optional, smaller)', 't' => 'video', 'accept' => 'webm'],
                ['k' => 'mobile_video_path', 'label' => 'Portrait video for phones (optional MP4)', 't' => 'video', 'accept' => 'mp4', 'help' => 'Vertical 9:16 clip. Without it, phones on slow connections see the poster photo.'],
                ['k' => 'video_url', 'label' => 'Or an external video file URL', 't' => 'url', 'help' => 'Direct link to an .mp4/.webm file on a CDN (YouTube/Vimeo pages can’t be used as silent background videos). Used when no file is uploaded.'],
                ['k' => 'poster_image', 'label' => 'Poster photo (shown while loading & as fallback)', 't' => 'image', 'folder' => 'hero', 'required' => true],
                ['k' => 'primary_button_text', 'label' => 'Primary button text', 'maxlen' => 60],
                ['k' => 'primary_button_url', 'label' => 'Primary button link', 't' => 'link'],
                ['k' => 'secondary_button_text', 'label' => 'Secondary button text', 'maxlen' => 60],
                ['k' => 'secondary_button_url', 'label' => 'Secondary button link', 't' => 'link'],
                ['k' => 'duration', 'label' => 'Slide duration (seconds)', 't' => 'number', 'min' => 3, 'max' => 60, 'default' => 8],
                ['k' => 'overlay_opacity', 'label' => 'Dark overlay strength (0–90 %)', 't' => 'number', 'min' => 0, 'max' => 90, 'default' => 45],
                ['k' => 'text_alignment', 'label' => 'Text alignment', 't' => 'select', 'options' => ['left' => 'Left', 'center' => 'Centre', 'right' => 'Right'], 'default' => 'left'],
                f_status('Active (shown in the slider)'),
            ],
        ],
        'services' => [
            'table' => 'services', 'label' => 'Services', 'singular' => 'service', 'icon' => 'fa-solid fa-camera-retro', 'folder' => 'services', 'admin' => 'services.php', 'public' => 'services.php',
            'title' => 'title', 'subtitle' => 'tagline', 'thumb' => 'cover_image', 'icon_field' => 'icon', 'slug_from' => 'title', 'search' => ['title', 'tagline', 'description'],
            'fields' => [
                ['k' => 'title', 'label' => 'Service name', 'required' => true, 'maxlen' => 150],
                ['k' => 'tagline', 'label' => 'Short tagline', 'maxlen' => 160],
                ['k' => 'icon', 'label' => 'Icon', 't' => 'icon'],
                ['k' => 'cover_image', 'label' => 'Cover photo', 't' => 'image', 'folder' => 'services'],
                ['k' => 'description', 'label' => 'Short description (cards)', 't' => 'textarea', 'rows' => 3, 'required' => true, 'maxlen' => 500],
                ['k' => 'details', 'label' => 'Service details (Services page)', 't' => 'textarea', 'rows' => 6, 'maxlen' => 4000, 'help' => 'Leave a blank line between paragraphs.'],
                ['k' => 'features', 'label' => 'Features / what’s included (one per line)', 't' => 'tags'],
                ['k' => 'price_note', 'label' => 'Package note', 'placeholder' => 'e.g. Packages from ₹75,000', 'maxlen' => 100],
                ['k' => 'gallery', 'label' => 'Gallery photos', 't' => 'image_list', 'folder' => 'services', 'max' => 12],
                ['k' => 'is_featured', 'label' => 'Show on the Home page', 't' => 'toggle', 'default' => true],
                f_status(),
            ],
        ],
        'albums' => [
            'table' => 'portfolio_albums', 'label' => 'Portfolio', 'singular' => 'album', 'icon' => 'fa-solid fa-book-open', 'folder' => 'albums', 'admin' => 'portfolio.php', 'public' => 'portfolio.php',
            'title' => 'title', 'subtitle' => 'couple_names', 'thumb' => 'cover_image', 'slug_from' => 'title', 'search' => ['title', 'couple_names', 'location', 'category'], 'photos' => true,
            'fields' => [
                ['k' => 'title', 'label' => 'Album title', 'required' => true, 'maxlen' => 150, 'placeholder' => 'e.g. A Royal Udaipur Wedding'],
                ['k' => 'couple_names', 'label' => 'Couple names', 'maxlen' => 150, 'placeholder' => 'e.g. Aarav & Isha'],
                ['k' => 'category', 'label' => 'Category', 't' => 'select', 'options' => option_list('portfolio_categories'), 'required' => true],
                ['k' => 'event_date', 'label' => 'Wedding / event date', 't' => 'date'],
                ['k' => 'location', 'label' => 'Location', 'maxlen' => 150, 'placeholder' => 'e.g. Kolkata, West Bengal'],
                ['k' => 'cover_image', 'label' => 'Cover photo', 't' => 'image', 'folder' => 'albums', 'required' => true],
                ['k' => 'description', 'label' => 'Story / description', 't' => 'textarea', 'rows' => 5, 'maxlen' => 4000],
                ['k' => 'is_featured', 'label' => 'Featured (shown on the Home page)', 't' => 'toggle', 'default' => false],
                f_status(),
            ],
        ],
        'films' => [
            'table' => 'films', 'label' => 'Films', 'singular' => 'film', 'icon' => 'fa-solid fa-film', 'folder' => 'films', 'admin' => 'films.php', 'public' => 'films.php',
            'title' => 'title', 'subtitle' => 'couple_names', 'thumb' => 'thumbnail', 'slug_from' => 'title', 'search' => ['title', 'couple_names', 'location', 'category'],
            'one_of' => [['video_path', 'video_url', 'Upload the film as an MP4 or paste a YouTube / Vimeo link.']],
            'fields' => [
                ['k' => 'title', 'label' => 'Film title', 'required' => true, 'maxlen' => 150],
                ['k' => 'couple_names', 'label' => 'Couple / project name', 'maxlen' => 150],
                ['k' => 'category', 'label' => 'Type', 't' => 'select', 'options' => ['Wedding Film' => 'Wedding Film', 'Highlights' => 'Highlights', 'Pre-Wedding' => 'Pre-Wedding', 'Teaser' => 'Teaser', 'Event Film' => 'Event Film'], 'default' => 'Wedding Film'],
                ['k' => 'video_path', 'label' => 'Upload film — MP4 (16:9)', 't' => 'video', 'accept' => 'mp4', 'help' => 'Upload the film directly: MP4 (H.264), 1920×1080 or 1280×720, under ' . (VIDEO_MAX_BYTES / 1048576) . ' MB. On the website it previews silently on hover and opens full-screen with sound.'],
                ['k' => 'video_url', 'label' => 'Or a video link (YouTube, Vimeo or .mp4)', 't' => 'video_url', 'help' => 'Used when no MP4 is uploaded. Paste the normal YouTube/Vimeo page link — it is converted to a safe embed automatically. Best for long full wedding films.'],
                ['k' => 'thumbnail', 'label' => 'Thumbnail / cover photo (16:9)', 't' => 'image', 'folder' => 'films', 'help' => 'Recommended: a sharp 1920×1080 frame from the film. Leave empty for YouTube links (their thumbnail is used) — for uploaded MP4s the first frame is shown.'],
                ['k' => 'description', 'label' => 'Description', 't' => 'textarea', 'rows' => 3, 'maxlen' => 1000],
                ['k' => 'location', 'label' => 'Location', 'maxlen' => 150],
                ['k' => 'event_date', 'label' => 'Date', 't' => 'date'],
                ['k' => 'duration_label', 'label' => 'Length', 'placeholder' => 'e.g. 4:32', 'maxlen' => 20],
                ['k' => 'is_featured', 'label' => 'Featured film (Home & Films page)', 't' => 'toggle', 'default' => false],
                f_status(),
            ],
        ],
        'reels' => [
            'table' => 'reels', 'label' => 'Reels', 'singular' => 'reel', 'icon' => 'fa-solid fa-mobile-screen-button', 'folder' => 'reels', 'admin' => 'reels.php', 'public' => 'index.php',
            'title' => 'title', 'subtitle' => 'category', 'thumb' => 'poster_image', 'search' => ['title', 'couple_names', 'category'],
            'one_of' => [['video_path', 'video_url', 'Upload a vertical MP4 or paste a YouTube Shorts / Vimeo link.']],
            'fields' => [
                ['k' => 'title', 'label' => 'Reel title', 'required' => true, 'maxlen' => 120, 'placeholder' => 'e.g. Haldi in 30 seconds'],
                ['k' => 'couple_names', 'label' => 'Couple / event name', 'maxlen' => 150, 'placeholder' => 'e.g. Aarav & Isha'],
                ['k' => 'category', 'label' => 'Type', 't' => 'select', 'options' => ['Reel' => 'Reel', 'Highlights' => 'Highlights', 'Teaser' => 'Teaser', 'Pre-Wedding' => 'Pre-Wedding', 'Behind the scenes' => 'Behind the scenes'], 'default' => 'Reel'],
                ['k' => 'video_path', 'label' => 'Vertical video — MP4 (9:16)', 't' => 'video', 'accept' => 'mp4', 'help' => 'Upload a portrait 9:16 clip (1080×1920 or 720×1280, 10–60 s, under ' . (VIDEO_MAX_BYTES / 1048576) . ' MB). It plays silently on the Home page while visible.'],
                ['k' => 'video_url', 'label' => 'Or a YouTube Shorts / Vimeo / .mp4 link', 't' => 'video_url', 'help' => 'Used when no video is uploaded. Instagram links cannot be played on other websites — upload the MP4 instead.'],
                ['k' => 'poster_image', 'label' => 'Cover photo (portrait 9:16)', 't' => 'image', 'folder' => 'reels', 'help' => 'Shown before the video plays. Leave empty for a YouTube link to use its thumbnail.'],
                f_status(),
            ],
        ],
        'testimonials' => [
            'table' => 'testimonials', 'label' => 'Testimonials', 'singular' => 'testimonial', 'icon' => 'fa-solid fa-quote-left', 'folder' => 'testimonials', 'admin' => 'testimonials.php', 'public' => 'index.php',
            'title' => 'client_name', 'subtitle' => 'testimonial', 'thumb' => 'client_image', 'search' => ['client_name', 'event_type', 'testimonial'],
            'fields' => [
                ['k' => 'client_name', 'label' => 'Client / couple names', 'required' => true, 'maxlen' => 150],
                ['k' => 'event_type', 'label' => 'Event type', 'maxlen' => 80, 'placeholder' => 'e.g. Wedding'],
                ['k' => 'location', 'label' => 'Location', 'maxlen' => 120],
                ['k' => 'rating', 'label' => 'Star rating (0–5)', 't' => 'number', 'min' => 0, 'max' => 5, 'default' => 5],
                ['k' => 'testimonial', 'label' => 'Testimonial', 't' => 'textarea', 'rows' => 4, 'required' => true, 'maxlen' => 1000],
                ['k' => 'client_image', 'label' => 'Photo (optional)', 't' => 'image', 'folder' => 'testimonials'],
                f_status(),
            ],
        ],
        'team' => [
            'table' => 'team_members', 'label' => 'Team', 'singular' => 'team member', 'icon' => 'fa-solid fa-user-group', 'folder' => 'team', 'admin' => 'team.php', 'public' => 'about.php',
            'title' => 'name', 'subtitle' => 'designation', 'thumb' => 'profile_image', 'search' => ['name', 'designation', 'bio'],
            'fields' => [
                ['k' => 'name', 'label' => 'Name', 'required' => true, 'maxlen' => 120],
                ['k' => 'designation', 'label' => 'Designation', 'required' => true, 'maxlen' => 120],
                ['k' => 'bio', 'label' => 'Biography', 't' => 'textarea', 'rows' => 3, 'maxlen' => 600],
                ['k' => 'profile_image', 'label' => 'Profile photo (portrait)', 't' => 'image', 'folder' => 'team'],
                ['k' => 'social_links', 'label' => 'Social links', 't' => 'group', 'fields' => [
                    ['k' => 'instagram', 'label' => 'Instagram', 't' => 'url'], ['k' => 'facebook', 'label' => 'Facebook', 't' => 'url'], ['k' => 'youtube', 'label' => 'YouTube', 't' => 'url'],
                ]],
                f_status(),
            ],
        ],
    ];
}

/* ----------------------------------------------------------------- settings */

function settings_groups(): array
{
    return [
        'branding' => ['label' => 'Branding', 'icon' => 'fa-solid fa-palette', 'fields' => [
            ['k' => 'site_name', 'label' => 'Brand name', 'required' => true, 'maxlen' => 80],
            ['k' => 'tagline', 'label' => 'Tagline (under the logo text)', 'maxlen' => 80],
            ['k' => 'logo', 'label' => 'Navbar logo (horizontal)', 't' => 'image', 'folder' => 'branding', 'help' => 'Transparent PNG/WebP, wide format (emblem + name side by side). Leave empty to use the text logo.'],
            ['k' => 'logo_stacked', 'label' => 'Stacked logo (footer & loading screen)', 't' => 'image', 'folder' => 'branding', 'help' => 'Transparent PNG/WebP with the emblem above the name. Leave empty to reuse the navbar logo.'],
            ['k' => 'favicon', 'label' => 'Favicon', 't' => 'image', 'folder' => 'branding'],
            ['k' => 'nav_cta_text', 'label' => 'Header button text', 'maxlen' => 40],
            ['k' => 'nav_cta_link', 'label' => 'Header button link', 't' => 'link'],
        ]],
        'contact' => ['label' => 'Contact details', 'icon' => 'fa-solid fa-address-book', 'fields' => [
            ['k' => 'email', 'label' => 'Email', 't' => 'email', 'required' => true],
            ['k' => 'phone', 'label' => 'Phone', 'maxlen' => 30, 'pattern' => 'phone'],
            ['k' => 'whatsapp', 'label' => 'WhatsApp number (with country code)', 'maxlen' => 30, 'pattern' => 'phone'],
            ['k' => 'hours', 'label' => 'Studio hours', 'maxlen' => 80],
            ['k' => 'address', 'label' => 'Office / studio address', 't' => 'textarea', 'rows' => 2, 'maxlen' => 300],
        ]],
        'social' => ['label' => 'Social media', 'icon' => 'fa-solid fa-share-nodes', 'fields' => [
            ['k' => 'social_instagram', 'label' => 'Instagram', 't' => 'url'], ['k' => 'social_facebook', 'label' => 'Facebook', 't' => 'url'],
            ['k' => 'social_youtube', 'label' => 'YouTube', 't' => 'url'], ['k' => 'social_vimeo', 'label' => 'Vimeo', 't' => 'url'],
            ['k' => 'social_pinterest', 'label' => 'Pinterest', 't' => 'url'],
        ]],
        'booking' => ['label' => 'Booking form', 'icon' => 'fa-solid fa-calendar-check', 'fields' => [
            ['k' => 'event_types', 'label' => 'Event type options (one per line)', 't' => 'tags', 'required' => true],
            ['k' => 'service_options', 'label' => 'Interested service options (one per line)', 't' => 'tags', 'required' => true],
            ['k' => 'budget_options', 'label' => 'Budget options (one per line)', 't' => 'tags', 'required' => true],
            ['k' => 'portfolio_categories', 'label' => 'Portfolio categories (one per line)', 't' => 'tags', 'required' => true, 'help' => 'Used for album categories and the Portfolio filter buttons.'],
            ['k' => 'max_event_days', 'label' => 'Maximum number of event days', 't' => 'number', 'min' => 1, 'max' => 15, 'default' => 7],
            ['k' => 'booking_note', 'label' => 'Booking information (shown beside the form)', 't' => 'textarea', 'rows' => 3, 'maxlen' => 600],
            ['k' => 'booking_success', 'label' => 'Success message', 't' => 'textarea', 'rows' => 2, 'required' => true, 'maxlen' => 300],
        ]],
        'footer' => ['label' => 'Footer', 'icon' => 'fa-solid fa-shoe-prints', 'fields' => [
            ['k' => 'footer_headline', 'label' => 'Footer headline', 'help' => RICH_HELP, 'full' => true, 'maxlen' => 120],
            ['k' => 'footer_cta_text', 'label' => 'Footer button text', 'maxlen' => 40],
            ['k' => 'footer_cta_link', 'label' => 'Footer button link', 't' => 'link'],
            ['k' => 'footer_about', 'label' => 'Brand introduction', 't' => 'textarea', 'rows' => 3, 'maxlen' => 400],
            ['k' => 'footer_copyright', 'label' => 'Copyright line', 'help' => '{year} becomes the current year.', 'maxlen' => 120],
        ]],
        'seo' => ['label' => 'SEO', 'icon' => 'fa-solid fa-magnifying-glass', 'fields' => [
            ['k' => 'seo_title', 'label' => 'SEO title (site name in Google)', 'required' => true, 'maxlen' => 70],
            ['k' => 'seo_description', 'label' => 'Default SEO description', 't' => 'textarea', 'rows' => 2, 'maxlen' => 170],
            ['k' => 'seo_keywords', 'label' => 'Keywords', 't' => 'textarea', 'rows' => 2, 'maxlen' => 300],
            ['k' => 'og_image', 'label' => 'Social share image (1200×630)', 't' => 'image', 'folder' => 'branding'],
        ]],
    ];
}

function page_seo_fields(): array
{
    return [
        ['k' => 'meta_title', 'label' => 'Page title (browser tab & Google)', 'maxlen' => 70, 'help' => 'Shown as “Title | Brand”. Keep under 60 characters.'],
        ['k' => 'meta_description', 'label' => 'Meta description', 't' => 'textarea', 'rows' => 2, 'maxlen' => 170, 'help' => '140–160 characters is ideal.'],
    ];
}

const ENQUIRY_STATUSES = [
    'new' => 'New', 'contacted' => 'Contacted', 'follow_up' => 'Follow-up', 'quotation_sent' => 'Quotation Sent',
    'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled',
];
const MEDIA_FOLDERS = ['general', 'home', 'about', 'hero', 'services', 'albums', 'films', 'team', 'testimonials', 'branding', 'videos'];

<?php
/** @var array $page */
declare(strict_types=1);
defined('APP_NAME') || exit; // no direct access
$footerServices = array_slice(public_collection('services'), 0, 6);
$contacts = [
    'phone' => ['fa-solid fa-phone', setting('phone')], 'whatsapp' => ['fa-brands fa-whatsapp', setting('whatsapp')],
    'email' => ['fa-solid fa-envelope', setting('email')], 'address' => ['fa-solid fa-location-dot', setting('address')],
];
$wa = contact_href('whatsapp', setting('whatsapp'));
$mono = initials(setting('site_name', APP_NAME));
?>
  </main>

  <footer class="footer">
    <div class="footer__glow" aria-hidden="true"></div>
    <?php if (setting('footer_headline') !== ''): ?>
    <div class="container footer__top reveal">
      <h2 class="footer__headline"><?= rich(setting('footer_headline')) ?></h2>
      <?php cms_button(setting('footer_cta_text'), setting('footer_cta_link', 'contact.php'), 'btn btn--spark'); ?>
    </div>
    <?php endif; ?>
    <div class="container footer__grid">
      <div class="footer__brand">
        <a href="<?= e(url()) ?>" class="brand brand--footer" aria-label="<?= e(setting('site_name')) ?> — home">
          <?php if ($fl = media_url(setting('logo_stacked') ?: setting('logo'))): ?><img class="brand__img<?= setting('logo_stacked') ? ' brand__img--stacked' : '' ?>" src="<?= e($fl) ?>" alt="<?= e(setting('site_name')) ?>" loading="lazy" decoding="async">
          <?php else: ?><span class="brand__mono" aria-hidden="true"><span><?= e($mono) ?></span></span><span class="brand__text"><?= e(setting('site_name')) ?><small><?= e(setting('tagline')) ?></small></span><?php endif; ?>
        </a>
        <p><?= e(setting('footer_about')) ?></p>
        <?php social_links(site_socials()); ?>
      </div>
      <div class="footer__col">
        <h4>Explore</h4>
        <ul>
          <li><a href="<?= e(url()) ?>">Home</a></li>
          <li><a href="<?= e(url('about.php')) ?>">About Us</a></li>
          <li><a href="<?= e(url('portfolio.php')) ?>">Portfolio</a></li>
          <li><a href="<?= e(url('films.php')) ?>">Films</a></li>
          <li><a href="<?= e(url('contact.php')) ?>">Book Your Date</a></li>
        </ul>
      </div>
      <div class="footer__col">
        <h4>Services</h4>
        <ul>
          <?php foreach ($footerServices as $svc): ?><li><a href="<?= e(url('services.php#' . ($svc['slug'] ?? slugify($svc['title'])))) ?>"><?= e($svc['title']) ?></a></li><?php endforeach; ?>
        </ul>
      </div>
      <div class="footer__col">
        <h4>Contact</h4>
        <ul class="footer__contact">
          <?php foreach ($contacts as $type => [$icon, $val]): if ($val === '') continue; ?>
            <li><i class="<?= $icon ?>" aria-hidden="true"></i><a href="<?= e(contact_href($type, $val)) ?>"<?= in_array($type, ['whatsapp', 'address'], true) ? ' target="_blank" rel="noopener"' : '' ?>><?= e($val) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <div class="container footer__bottom">
      <p><?= e(setting('footer_copyright')) ?></p>
      <p class="footer__legal"><a href="<?= e(url('privacy-policy.php')) ?>">Privacy Policy</a><a href="<?= e(url('terms.php')) ?>">Terms</a></p>
    </div>
  </footer>

  <?php if ($wa): ?><a class="wa-float magnetic" href="<?= e($wa) ?>" target="_blank" rel="noopener" aria-label="Chat on WhatsApp"><i class="fa-brands fa-whatsapp"></i></a><?php endif; ?>
  <button class="to-top" type="button" aria-label="Back to top"><i class="fa-solid fa-arrow-up"></i></button>

  <div class="lb" id="lightbox" role="dialog" aria-modal="true" aria-label="Photo viewer" hidden>
    <div class="lb__top"><span data-lb-count></span><button class="lb__btn" type="button" data-lb-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button></div>
    <div class="lb__stage">
      <button class="lb__btn lb__prev" type="button" data-lb-prev aria-label="Previous photo"><i class="fa-solid fa-arrow-left"></i></button>
      <img alt="" data-lb-img>
      <button class="lb__btn lb__next" type="button" data-lb-next aria-label="Next photo"><i class="fa-solid fa-arrow-right"></i></button>
    </div>
    <p class="lb__caption" data-lb-caption></p>
  </div>
  <div class="vmodal" id="videoModal" role="dialog" aria-modal="true" aria-label="Video player" hidden>
    <p class="vmodal__title" data-vm-title></p>
    <button class="lb__btn vmodal__close" type="button" data-vm-close aria-label="Close video"><i class="fa-solid fa-xmark"></i></button>
    <div class="vmodal__frame" data-vm-frame></div>
  </div>

  <script src="<?= e(asset('assets/js/animations.js')) ?>" defer></script>
  <script src="<?= e(asset('assets/js/main.js')) ?>" defer></script>
  <?php if (($page['slug'] ?? '') === 'home'): ?><script src="<?= e(asset('assets/js/hero-slider.js')) ?>" defer></script><?php endif; ?>
  <?php if (($page['slug'] ?? '') === 'contact'): ?><script src="<?= e(asset('assets/js/contact.js')) ?>" defer></script><?php endif; ?>
</body>
</html>

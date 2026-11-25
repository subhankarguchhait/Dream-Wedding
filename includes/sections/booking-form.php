<?php defined('APP_NAME') || exit;
/** @var array $s  Booking enquiry form. Dropdown options are editable in Admin → Settings → Booking. */
$eventTypes = option_list('event_types');
$serviceOptions = option_list('service_options');
$budgets = option_list('budget_options');
$maxDays = max(1, min(30, (int)(setting('max_event_days') ?: 7)));
$wanted = get_str('service', 150);
if ($wanted !== '' && !isset($serviceOptions[$wanted])) {
    // Coming from a service page: map "Wedding Photography" etc. to the closest booking option.
    $match = '';
    foreach ($serviceOptions as $o) if (stripos($wanted, $o) !== false || stripos($o, $wanted) !== false) { $match = $o; break; }
    $wanted = $match;
}
$info = [
    'email' => ['fa-solid fa-envelope', 'Email'], 'phone' => ['fa-solid fa-phone', 'Phone'],
    'whatsapp' => ['fa-brands fa-whatsapp', 'WhatsApp'], 'address' => ['fa-solid fa-location-dot', 'Studio'],
];
$opt = function (array $list, string $placeholder, string $selected = ''): string {
    $h = '<option value="" disabled' . ($selected === '' ? ' selected' : '') . '>' . e($placeholder) . '</option>';
    foreach ($list as $o) $h .= '<option value="' . e($o) . '"' . ($o === $selected ? ' selected' : '') . '>' . e($o) . '</option>';
    return $h;
};
?>
<section class="section contact" data-section="form">
  <div class="container contact__grid">
    <aside class="contact__info">
      <?php sec_head($s); ?>
      <ul class="info-list reveal">
        <?php foreach ($info as $type => [$icon, $label]): $val = setting($type); if ($val === '') continue; ?>
          <li><span class="info-list__icon"><i class="<?= $icon ?>" aria-hidden="true"></i></span><div><small><?= $label ?></small><a href="<?= e(contact_href($type, $val)) ?>"<?= in_array($type, ['whatsapp', 'address'], true) ? ' target="_blank" rel="noopener"' : '' ?>><?= e($val) ?></a></div></li>
        <?php endforeach; ?>
        <?php if (setting('hours') !== ''): ?><li><span class="info-list__icon"><i class="fa-regular fa-clock" aria-hidden="true"></i></span><div><small>Hours</small><span><?= e(setting('hours')) ?></span></div></li><?php endif; ?>
      </ul>
      <?php if (setting('booking_note') !== ''): ?><p class="booking-note reveal"><i class="fa-regular fa-calendar-check" aria-hidden="true"></i><?= e(setting('booking_note')) ?></p><?php endif; ?>
      <div class="contact__social reveal"><small>Follow our stories</small><?php social_links(site_socials(), 'socials socials--dark'); ?></div>
    </aside>

    <div class="contact__form-wrap reveal">
      <h2 class="contact__form-title"><?= rich(sv($s, 'form_title', 'Check your *date*')) ?></h2>
      <?php if (sv($s, 'content') !== ''): ?><p class="contact__form-text"><?= e(sv($s, 'content')) ?></p><?php endif; ?>
      <form class="form" id="contactForm" action="<?= e(url('actions/booking-submit.php')) ?>" method="post" novalidate data-max-days="<?= $maxDays ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="form_stamp" value="<?= e(form_stamp()) ?>">
        <div class="row g-3">
          <div class="col-md-6"><div class="field"><input id="bf-name" name="name" type="text" placeholder=" " autocomplete="name" maxlength="100" required><label for="bf-name">Full name *</label><small class="field__err"></small></div></div>
          <div class="col-md-6"><div class="field"><input id="bf-partner" name="partner_name" type="text" placeholder=" " maxlength="100"><label for="bf-partner">Partner's name</label><small class="field__err"></small></div></div>
          <div class="col-md-6"><div class="field"><input id="bf-email" name="email" type="email" placeholder=" " autocomplete="email" maxlength="150" required><label for="bf-email">Email address *</label><small class="field__err"></small></div></div>
          <div class="col-md-6"><div class="field"><input id="bf-phone" name="phone" type="tel" placeholder=" " autocomplete="tel" maxlength="20" required><label for="bf-phone">Phone / WhatsApp *</label><small class="field__err"></small></div></div>
          <div class="col-md-6"><div class="field field--select"><select id="bf-type" name="event_type" required><?= $opt($eventTypes, 'Select event type') ?></select><label for="bf-type">Event type *</label><small class="field__err"></small></div></div>
          <div class="col-md-6"><div class="field field--date"><input id="bf-date" name="event_date" type="date" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+3 years')) ?>" required><label for="bf-date">Event date *</label><small class="field__err"></small></div></div>
          <div class="col-md-8"><div class="field"><input id="bf-location" name="event_location" type="text" placeholder=" " maxlength="150" required><label for="bf-location">Event location / city *</label><small class="field__err"></small></div></div>
          <div class="col-md-4"><div class="field"><input id="bf-days" name="event_days" type="number" min="1" max="<?= $maxDays ?>" step="1" value="1" placeholder=" " inputmode="numeric" required><label for="bf-days">Event days *</label><small class="field__err"></small></div></div>
          <div class="col-md-6"><div class="field field--select"><select id="bf-service" name="service" required><?= $opt($serviceOptions, 'Select a service', $wanted) ?></select><label for="bf-service">Interested service *</label><small class="field__err"></small></div></div>
          <div class="col-md-6"><div class="field field--select"><select id="bf-budget" name="budget" required><?= $opt($budgets, 'Select a range') ?></select><label for="bf-budget">Estimated budget *</label><small class="field__err"></small></div></div>
          <div class="col-12"><div class="field"><textarea id="bf-message" name="message" rows="5" placeholder=" " maxlength="3000" required></textarea><label for="bf-message">Tell us about your celebration *</label><small class="field__err"></small></div></div>
        </div>
        <div class="hp" aria-hidden="true"><label for="bf-website">Leave this field empty</label><input id="bf-website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
        <label class="consent"><input type="checkbox" name="consent" value="1" required><span>I agree to be contacted by <?= e(setting('site_name')) ?> about this enquiry. See our <a href="<?= e(url('privacy-policy.php')) ?>">privacy policy</a>.</span></label>
        <small class="field__err" data-err="consent"></small>
        <button class="btn btn--spark btn--lg btn--block magnetic" type="submit"><span class="btn__label">Check availability</span><i class="fa-solid fa-paper-plane" aria-hidden="true"></i></button>
        <div class="form__status" role="status" aria-live="polite"></div>
      </form>
    </div>
  </div>
</section>

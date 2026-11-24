<?php
declare(strict_types=1);

// Only reached after a booking, so every visit Google Ads counts here is a real lead.
require_once __DIR__ . '/includes/boot.php';

$b = $_SESSION['last_booking'] ?? null;
if (!isset($b['ref'])) redirect('book');
$new = !empty($_SESSION['booking_uncounted']);   // a refresh does not count again
unset($_SESSION['booking_uncounted']);
siteOpen('Thank you | ' . APP_NAME, ['path' => 'thank-you', 'noindex' => true, 'description' => 'We have your booking.']);
?>
<section class="sec"><div class="wrap done"><div class="meter"><?= lcd('donE', '', 'Done') ?></div><h1>We have your booking</h1><p class="sub">You asked for <strong><?= e($b['when']) ?></strong>. Your reference is <strong><?= e($b['ref']) ?></strong>. We will confirm the time<?= $b['email'] ? ' by email, WhatsApp or phone' : ' by WhatsApp or phone' ?>. Send it on WhatsApp too and you can add photos.</p><div class="row"><?= waButton("Hi, I booked online. Ref {$b['ref']}. {$b['appliance']} in {$b['area']}, {$b['when']}. Problem: {$b['problem']}", 'Send on WhatsApp', 'btn btn-wa btn-lg') ?><?= callButton('btn btn-line btn-lg') ?></div></div></section>
<?php siteClose($new ? "<script>window.addEventListener('load',function(){if(window.gtag)gtag('event','generate_lead',{method:'booking_form'});if(window.fbq)fbq('track','Lead');});</script>" : '');

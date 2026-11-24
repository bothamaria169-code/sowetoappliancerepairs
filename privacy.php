<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';
$mail = e(biz('biz_email'));
siteOpen('Privacy Notice | ' . APP_NAME, ['path' => 'privacy', 'crumbs' => [['Privacy', 'privacy']], 'description' => 'How Soweto Appliance Repairs uses and protects your personal information under POPIA.']);
?>
<section class="top top--plain"><div class="wrap top__in"><div><?= crumbs([['Privacy', 'privacy']]) ?><h1>Privacy notice</h1><p class="sub">How we handle your information under POPIA.</p></div></div></section>
<section class="sec"><div class="wrap prose">
<h2>What we collect</h2><p>When you book: your name, phone number, email (if you give it), area, address, the appliance, what is wrong with it and any photo you add. Hidden location data in photos is removed when you upload them. When you use the chat: the messages you type.</p>
<h2>Why</h2><p>To contact you, plan the visit, do the repair and honour the guarantee. We do not sell your information.</p>
<h2>Who helps us</h2><p>Our hosting company stores the website. Chat messages are sent to an AI service (Groq) to write the answer, so please do not type private details there. Photos we publish may be stored on ImageKit. If you use WhatsApp, its own privacy terms apply.</p>
<h2>Website statistics</h2><p>We count page visits per day without cookies and without storing who you are. If Google Analytics, Google Ads or the Meta Pixel is switched on, those companies may set their own cookies.</p>
<h2>How long</h2><p>Bookings are kept for as long as we need them for the guarantee and for tax records. You may ask us to delete yours sooner where the law allows.</p>
<h2>Your rights</h2><p>You may ask to see, correct or delete your information, or object to how we use it. Email <a href="mailto:<?= $mail ?>"><?= $mail ?></a>. You may also complain to the Information Regulator of South Africa.</p>
</div></section>
<?php siteClose();

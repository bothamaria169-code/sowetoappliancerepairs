<?php
declare(strict_types=1);

// The owner's PDF guide. It contains the admin password, so it lives in config/
// (never served directly, never in GitHub) and only a signed-in admin can open it.
require_once __DIR__ . '/_shell.php';

$file = BASE_DIR . '/config/guide.pdf';
if (isset($_GET['open']) && is_file($file)) {
    audit('guide_open');
    header('Content-Type: application/pdf');
    header('Content-Disposition: ' . ($_GET['open'] === 'save' ? 'attachment' : 'inline') . '; filename="Soweto-Appliance-Repairs-Guide.pdf"');
    header('Content-Length: ' . filesize($file));
    header('Cache-Control: private, no-store');
    readfile($file);
    exit;
}
adminOpen('guide', 'Guide', 'How to run this website, with screenshots.');
?>
<div class="card" style="max-width:720px"><div class="card__body">
<?php if (is_file($file)): ?>
  <p style="margin-bottom:1rem">The full guide: going live, Google, bookings, articles and fixing problems. <strong>It contains the admin password, so keep it private.</strong></p>
  <div class="form-foot"><a class="btn btn-primary" href="?open=view" target="_blank"><i class="fa-solid fa-file-pdf"></i>Open the PDF guide</a><a class="btn btn-ghost" href="?open=save"><i class="fa-solid fa-download"></i>Download</a></div>
  <p class="hint" style="margin-top:1rem">Made <?= date('j F Y', (int) filemtime($file)) ?>, <?= round(filesize($file) / 1048576, 1) ?> MB.</p>
<?php else: ?>
  <p>The guide is not on this server yet. 🟧 cPanel → <strong>File Manager</strong> → <strong>public_html/config</strong> → <strong>Upload</strong> → <strong>guide.pdf</strong> (from the <strong>config</strong> folder on your PC).</p>
<?php endif; ?>
</div></div>
<?php adminClose();

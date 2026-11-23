<?php
// Soweto Appliance Repairs secrets — copy to config/secrets.php and fill in.
// NEVER commit config/secrets.php. Upload it once to public_html/config with
// cPanel File Manager; git deploys leave it alone.
return [
    // Live database (cPanel → MySQL® Databases)
    'db_host' => 'localhost',
    'db_name' => 'CHANGE_ME',
    'db_user' => 'CHANGE_ME',
    'db_pass' => 'CHANGE_ME',

    // Local XAMPP database (used automatically on localhost)
    'local_db_name' => 'sowetoappliance',
    'local_db_user' => 'root',
    'local_db_pass' => '',

    'app_url' => 'https://sowetoappliancerepairs.co.za',

    // Admin account: created on the first page load. Change it after signing in.
    'admin_email'         => 'admin@sowetoappliancerepairs.co.za',
    'admin_seed_password' => 'CHANGE_ME',

    // Locked out? Put a new password here and load any page once.
    'admin_reset_password' => '',
    'admin_reset_emails'   => [],

    // Mailbox that sends booking emails (cPanel → Email Accounts).
    // smtp_pass blank = the server's built-in mail is used.
    'smtp_host' => 'mail.sowetoappliancerepairs.co.za',
    'smtp_port' => 465,
    'smtp_user' => 'admin@sowetoappliancerepairs.co.za',
    'smtp_pass' => '',

    // Groq AI (console.groq.com → API Keys)
    'groq_api_key' => 'CHANGE_ME',

    // ImageKit (imagekit.io → Developer options → API keys)
    'imagekit_id'           => 'CHANGE_ME',
    'imagekit_url_endpoint' => 'https://ik.imagekit.io/CHANGE_ME',
    'imagekit_public_key'   => 'CHANGE_ME',
    'imagekit_private_key'  => 'CHANGE_ME',

    // Long random strings
    'app_secret'   => 'CHANGE_ME',
    'cron_key'     => 'CHANGE_ME',
    'indexnow_key' => '',
];

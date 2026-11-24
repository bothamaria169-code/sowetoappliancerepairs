<?php
declare(strict_types=1);

/**
 * Email and WhatsApp templates. The texts below are the originals; what the
 * owner changes in Admin → Email templates is kept in email_templates and wins.
 * Words in {curly brackets} are filled in from the booking.
 */

/** @return array<string, string> placeholder => what it is */
function templateWords(): array
{
    return [
        'name' => 'first name', 'full_name' => 'full name', 'ref' => 'reference', 'appliance' => 'fridge, oven…', 'brand' => 'brand or model', 'problem' => 'what is wrong',
        'area' => 'area', 'address' => 'street or suburb', 'requested' => 'the day and time they asked for', 'visit' => 'your visit: day and time', 'date' => 'visit day', 'time' => 'visit time',
        'callout' => 'R' . callout(), 'guarantee' => 'guarantee in months', 'our_phone' => 'your phone', 'customer_phone' => 'their phone', 'customer_email' => 'their email',
        'link' => 'their page to confirm or change', 'review_link' => 'your review page', 'reply' => 'what the customer answered', 'details' => 'a table with all the details',
    ];
}

/** @return array<string, array{name: string, to: string, when: string, subject: string, body: string, wa: string}> */
function templateDefaults(): array
{
    return [
        'visit_confirmed' => ['name' => 'Visit confirmed', 'to' => 'customer', 'when' => 'You choose a date and time and press send',
            'subject' => 'We are coming on {date}, {time} (ref {ref})',
            'body' => "Hi {name},\n\nGood news: our technician will come to look at your {appliance} on:\n\n**{visit}**\n\nAddress: {address}, {area}\n\nThe call-out is {callout}, and it is free if we do the repair. Please make sure somebody is home and that we can reach the appliance.\n\nDoes the time not suit you? Press the button below to choose another one, or WhatsApp {our_phone}.\n\nSoweto Appliance Repairs",
            'wa' => "Hi {name}, this is Soweto Appliance Repairs. We will come to look at your {appliance} on *{visit}* (ref {ref}). Call-out {callout}, free if we do the repair. Please confirm here or reply to this message: {link}"],
        'visit_changed' => ['name' => 'Visit moved to another time', 'to' => 'customer', 'when' => 'You change the date or time',
            'subject' => 'New time for your visit: {date}, {time} (ref {ref})',
            'body' => "Hi {name},\n\nWe had to move your visit. The new time is:\n\n**{visit}**\n\nAddress: {address}, {area}\n\nSorry for the change. If it does not suit you, press the button below to choose another time, or WhatsApp {our_phone}.\n\nSoweto Appliance Repairs",
            'wa' => "Hi {name}, we had to move your {appliance} visit. The new time is *{visit}* (ref {ref}). Sorry for the change. Please confirm here or reply: {link}"],
        'ask_time' => ['name' => 'Ask which time suits', 'to' => 'customer', 'when' => 'You want the customer to choose a time',
            'subject' => 'When can we come for your {appliance}? (ref {ref})',
            'body' => "Hi {name},\n\nThank you for your request about your {appliance}. When can our technician come? Press the button below to choose a day and time, or WhatsApp {our_phone}.\n\nThe call-out is {callout}, and it is free if we do the repair.\n\nSoweto Appliance Repairs",
            'wa' => "Hi {name}, this is Soweto Appliance Repairs about your {appliance} (ref {ref}). Which day and time can we come? You can choose here: {link}"],
        'on_the_way' => ['name' => 'On the way', 'to' => 'customer', 'when' => 'You press send when you leave',
            'subject' => 'We are on the way (ref {ref})',
            'body' => "Hi {name},\n\nOur technician is on the way to you for your {appliance}. Please have your phone with you in case we need directions.\n\n{our_phone}\n\nSoweto Appliance Repairs",
            'wa' => "Hi {name}, we are on the way to you for your {appliance} (ref {ref}). Please keep your phone close."],
        'visit_reminder' => ['name' => 'Reminder before the visit', 'to' => 'customer', 'when' => 'By itself, in the day before the visit',
            'subject' => 'Reminder: we are coming {date}, {time}',
            'body' => "Hi {name},\n\nA quick reminder that our technician is coming to look at your {appliance}:\n\n**{visit}**\n\nAddress: {address}, {area}\n\nIf anything has changed, press the button below or WhatsApp {our_phone}.\n\nSoweto Appliance Repairs",
            'wa' => "Hi {name}, a reminder that we are coming for your {appliance} on *{visit}* (ref {ref}). Is that still fine?"],
        'visit_cancelled' => ['name' => 'Visit cancelled', 'to' => 'customer', 'when' => 'You cancel a visit',
            'subject' => 'Your visit is cancelled (ref {ref})',
            'body' => "Hi {name},\n\nWe are sorry, we cannot come at the time we agreed. We will contact you to arrange another time, or you can WhatsApp {our_phone}.\n\nSoweto Appliance Repairs",
            'wa' => "Hi {name}, we are sorry, we cannot make your {appliance} visit at the time we agreed (ref {ref}). When would suit you instead?"],
        'repair_done' => ['name' => 'Thank you + ask for a review', 'to' => 'customer', 'when' => 'You press send after the repair',
            'subject' => 'Thank you, {name}. Is everything working?',
            'body' => "Hi {name},\n\nThank you for letting us repair your {appliance}. The repair has a {guarantee}-month guarantee. Keep this email, your reference is **{ref}**.\n\nIf anything is not right, WhatsApp {our_phone} and we will sort it out.\n\nCould you take a minute to tell others how it went? It helps us a lot.\n\nSoweto Appliance Repairs",
            'wa' => "Hi {name}, thank you for letting us repair your {appliance}. It has a {guarantee}-month guarantee (ref {ref}). Could you leave us a short review? {review_link}"],
        'not_going_ahead' => ['name' => 'Not going ahead', 'to' => 'customer', 'when' => 'You close a lead politely',
            'subject' => 'About your {appliance} (ref {ref})',
            'body' => "Hi {name},\n\nThank you for contacting us about your {appliance}. We have closed this request for now. If you change your mind, reply to this email or WhatsApp {our_phone} and mention {ref}.\n\nSoweto Appliance Repairs",
            'wa' => "Hi {name}, thank you for asking us about your {appliance}. We have closed request {ref} for now. Message us any time if you want us to come."],
        'booking_received' => ['name' => 'Booking received', 'to' => 'customer', 'when' => 'By itself, when somebody books on the website',
            'subject' => 'We got your booking {ref}',
            'body' => "Hi {name},\n\nThank you, we have your booking. You asked for:\n\n**{requested}**\n\nWe will confirm the time by email, WhatsApp or phone. Need us sooner? Call or WhatsApp {our_phone}.\n\n{details}\n\nSoweto Appliance Repairs",
            'wa' => "Hi {name}, we got your booking {ref} for your {appliance}. You asked for {requested}. We will confirm the time shortly."],
        'booking_admin' => ['name' => 'New booking', 'to' => 'you', 'when' => 'By itself, when somebody books on the website',
            'subject' => 'New booking {ref}: {appliance} in {area}',
            'body' => "**{full_name}** asked for **{requested}**.\n\n{details}\n\nPress the button to choose a time and tell the customer.",
            'wa' => ''],
        'customer_reply' => ['name' => 'Customer answered', 'to' => 'you', 'when' => 'By itself, when a customer confirms, changes or cancels',
            'subject' => '{full_name} ({ref}): {reply}',
            'body' => "**{full_name}** answered about the {appliance} visit:\n\n**{reply}**\n\n{details}",
            'wa' => ''],
    ];
}

/** One template: the owner's version if there is one, otherwise the original. */
function template(string $key): array
{
    static $mine = null;
    if ($mine === null) {
        $mine = [];
        foreach (getDB()->query('SELECT * FROM email_templates') as $r) $mine[$r['tkey']] = $r;
    }
    $t = templateDefaults()[$key];
    if (isset($mine[$key])) foreach (['subject', 'body', 'wa'] as $f) if (trim((string) $mine[$key][$f]) !== '') $t[$f] = (string) $mine[$key][$f];
    return $t;
}

/** @return array<string, string> */
function bookingWords(array $b): array
{
    return [
        'name' => explode(' ', trim($b['full_name']))[0], 'full_name' => $b['full_name'], 'ref' => $b['ref'],
        'appliance' => lcfirst(services()[$b['service']]['short'] ?? 'appliance'), 'brand' => $b['brand'] ?: '-', 'problem' => $b['problem'] ?: '-',
        'area' => $b['area'], 'address' => $b['address'] ?: 'the address you gave us', 'requested' => requestedLabel($b) ?: 'a time that suits you',
        'visit' => visitLabel($b) ?: '(choose a date and time)', 'date' => dayLabel($b['visit_date'] ?? null) ?: '(date)', 'time' => !empty($b['visit_date']) ? visitTime($b) : '(time)',
        'callout' => 'R' . callout(), 'guarantee' => biz('biz_guarantee'), 'our_phone' => biz('biz_phone'), 'customer_phone' => $b['phone'], 'customer_email' => $b['email'] ?: '-',
        'link' => visitUrl($b), 'review_link' => biz('biz_review_url') ?: APP_URL . '/reviews', 'reply' => (string) ($b['reply_note'] ?? ''),
    ];
}

/** Fills in the words; {details} is left for textToEmail(), or becomes lines in plain text. */
function fillText(string $text, array $b, bool $plain = false): string
{
    $words = bookingWords($b);
    if ($plain) {
        $rows = bookingRows($b);
        $words['details'] = implode("\n", array_map(fn($k, $v) => "{$k}: {$v}", array_keys($rows), $rows));
    }
    return preg_replace_callback('/\{([a-z_]+)\}/', fn($m) => (string) ($words[$m[1]] ?? $m[0]), $text);
}

function bookingRows(array $b): array
{
    return array_filter(['Reference' => $b['ref'], 'Name' => $b['full_name'], 'Phone' => $b['phone'], 'Email' => $b['email'], 'Appliance' => services()[$b['service']]['short'] ?? $b['service'], 'Brand / model' => $b['brand'],
        'Area' => $b['area'], 'Address' => $b['address'], 'Asked for' => requestedLabel($b), 'Visit' => visitLabel($b), 'Problem' => $b['problem']], fn($v) => (string) $v !== '');
}

/** Message text → safe email HTML: blank line = paragraph, **bold**, links, {details} table. */
function textToEmail(string $text, array $b): string
{
    $out = '';
    foreach (preg_split('/\R\s*\R/', trim($text)) ?: [] as $p) {
        if (trim($p) === '{details}') { $out .= emailRows(bookingRows($b)) . '<br>'; continue; }
        $h = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', nl2br(e(trim($p))));
        $h = preg_replace('#(https?://[^\s<]+)#', '<a href="$1" style="color:#a84300">$1</a>', $h);
        $out .= '<p style="margin:0 0 14px">' . $h . '</p>';
    }
    return $out;
}

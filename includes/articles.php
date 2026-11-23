<?php
declare(strict_types=1);

/**
 * The article writer.
 *
 * cron.php (and the "Write now" button) take the next topic from the queue,
 * ask Groq for the article, run it through a quality gate, save it and, if
 * auto-publish is on, publish it and tell the search engines (IndexNow).
 * Bodies are stored as simple Markdown (##, ###, -, 1., **bold**) and turned
 * into safe HTML with internal links by articleHtml().
 */
require_once __DIR__ . '/ai.php';

const ARTICLE_MIN_WORDS = 540;

/** Covers the writer may use, by service. */
function coverFor(?string $service, int $seed): string
{
    $pool = $service && isset(services()[$service])
        ? array_merge([services()[$service]['photo']], services()[$service]['photos'])
        : ['board-multimeter', 'stove-multimeter', 'washer-front-loader-opened', 'fridge-compressor'];
    return $pool[$seed % count($pool)];
}

/** URL of a cover: a full URL, an uploaded file or one of the site's own photos. */
function coverUrl(?string $cover, bool $small = false): string
{
    $cover = (string) $cover;
    if ($cover === '') $cover = 'board-multimeter';
    if (preg_match('#^https://#', $cover)) return $cover;
    if (str_starts_with($cover, 'uploads/')) return APP_URL . '/' . $cover;
    return APP_URL . '/assets/img/work/' . $cover . ($small ? '-sm' : '') . '.webp';
}

function postUrl(array $p): string
{
    return APP_URL . '/blog/' . $p['slug'];
}

// ── Writing ───────────────────────────────────────────────────────
function articleSystemPrompt(): string
{
    $c = callout();
    $g = biz('biz_guarantee');
    $areas = implode(', ', array_column(areas(), 0));
    return <<<SYS
You are a senior South African home-appliance technician who writes clear, useful articles for the website of Soweto Appliance Repairs, a mobile repair service.

FACTS YOU MAY USE (nothing else about the company):
- Mobile service: the technician comes to the customer's home. Repairs fridges and freezers, washing machines, stoves, ovens, microwaves and geysers.
- Call-out R{$c}, free if we do the repair. Same-day service. Open 7 days. {$g}-month guarantee on repairs.
- Areas: {$areas}.
- WhatsApp or call {$GLOBALS['SAR_PHONE']}.

WRITING RULES
- South African English, plain words a busy parent understands. Short paragraphs. Second person. Warm, direct, no hype.
- Answer the question in the first two sentences. Then explain.
- 850 to 1150 words in total. 5 to 7 sections. Each section heading says something specific. No numbers in headings. No horizontal rules.
- Use lists for steps and checks. Use concrete details: sounds, smells, where to look, what to note down.
- The VERIFIED NOTES in the brief are your only source for causes, owner checks and warnings. Explain them in your own words and in more detail, but do not add other owner checks, causes, codes, temperatures, time limits or technical claims.
- Owner checks are only: looking, listening, smelling, settings on the front, filters and hoses on washing machines, door seals, plugs and sockets, breakers, levelling, cleaning surfaces, moving food or the appliance. Never tell the reader to open, unscrew or remove any cover or panel, adjust a geyser thermostat, touch or test a valve, element, wire, capacitor, compressor or gas part, take the cover off a microwave (it stays dangerous when unplugged) or run one empty, or to reset a tripping breaker again and again.
- South African stoves and geysers are wired in, not plugged in, so never say to unplug a stove, oven or geyser: say switch it off at the wall isolator or the distribution board.
- Say clearly when to switch off and call a technician.
- About the company, use only the FACTS. No dispatchers, receipts, arrival times, spares carried or processes that are not listed.
- No prices except the R{$c} call-out. If cost comes up, explain what drives it and say the price is given after testing.
- Never claim certificates, years of experience, numbers of customers, awards, reviews, authorised or official status, or that we are the best. Never invent statistics, studies, laws or quotes.
- No emojis. No "in conclusion", "in today's world", "look no further", "game changer". No markdown tables. No links.
- Local detail must be true and general (load-shedding or power cuts, summer storms, low water pressure, dust). Do not invent local events.

Answer ONLY with JSON:
{"title":"","meta_title":"","meta_description":"","excerpt":"","intro":"","sections":[{"h2":"","body":""}],"faqs":[{"q":"","a":""}]}
- title: max 75 characters, natural, contains the main words of the search phrase.
- meta_title: max 58 characters. meta_description: 130 to 155 characters, ends with a reason to click.
- excerpt: one sentence, max 170 characters.
- intro: 60 to 90 words.
- body: Markdown with "- " or "1. " lists, "### " sub-headings if needed and **bold** for key words.
- faqs: 4 questions real people would ask, each answer 30 to 60 words, not repeating a section word for word.
SYS;
}

/**
 * Writes one article from a queue row. Returns the saved post id or an error.
 *
 * @return array{ok: bool, id?: int, title?: string, error?: string}
 */
function writeArticle(array $topic, ?int $adminId = null): array
{
    if (!aiEnabled()) return ['ok' => false, 'error' => 'Groq is off or the key is missing (Settings → AI).'];
    if (!aiUnderLimit()) return ['ok' => false, 'error' => 'This month\'s AI limit is used up (Settings → AI).'];
    $db = getDB();
    $GLOBALS['SAR_PHONE'] = biz('biz_phone');

    $svc  = services()[$topic['service'] ?? ''] ?? null;
    $area = areas()[$topic['area'] ?? ''] ?? null;
    $brief = "Working title: {$topic['title']}\nSearch phrase to answer: {$topic['keyword']}\n";
    if ($svc)  $brief .= "Appliance: {$svc['name']}. Faults people have: " . implode(', ', $svc['common']) . ".\n";
    $brief .= verifiedNotes($topic);
    if ($area) $brief .= "Area: {$area[0]} ({$area[1]}). Suburbs: " . implode(', ', array_slice($area[3], 0, 10)) . ". Useful note: {$area[5]}\n";
    $codes = array_filter(errorCodes(), fn($c) => stripos($topic['keyword'], $c[0]) !== false && stripos($topic['keyword'], explode(' ', $c[1])[0]) !== false);
    if ($codes) $brief .= "Verified codes (use only these, do not add others):\n" . implode("\n", array_map(fn($c) => "- {$c[2]}: {$c[3]}. {$c[4]}", $codes)) . "\n";
    $recent = $db->query('SELECT title FROM posts ORDER BY id DESC LIMIT 12')->fetchAll(PDO::FETCH_COLUMN);
    if ($recent) $brief .= "Already on the site, so take a different angle: " . implode(' | ', $recent) . "\n";

    $problem = 'no draft';
    $post = null;
    for ($try = 0; $try < 4 && $problem !== ''; $try++) {
        $r = patientGroq(articleSystemPrompt(), $brief . ($try && $problem !== 'no draft' ? "\nYour last draft was rejected. Fix this and write the full article again: {$problem}" : ''), 2900);
        logAi($adminId, 'article', $r['ok']);
        if (!$r['ok']) { $problem = $r['error'] ?: 'no answer'; continue; }
        [$post, $problem] = checkArticle($r['data'], $topic);
        if ($problem === '') $problem = implode(' | ', editorIssues($post, $brief, $adminId));
    }
    if ($problem !== '') return ['ok' => false, 'error' => $problem];

    $slug = slugify($post['title']);
    $s = $db->prepare('SELECT COUNT(*) FROM posts WHERE slug = ?');
    $s->execute([$slug]);
    if ($s->fetchColumn()) $slug .= '-' . bin2hex(random_bytes(2));
    // Mains-powered heat (stove, oven, microwave, geyser) waits for a human unless the owner says otherwise.
    $mode = get_setting('articles_review', 'risky');
    $publish = $mode === 'none' || ($mode === 'risky' && !in_array($topic['service'], ['stove-repairs', 'oven-repairs', 'microwave-repairs', 'geyser-repairs'], true));
    $n = (int) $db->query('SELECT COUNT(*) FROM posts')->fetchColumn();
    $db->prepare('INSERT INTO posts (slug, title, meta_title, meta_description, excerpt, body, faqs, keyword, service, area, cover, source, is_published, published_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
       ->execute([$slug, $post['title'], $post['meta_title'], $post['meta_description'], $post['excerpt'], $post['body'], json_encode($post['faqs'], JSON_UNESCAPED_UNICODE),
                  $topic['keyword'], $topic['service'] ?: null, $topic['area'] ?: null, coverFor($topic['service'], $n), 'ai', $publish ? 1 : 0, $publish ? date('Y-m-d H:i:s') : null]);
    $id = (int) $db->lastInsertId();
    if ($publish) afterPublish($id);
    return ['ok' => true, 'id' => $id, 'title' => $post['title']];
}

/** The site's own checked knowledge on a topic, for the writer and the editor. */
function verifiedNotes(array $topic): string
{
    $list = faults();
    if (!empty($topic['service'])) $list = array_filter($list, fn($f) => $f['service'] === $topic['service']);
    $words = array_filter(preg_split('/\W+/', mb_strtolower($topic['keyword'] . ' ' . $topic['title'])), fn($w) => mb_strlen($w) > 3);
    uasort($list, function ($x, $y) use ($words) {
        $score = fn($f) => count(array_filter($words, fn($w) => str_contains(mb_strtolower($f['q'] . ' ' . $f['lead']), $w)));
        return $score($y) <=> $score($x);
    });
    $out = "VERIFIED NOTES\n";
    foreach (array_slice($list, 0, empty($topic['service']) ? 3 : 2) as $f) {
        $out .= '* ' . services()[$f['service']]['short'] . ', ' . lcfirst($f['s']) . ': ' . $f['lead'] . ' Causes: ' . implode(' ', array_map(fn($c) => "{$c[0]}: {$c[1]}", $f['causes']))
              . ' Safe owner checks: ' . implode(' ', $f['checks']) . ' Stop: ' . $f['stop'] . ' What the technician does: ' . $f['fix'] . "\n";
    }
    if (!empty($topic['service'])) foreach (services()[$topic['service']]['body'] as [$h, $t]) $out .= "* {$h}: {$t}\n";
    return $out;
}

/** groqJson that waits when the key's per-minute limit is reached. */
function patientGroq(string $system, string $user, int $max): array
{
    for ($i = 0; $i < 4; $i++) {
        $r = groqJson($system, $user, $max);
        if ($r['ok'] || !preg_match('/try again in ([0-9.]+)s|rate limit/i', $r['error'], $w)) return $r;
        sleep((int) min(60, ceil((float) ($w[1] ?? 20)) + 3));
    }
    return $r;
}

/**
 * A second AI reads the draft as a safety and facts editor.
 *
 * @return string[] problems; empty when the article may go out
 */
function editorIssues(array $post, string $brief, ?int $adminId): array
{
    $sys = 'You are the safety and facts editor of a South African appliance repair website. Compare the DRAFT with the VERIFIED NOTES and FACTS. Report ONLY serious problems: '
        . '(1) it tells the owner to open, remove or unscrew a cover or panel, adjust a geyser thermostat, touch or test a valve, element, wiring, compressor or gas part, use a stove or oven that trips, or do a check that could shock, burn or flood; '
        . '(2) a technical statement that is wrong, or an error code, cause or temperature that is not in the notes; '
        . '(3) a company claim that is not in the FACTS (prices, arrival times, certificates, experience, processes); '
        . '(4) steps that do not make sense. Explaining the notes in more words, general cleaning tips and telling people to call us are fine. '
        . 'Answer ONLY with JSON {"issues":["short quote from the draft: what is wrong"]}. Use an empty list when there is nothing serious. At most 6 issues.';
    $facts = 'FACTS: mobile service at the home; fridges, freezers, washing machines, stoves, ovens, microwaves, geysers; call-out R' . callout() . ' free if we repair; same-day service; 7 days; ' . biz('biz_guarantee') . '-month guarantee; price given after testing; phone and WhatsApp ' . biz('biz_phone') . '; Soweto and 19 other Johannesburg areas.';
    $faq = implode("\n", array_map(fn($f) => "Q: {$f['q']} A: {$f['a']}", $post['faqs']));
    $r = patientGroq($sys, "{$facts}\n{$brief}\nDRAFT\n{$post['title']}\n{$post['body']}\n{$faq}", 900);
    logAi($adminId, 'editor', $r['ok']);
    if (!$r['ok']) return ['the editor could not check it: ' . $r['error']];
    return array_slice(array_values(array_filter(array_map(fn($x) => is_string($x) ? mb_substr(trim($x), 0, 220) : '', (array) ($r['data']['issues'] ?? [])))), 0, 6);
}

/**
 * The quality gate. Returns [post, problem]; problem is '' when it passes.
 *
 * @return array{0: array, 1: string}
 */
function checkArticle(array $d, array $topic): array
{
    // AI text is full of special spaces, hyphens and dashes; make them ordinary ones.
    array_walk_recursive($d, function (&$v) {
        if (is_string($v)) $v = str_replace(["\u{00A0}", "\u{202F}", "\u{2009}", "\u{2011}", "\u{2010}", "\u{2013}", ' — ', '—'], [' ', ' ', ' ', '-', '-', '-', ', ', ', '], $v);
        if (is_string($v)) $v = preg_replace('/(qualified|certified|licensed|licenced|professional|experienced) (technician)/i', '$2', $v);
    });
    $clean = fn($v, int $max) => mb_substr(trim(preg_replace('/\s+/u', ' ', (string) $v)), 0, $max);
    $md = trim((string) ($d['intro'] ?? ''));
    $sections = is_array($d['sections'] ?? null) ? $d['sections'] : [];
    foreach ($sections as $sec) {
        if (!is_array($sec)) continue;
        $h = trim(preg_replace('/^[#\s]*(\d+[.)]\s*)?/', '', (string) ($sec['h2'] ?? '')));
        $b = trim((string) ($sec['body'] ?? ''));
        if ($h !== '' && $b !== '') $md .= "\n\n## {$h}\n\n{$b}";
    }
    $md = preg_replace('/^\s*([-*_]\s*){3,}$/m', '', $md);
    $md = preg_replace('/\[([^\]]+)\]\([^)]*\)/', '$1', strip_tags($md));
    // A sentence with an invented price is cut out; the call-out fee stays.
    $price = '/\bR ?(?!' . (int) biz('biz_callout') . '\b)\d[\d ,]*(?![\da-z])/';
    $noPrice = fn(string $t) => implode(' ', array_filter(preg_split('/(?<=[.!?]) +/u', $t), fn($x) => !preg_match($price, $x)));
    $md = implode("\n", array_filter(array_map($noPrice, explode("\n", $md)), fn($l) => !preg_match('/^\s*([-*]|\d+[.)])\s*$/', $l)));
    foreach (['excerpt', 'meta_description'] as $k) $d[$k] = $noPrice((string) ($d[$k] ?? ''));
    if (is_array($d['faqs'] ?? null)) $d['faqs'] = array_filter($d['faqs'], fn($f) => is_array($f) && !preg_match($price, ($f['q'] ?? '') . ' ' . ($f['a'] ?? '')));
    $faqs = [];
    foreach (is_array($d['faqs'] ?? null) ? $d['faqs'] : [] as $f) {
        if (!empty($f['q']) && !empty($f['a'])) $faqs[] = ['q' => $clean(strip_tags((string) $f['q']), 200), 'a' => $clean(strip_tags((string) $f['a']), 700)];
    }
    $post = [
        'title' => $clean($d['title'] ?? '', 110), 'meta_title' => $clean($d['meta_title'] ?? '', 70),
        'meta_description' => excerpt((string) ($d['meta_description'] ?? ''), 157), 'excerpt' => $clean($d['excerpt'] ?? '', 260),
        'body' => $md, 'faqs' => array_slice($faqs, 0, 5),
    ];
    $all = implode(' ', [$post['title'], $post['meta_description'], $post['excerpt'], $md, json_encode($faqs, JSON_UNESCAPED_UNICODE)]);
    $words = str_word_count($md);

    if ($post['title'] === '' || $post['meta_description'] === '') return [$post, 'title or meta description is missing'];
    if ($words < ARTICLE_MIN_WORDS) return [$post, "only {$words} words; write at least 900"];
    if (substr_count($md, "\n## ") < 4) return [$post, 'fewer than 4 sections'];
    if (count($faqs) < 3) return [$post, 'fewer than 3 FAQs'];
    $banned = [
        '/\b(certified|accredited|authori[sz]ed (technician|dealer|service|agent)s?|licen[sc]ed|qualified technicians?|award[- ]winning|registered (technician|plumber|electrician)s?)\b/i' => 'claims certificates or official status',
        '/\b(\d+|ten|twenty|fifteen)\+? years?\b[^.]{0,30}\bexperience\b/i' => 'claims years of experience',
        '/\b(the best|number one|no\. ?1|#1|leading|most trusted)\b[^.]{0,40}\b(repair|technician|service|company)/i' => 'says we are the best',
        '/\b(hundred|thousand)s? (of )?rands?\b/i' => 'guesses a repair price',
        '/\b(first two sentences|the (verified )?notes\b|as an AI|this brief)/i' => 'talks about its own instructions',
        '/\b(studies show|according to a (study|survey)|research shows|statistics)\b/i' => 'invents studies or statistics',
        '/\b(discharge|short)\b[^.]{0,30}\bcapacitor/i' => 'gives unsafe instructions',
        '/\b(set|turn|adjust|locate|check)\b[^.]{0,30}\bthermostat (dial|setting|screw)/i' => 'gives unsafe instructions',
        '/\b(lift|pull|open|test|press)\b[^.]{0,40}\b(relief|safety|pressure|T-?P)\b[^.]{0,15}\b(valve|lever)/i' => 'gives unsafe instructions',
        '/\bunplug\b[^.]{0,25}\b(stove|oven|hob|geyser)\b/i' => 'gives unsafe instructions',
        '/\b(remove|open|unscrew|take off)\b[^.]{0,40}\b(back panel|rear panel|thermostat cover|geyser cover|element cover)/i' => 'tells the owner to open live parts',
    ];
    // "Never open the back panel" is good advice, so warnings are left out of this check.
    $advice = implode('. ', array_filter(preg_split('/(?<=[.!?\n])\s+/u', $all) ?: [], fn($x) => !preg_match("/\b(never|not|don't|avoid|technician|we )\b/i", $x)));
    foreach ($banned as $rx => $why) {
        $text = str_starts_with($why, 'gives unsafe') || str_starts_with($why, 'tells the owner') ? $advice : $all;
        if (preg_match($rx, $text, $m)) return [$post, $why . ' ("' . excerpt($m[0], 60) . '")'];
    }
    // R250 is fine; R600a and R134a are fridge gases, not prices.
    preg_match_all('/\bR ?(\d{1,3}(?:[ ,]\d{3})+|\d+)(?![\da-z])/', $all, $m);
    foreach ($m[1] as $amount) if ((int) str_replace([' ', ','], '', $amount) !== (int) biz('biz_callout')) return [$post, "mentions R{$amount}; the only price allowed is the R" . callout() . ' call-out fee'];
    // Error codes must be ones we have checked.
    preg_match_all('/\b([A-Z]\d{1,2}|\d{1,2}[A-Z])\b/', $md, $codes);
    $known = array_map('strtoupper', array_column(errorCodes(), 2));
    foreach (array_unique($codes[1]) as $code) if (!in_array(strtoupper($code), $known, true) && !preg_match('/^(\d+[AVWL]|R\d+)$/', $code)) return [$post, "uses the code {$code}, which is not in our checked list"];
    if (preg_match('/\b(\d+)[- ]month (guarantee|warranty)/i', $all, $m) && (int) $m[1] !== (int) biz('biz_guarantee')) return [$post, 'wrong guarantee period'];
    foreach (getDB()->query('SELECT title FROM posts')->fetchAll(PDO::FETCH_COLUMN) as $t) {
        similar_text(mb_strtolower($t), mb_strtolower($post['title']), $pct);
        if ($pct > 86) return [$post, "title is too close to an existing article (\"{$t}\")"];
    }
    return [$post, ''];
}

/** Takes the next queued topic, writes it and updates the queue. */
function writeNextArticle(?int $adminId = null, ?int $topicId = null): array
{
    $db = getDB();
    if ((int) $db->query("SELECT GET_LOCK('sar_article', 1)")->fetchColumn() !== 1) return ['ok' => false, 'error' => 'Another article is being written right now.'];
    try {
        if ($topicId) {
            $s = $db->prepare("SELECT * FROM article_topics WHERE id = ? AND status <> 'done'");
            $s->execute([$topicId]);
        } else {
            $s = $db->query("SELECT * FROM article_topics WHERE status = 'queued' AND tries < 3 ORDER BY sort_order, id LIMIT 1");
        }
        $topic = $s->fetch();
        if (!$topic) return ['ok' => false, 'error' => 'The topic queue is empty.'];
        $r = writeArticle($topic, $adminId);
        if ($r['ok']) {
            $db->prepare("UPDATE article_topics SET status = 'done', post_id = ?, last_error = NULL WHERE id = ?")->execute([$r['id'], $topic['id']]);
        } else {
            $db->prepare("UPDATE article_topics SET tries = tries + 1, last_error = ?, sort_order = sort_order + 30 WHERE id = ?")->execute([mb_substr($r['error'], 0, 400), $topic['id']]);
        }
        return $r + ['topic' => $topic['title']];
    } finally {
        $db->query("SELECT RELEASE_LOCK('sar_article')");
    }
}

/** When fewer than $min topics are left, AI suggests new ones. Returns how many were added. */
function refillTopics(int $min = 6, int $want = 12): int
{
    $db = getDB();
    if ((int) $db->query("SELECT COUNT(*) FROM article_topics WHERE status = 'queued' AND tries < 3")->fetchColumn() >= $min || !aiEnabled() || !aiUnderLimit()) return 0;
    $used = implode(' | ', $db->query('SELECT keyword FROM article_topics ORDER BY id DESC LIMIT 120')->fetchAll(PDO::FETCH_COLUMN));
    $svc  = implode(', ', array_keys(services()));
    $ar   = implode(', ', array_keys(areas()));
    $r = groqJson('You plan articles for a South African appliance repair website. Answer ONLY with JSON {"topics":[{"title":"","keyword":"","service":"","area":""}]}. '
        . 'Each topic is a question or problem a real person in Johannesburg would type into Google about a broken appliance: faults, error codes, costs explained without prices, care, buying advice. '
        . 'No brand comparisons, no DIY electrical or gas work, nothing close to the used list. service must be one of the given slugs or "". area must be one of the given slugs or "" and is used for at most a quarter of the topics.',
        "service slugs: {$svc}\narea slugs: {$ar}\nused: {$used}\nGive {$want} new topics.", 1600);
    logAi(null, 'topics', $r['ok']);
    $s = $db->prepare('INSERT IGNORE INTO article_topics (title, keyword, service, area, sort_order) VALUES (?, ?, ?, ?, ?)');
    $base = (int) $db->query('SELECT COALESCE(MAX(sort_order), 0) FROM article_topics')->fetchColumn();
    $n = 0;
    foreach ((array) ($r['data']['topics'] ?? []) as $i => $t) {
        $title = mb_substr(trim((string) ($t['title'] ?? '')), 0, 200);
        $kw    = mb_strtolower(mb_substr(trim((string) ($t['keyword'] ?? '')), 0, 150));
        if ($title === '' || $kw === '') continue;
        $s->execute([$title, $kw, isset(services()[$t['service'] ?? '']) ? $t['service'] : null, isset(areas()[$t['area'] ?? '']) ? $t['area'] : null, $base + ($i + 1) * 10]);
        $n += $s->rowCount();
    }
    return $n;
}

/** Tells Bing, Yandex and friends (IndexNow) about new or changed pages. Google finds them in the sitemap. */
function indexNow(array $urls): bool
{
    $key = (string) secret('indexnow_key', '');
    if (IS_LOCAL || !preg_match('/^[a-f0-9]{32}$/', $key) || !$urls) return false;
    $ch = curl_init('https://api.indexnow.org/indexnow');
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 12,
        CURLOPT_POSTFIELDS => json_encode(['host' => parse_url(APP_URL, PHP_URL_HOST), 'key' => $key, 'keyLocation' => APP_URL . "/{$key}.txt", 'urlList' => array_values($urls)], JSON_UNESCAPED_SLASHES)]);
    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    set_setting('indexnow_last', date('Y-m-d H:i') . ' · HTTP ' . $code);
    return $code === 200 || $code === 202;
}

function afterPublish(int $postId): void
{
    $s = getDB()->prepare('SELECT * FROM posts WHERE id = ?');
    $s->execute([$postId]);
    if ($p = $s->fetch()) indexNow([postUrl($p), APP_URL . '/blog', APP_URL . '/']);
}

// ── Showing ───────────────────────────────────────────────────────
/** Simple Markdown → safe HTML, with links to our own pages on first mention. */
function articleHtml(string $md, array $skip = []): string
{
    $fmt = fn(string $l) => preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', e($l));
    $out = '';
    $list = '';
    $close = function () use (&$out, &$list) { if ($list !== '') { $out .= "</{$list}>"; $list = ''; } };
    $para = [];
    $flush = function () use (&$out, &$para, $fmt) { if ($para) { $out .= '<p>' . $fmt(implode(' ', $para)) . '</p>'; $para = []; } };
    foreach (preg_split('/\r?\n/', $md) as $line) {
        $l = trim($line);
        if ($l === '') { $flush(); continue; }
        if (preg_match('/^(#{2,4})\s+(.+)$/', $l, $m)) {
            $flush(); $close();
            $h = strlen($m[1]) === 2 ? 'h2' : 'h3';
            $out .= "<{$h}" . ($h === 'h2' ? ' id="' . e(slugify($m[2])) . '"' : '') . '>' . $fmt(rtrim($m[2], '# ')) . "</{$h}>";
        } elseif (preg_match('/^[-*•]\s+(.+)$/u', $l, $m)) {
            $flush();
            if ($list !== 'ul') { $close(); $out .= '<ul>'; $list = 'ul'; }
            $out .= '<li>' . $fmt($m[1]) . '</li>';
        } elseif (preg_match('/^\d+[.)]\s+(.+)$/', $l, $m)) {
            $flush();
            if ($list !== 'ol') { $close(); $out .= '<ol>'; $list = 'ol'; }
            $out .= '<li>' . $fmt($m[1]) . '</li>';
        } else {
            $close();
            $para[] = $l;
        }
    }
    $flush(); $close();
    return linkTerms($out, $skip);
}

/** Links the first mention of each service, fault and area inside paragraphs and list items. */
function linkTerms(string $html, array $skip = []): string
{
    $terms = [
        'fridge repairs' => 'fridge-repairs', 'washing machine repairs' => 'washing-machine-repairs', 'stove repairs' => 'stove-repairs',
        'oven repairs' => 'oven-repairs', 'geyser repairs' => 'geyser-repairs', 'microwave repairs' => 'microwave-repairs', 'error codes' => 'error-codes', 'error code' => 'error-codes', 'fault code' => 'error-codes',
        'call-out' => 'prices', 'not cooling' => 'faults/fridge-not-cooling', 'not draining' => 'faults/washing-machine-not-draining',
        'no hot water' => 'faults/no-hot-water', 'earth leakage' => 'faults/stove-tripping-power', 'door seal' => 'faults/freezer-ice-build-up', 'drain pump' => 'faults/washing-machine-not-draining',
    ];
    foreach (areas() as $slug => $a) if ($a[0] !== 'Johannesburg') $terms[$a[0]] = 'areas/' . $slug;
    $used = array_flip($skip);
    $n = 0;
    foreach ($terms as $term => $path) {
        if (isset($used[$path]) || $n >= 9) continue;
        $html = preg_replace_callback('#(<p>|<li>)(.*?)(</p>|</li>)#su', function ($m) use ($term, $path, &$used, &$n) {
            if (isset($used[$path]) || str_contains($m[2], '<a ')) return $m[0];
            $new = preg_replace('/\b(' . preg_quote(e($term), '/') . ')\b/iu', '<a href="' . APP_URL . '/' . $path . '">$1</a>', $m[2], 1, $c);
            if ($c) { $used[$path] = 1; $n++; return $m[1] . $new . $m[3]; }
            return $m[0];
        }, $html);
    }
    return $html;
}

function readMinutes(string $md): int
{
    return max(2, (int) round(str_word_count($md) / 210));
}

/** @return array<int, array> */
function latestPosts(int $n, ?string $service = null, int $not = 0): array
{
    $sql = 'SELECT * FROM posts WHERE is_published = 1 AND id <> ?' . ($service ? ' ORDER BY (service = ?) DESC, published_at DESC' : ' ORDER BY published_at DESC') . ' LIMIT ' . $n;
    $s = getDB()->prepare($sql);
    $s->execute($service ? [$not, $service] : [$not]);
    return $s->fetchAll();
}

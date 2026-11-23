"""
Builds the owner's PDF guide (SWIFTDNA section 17).

    python docs/build-guide.py            screenshots + PDF
    python docs/build-guide.py --pdf      PDF only, with the screenshots already taken

Needs: the site running on http://localhost:8790, Google Chrome, and
`pip install playwright`. The admin password is read from config/secrets.php
when the PDF is made. It is never written in this file or in docs/guide/.

Output: _private/Soweto-Appliance-Repairs-Guide.pdf and config/guide.pdf
(both git-ignored, because they contain the password).
Drop real cPanel or Google screenshots in docs/guide/ with the names used in
shot('...') below and they appear in the next build.
"""
import base64, html, json, re, shutil, subprocess, sys
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parent.parent
SHOTS = ROOT / 'docs' / 'guide'
SHOTS.mkdir(exist_ok=True)
BASE = 'http://localhost:8790'
NAME = 'Soweto Appliance Repairs'
SITE = 'sowetoappliancerepairs.co.za'
REPO = 'https://github.com/bothamaria169-code/sowetoappliancerepairs.git'
MYSQL = r'C:\xampp\mysql\bin\mysql.exe'
OUT = ROOT / '_private' / 'Soweto-Appliance-Repairs-Guide.pdf'

secrets = (ROOT / 'config' / 'secrets.php').read_text(encoding='utf-8')


def secret(key):
    m = re.search(r"'" + key + r"'\s*=>\s*'([^']*)'", secrets)
    return m.group(1) if m else ''


OPEN_SECRETS = f'Open <b>{ROOT / "config" / "secrets.php"}</b> in Notepad.'
EMAIL, PASSWORD = secret('admin_email'), secret('admin_seed_password')


def sql(q):
    return subprocess.run([MYSQL, '-uroot', '-N', 'sowetoappliance', '-e', q], capture_output=True, text=True, check=True).stdout.strip()


# ── Screenshots ─────────────────────────────────────────────────────
def take_shots():
    before = sql("SELECT v FROM app_settings WHERE k='scheduler_heartbeat'")
    # Example data, only so the admin screens are not empty. Removed again below.
    sql("""INSERT INTO bookings (ref,full_name,phone,service,area,address,brand,problem,when_wanted,status,source_page,referrer,created_at) VALUES
      ('EXAMPLE1','Example Customer','0601234567','fridge-repairs','Soweto','Diepkloof','Samsung','Fridge is warm but the freezer is cold. It started yesterday.','Today if possible','new','/faults/fridge-not-cooling','https://www.google.com/',NOW()),
      ('EXAMPLE2','Example Landlord','0601234567','geyser-repairs','Roodepoort','Wilgeheuwel','','No hot water since this morning.','Tomorrow','booked','/areas/roodepoort','Direct',NOW()-INTERVAL 1 DAY),
      ('EXAMPLE3','Example Shop','0601234567','washing-machine-repairs','Lenasia','Extension 3','LG','Shows OE and will not drain.','This week','done','/error-codes','https://www.google.com/',NOW()-INTERVAL 3 DAY)""")
    sql("INSERT INTO reviews (full_name,area,service,stars,body) VALUES ('Example Customer','Soweto','fridge-repairs',5,'EXAMPLE review, waiting for you to approve it.')")
    sql("REPLACE INTO app_settings (k,v) VALUES ('scheduler_heartbeat', UNIX_TIMESTAMP())")
    hits = ','.join(f"(CURDATE()-INTERVAL {d} DAY,'{p}','{o}',{n})" for d in range(0, 30) for p, o, n in (('/', 'Google', 6 + (d * 7) % 9), ('/faults/fridge-not-cooling', 'Google', 3 + (d * 5) % 7), ('/areas/soweto', 'Direct', 2 + (d * 3) % 4), ('/error-codes', 'Bing', 1 + d % 3)))
    sql('CREATE TABLE IF NOT EXISTS zz_hits_keep AS SELECT * FROM page_hits; TRUNCATE page_hits; INSERT INTO page_hits (day,path,origin,n) VALUES ' + hits)
    try:
        with sync_playwright() as p:
            b = p.chromium.launch(channel='chrome')
            pc = b.new_context(viewport={'width': 1360, 'height': 850}, color_scheme='light', reduced_motion='reduce')
            ph = b.new_context(viewport={'width': 390, 'height': 844}, device_scale_factor=2, is_mobile=True, color_scheme='light', reduced_motion='reduce')

            def snap(ctx, name, path, full=False, js=None, clip_h=None):
                pg = ctx.new_page()
                pg.goto(BASE + path, wait_until='networkidle')
                pg.evaluate("([a,b])=>document.querySelectorAll('code').forEach(c=>c.textContent=c.textContent.replace(a,b))", [BASE, 'https://' + SITE])
                if js:
                    pg.evaluate(js)
                pg.wait_for_timeout(700)
                pg.screenshot(path=str(SHOTS / f'{name}.jpg'), type='jpeg', quality=82, full_page=full)
                pg.close()

            snap(pc, '01-home', '/')
            snap(pc, '02-fault-finder', '/', js="document.querySelector('.finder').scrollIntoView();window.scrollBy(0,-40)")
            snap(pc, '03-callout', '/', js="document.querySelector('.price').scrollIntoView();window.scrollBy(0,-90)")
            snap(pc, '04-repair-page', '/fridge-repairs')
            snap(pc, '05-area-page', '/areas/soweto')
            snap(pc, '06-fault-guide', '/faults/washing-machine-not-draining')
            snap(pc, '07-error-codes', '/error-codes')
            snap(pc, '08-article', '/blog')
            snap(pc, '09-booking', '/book?service=fridge-repairs')
            snap(ph, '10-phone-home', '/')
            snap(ph, '11-phone-microwave', '/microwave-repairs')
            snap(ph, '12-phone-book', '/book')
            snap(pc, '20-login', '/login')

            pg = pc.new_page()
            pg.goto(BASE + '/login')
            pg.fill('#email', EMAIL)
            pg.fill('#password', PASSWORD)
            pg.click('button[type=submit]')
            pg.wait_for_url('**/admin/**')
            pg.close()
            bid = sql("SELECT id FROM bookings WHERE ref='EXAMPLE1'")
            pid = sql('SELECT id FROM posts WHERE is_published=1 ORDER BY id LIMIT 1')
            for name, path in [('21-dashboard', '/admin/index.php'), ('22-bookings', '/admin/bookings.php'), ('23-booking', f'/admin/bookings.php?id={bid}'), ('24-articles', '/admin/articles.php'), ('25-article-edit', f'/admin/articles.php?id={pid}'),
                               ('26-topics', '/admin/topics.php'), ('27-seo', '/admin/seo.php'), ('28-photos', '/admin/media.php'), ('29-reviews', '/admin/reviews.php'), ('30-settings-business', '/admin/settings.php?tab=business'),
                               ('31-settings-writer', '/admin/settings.php?tab=articles'), ('32-settings-email', '/admin/settings.php?tab=email'), ('33-settings-google', '/admin/settings.php?tab=tracking'), ('34-social', '/admin/social.php'), ('35-account', '/admin/account.php')]:
                snap(pc, name, path)
            b.close()
    finally:
        sql("DELETE FROM bookings WHERE ref LIKE 'EXAMPLE%'; DELETE FROM reviews WHERE body LIKE 'EXAMPLE%'; TRUNCATE page_hits; INSERT INTO page_hits SELECT * FROM zz_hits_keep; DROP TABLE zz_hits_keep")
        sql(f"REPLACE INTO app_settings (k,v) VALUES ('scheduler_heartbeat','{before}')" if before else "DELETE FROM app_settings WHERE k='scheduler_heartbeat'")


# ── PDF ─────────────────────────────────────────────────────────────
e = html.escape
missing = []


def shot(name, caption='', cls=''):
    for ext in ('jpg', 'png'):
        f = SHOTS / f'{name}.{ext}'
        if f.is_file():
            data = base64.b64encode(f.read_bytes()).decode()
            return f'<figure class="{cls}"><img src="data:image/{"jpeg" if ext == "jpg" else "png"};base64,{data}"><figcaption>{caption}</figcaption></figure>'
    missing.append(name)
    return ''


def steps(items):
    return '<ol class="steps">' + ''.join(f'<li>{i}</li>' for i in items) + '</ol>'


CP, AD, AC, PC = ('<span class="b cp">CPANEL</span>', '<span class="b ad">ADMIN</span>', '<span class="b ac">ACCOUNT</span>', '<span class="b pc">YOUR PC</span>')


def test(t):
    return f'<p class="test"><b>Test</b>{t}</p>'


def note(t):
    return f'<p class="note">{t}</p>'


def copy(t):
    return f'<code class="copy">{e(t)}</code>'


def section(title, body, time='', brk=True):
    return f'<section class="{"brk" if brk else ""}"><h2>{title}{f"<small>{time}</small>" if time else ""}</h2>{body}</section>'


def build():
    mark = (ROOT / 'assets/img/mark.svg').read_text(encoding='utf-8')
    hero = base64.b64encode((SHOTS / '01-home.jpg').read_bytes()).decode()
    parts = []
    parts.append(f'''<section class="cover"><div class="brand">{mark}<div>Soweto<br><b>Appliance Repairs</b></div></div>
      <h1>Website guide</h1><p>How to put it live, sign in, take bookings and grow on Google.</p>
      <img src="data:image/jpeg;base64,{hero}"><div class="cfoot"><b>{SITE}</b><span>PRIVATE: this guide contains your admin password</span></div></section>''')

    parts.append(section('Your logins and links', f'''
      <table class="login"><tr><th>Admin sign-in page</th><td>https://{SITE}/login</td></tr>
      <tr><th>Admin email</th><td>{e(EMAIL)}</td></tr><tr><th>Admin password</th><td class="pw">{e(PASSWORD)}</td></tr></table>
      {note('Keep this PDF private. Do not email it or put it in a public folder. If anybody else sees it, change the password: <b>Admin → My account → Change password</b>. This page shows the first password; write a new one here if you change it: ______________________')}
      {shot('20-login', 'The sign-in page. Type the email and password above, then press <b>Sign in</b>.')}
      <table class="t"><tr><th>What</th><th>Where</th></tr>
      <tr><td>Website</td><td>https://{SITE}</td></tr><tr><td>Admin panel</td><td>https://{SITE}/admin</td></tr>
      <tr><td>Forgot password</td><td>https://{SITE}/forgot-password.php (needs email working, section 5)</td></tr>
      <tr><td>WhatsApp and calls</td><td>060 432 2996</td></tr><tr><td>Code</td><td>{REPO[:-4]}</td></tr>
      <tr><td>Database</td><td>{e(secret('db_name'))}</td></tr><tr><td>Database user</td><td>{e(secret('db_user'))} (password: the one you set in cPanel)</td></tr></table>'''))

    parts.append(section('How the website brings you work', f'''
      <div class="flow"><div><b>1</b><h4>Someone searches</h4>"fridge not cooling", "washing machine repairs Roodepoort", "5E error"</div><div><b>2</b><h4>They land on a page</h4>6 repairs, 20 areas, 26 fault guides, error codes and articles</div><div><b>3</b><h4>They tap</h4>WhatsApp, Call or Book, always one tap away</div><div><b>4</b><h4>You get the lead</h4>On WhatsApp, by email and in Admin → Bookings</div></div>
      <div class="two">{shot('02-fault-finder', 'Fault finder: pick an appliance and a symptom.')}{shot('06-fault-guide', 'A fault guide: causes, safe checks, when to stop.')}</div>
      <div class="two">{shot('05-area-page', 'One page for each of your 20 areas.')}{shot('07-error-codes', 'Error codes people search for.')}</div>
      <div class="two">{shot('03-callout', 'Your call-out fee, changed in one place in Settings.')}{shot('08-article', 'Articles that the website writes by itself.')}</div>'''))
    parts.append(section('On a phone', f'''<p>Most customers will see it like this. Call, WhatsApp and Book stay at the bottom of every page.</p>
      <div class="three">{shot('10-phone-home', 'Home')}{shot('11-phone-microwave', 'Repair page')}{shot('12-phone-book', 'Booking form')}</div>'''))

    parts.append(f'<section class="brk divider"><span>Part 1</span><h1>Put it live</h1><p>Do sections 1 to 6 in order. About 35 minutes.</p><p class="keys">{PC} on your computer &nbsp; {CP} your hosting &nbsp; {AD} the admin panel &nbsp; {AC} another website</p></section>')

    parts.append(section('1. Database', f'''{CP}{steps(['Open cPanel → <b>MySQL® Databases</b>.', f'Check that the database <b>{e(secret("db_name"))}</b> and the user <b>{e(secret("db_user"))}</b> are listed.', 'Do not know the password? <b>Current Users</b> → <b>Change Password</b> → <b>Password Generator</b> → copy it → <b>Change Password</b>.', '<b>Add User To Database</b>: choose both → <b>Add</b> → tick <b>ALL PRIVILEGES</b> → <b>Make Changes</b>.'])}
      {shot('cpanel-mysql', 'cPanel → MySQL Databases')}{note('The database must be empty. If an old website used it, create a new one.')}{test('The user shows next to the database under <b>Current Databases</b>.')}
      <h2 class="in">2. Secrets file<small>3 min</small></h2>{PC}{steps([OPEN_SECRETS, 'Replace <b>CHANGE_ME</b> next to <b>db_pass</b> with the database password.', 'Save. The Groq and ImageKit keys are already inside.'])}{test('Press Ctrl+F and look for CHANGE_ME. There must be none left.')}''', '5 min'))

    parts.append(section('3. Put the files on your hosting', f'''{CP}{steps(['cPanel → <b>Git™ Version Control</b> → <b>Create</b>.', 'Leave <b>Clone a Repository</b> on and fill in:'])}
      <table class="t"><tr><th>Field</th><th>Paste exactly</th></tr><tr><td>Clone URL</td><td>{copy(REPO)}</td></tr><tr><td>Repository Path</td><td>{copy('repositories/sowetoappliancerepairs')}</td></tr><tr><td>Repository Name</td><td>{copy('sowetoappliancerepairs')}</td></tr></table>
      {shot('cpanel-create-repository', 'cPanel → Create Repository, with the values for this website. The grey part of the path is your own cPanel name.', 'mid')}
      <ol class="steps" start="3"><li><b>Create</b> → <b>Manage</b> → <b>Pull or Deploy</b> → <b>Update from Remote</b> → <b>Deploy HEAD Commit</b>.</li><li><b>File Manager</b> → <b>public_html/config</b> → <b>Upload</b> → your <b>secrets.php</b>.</li><li>In the same folder upload <b>guide.pdf</b> (from <b>config</b> on your PC). It then opens in Admin → <b>Guide</b>.</li><li><b>Select PHP Version</b> → <b>8.2</b>. Tick curl, exif, gd, mbstring, pdo_mysql.</li></ol>
      {shot('cpanel-deploy', 'cPanel → Pull or Deploy')}{shot('cpanel-file-manager', 'cPanel → File Manager → public_html/config')}
      {note('Old <b>index.html</b> in public_html? Rename or delete it, or it shows instead of the new site.')}{test(f'Open {SITE}, then {SITE}/fridge-repairs and {SITE}/login.')}''', '10 min'))

    parts.append(section('4. The padlock (https)', f'''{CP}{steps(['<b>SSL/TLS Status</b> → tick your domains → <b>Run AutoSSL</b>.', f'Wait until https://{SITE} opens with a padlock and no warning.', '<b>File Manager</b> → <b>public_html</b> → <b>+ File</b> → name it <b>https-on.txt</b> → <b>Create New File</b>. Leave it empty.'])}{shot('cpanel-ssl', 'cPanel → SSL/TLS Status')}{note('That empty file switches https on. If anything goes wrong, delete it.')}{test(f'http://www.{SITE} jumps to https://{SITE}.')}
      <h2 class="in">5. Email<small>5 min</small></h2>{CP}{steps([f'<b>Email Accounts</b> → check that <b>{e(EMAIL)}</b> exists, or <b>Create</b> it.', '<b>Email Deliverability</b> → <b>Repair</b> if it is not Valid.'])}{AD}<ol class="steps" start="3"><li><b>Settings → Email</b> → type the <b>Mailbox password</b> → <b>Save</b>.</li></ol>{shot('32-settings-email', 'Admin → Settings → Email')}{test('Type your Gmail address in <b>Send the test to</b> → <b>Send test email</b>.')}''', '5 min'))

    parts.append(section('6. Cron: articles write themselves', f'''{CP}{steps(['<b>Cron Jobs</b> → <b>Common Settings</b> → <b>Once Per Ten Minutes</b>.', 'Paste this in <b>Command</b>, then <b>Add New Cron Job</b>:'])}{copy('/usr/local/bin/php $HOME/public_html/cron.php >> $HOME/sar-cron.log 2>&1')}{shot('cpanel-cron', 'cPanel → Cron Jobs')}{test('After ten minutes the admin dashboard shows a green tick next to <b>Is everything still running?</b>')}{shot('21-dashboard', 'Admin → Dashboard (example data). The bar at the top tells you whether cron is running.')}''', '3 min'))

    parts.append(f'<section class="brk divider"><span>Part 2</span><h1>Get found on Google</h1><p>Google Search Console and Google Business Profile matter most.</p></section>')
    parts.append(section('7. Google Search Console', f'''{AC}{steps([f'Open <b>search.google.com/search-console</b> → <b>Add property</b> → <b>URL prefix</b> → {copy("https://" + SITE)} → <b>Continue</b>.', '<b>HTML tag</b> → <b>Copy</b>.'])}{AD}<ol class="steps" start="3"><li><b>Settings → Google &amp; Meta</b> → <b>Google Search Console code</b> → paste → <b>Save</b>.</li></ol>{AC}<ol class="steps" start="4"><li>Back in Search Console → <b>Verify</b>.</li><li><b>Sitemaps</b> → {copy('sitemap.xml')} → <b>Submit</b>.</li><li>Paste your home page and main repair pages in the top bar → <b>Request Indexing</b>.</li></ol>{shot('33-settings-google', 'Admin → Settings → Google & Meta')}{shot('google-search-console', 'Google Search Console')}{test('Sitemaps shows <b>Success</b>. It can take a day.')}''', '10 min'))
    parts.append(section('8. Google Business Profile', f'''{AC}{steps(['Open <b>business.google.com</b> → <b>Add your business</b>.', f'Name: <b>{NAME}</b>, the same as on the website.', 'Category: <b>Appliance repair service</b>.', 'Location customers can visit? <b>No</b>. Add your 20 service areas.', f'Phone <b>060 432 2996</b>, website https://{SITE}.', 'Verify, then add photos and services.', '<b>Ask for reviews</b> → copy the link.'])}{AD}<ol class="steps" start="8"><li><b>Settings → Business</b> → paste the <b>Google Business Profile link</b> and <b>Google review link</b> → <b>Save</b>.</li></ol>{shot('30-settings-business', 'Admin → Settings → Business. Change the call-out fee, guarantee, phone and links here.')}{note('Use one business name everywhere. Your banners use three, and Google treats them as three businesses.')}{test('Search your business name on your phone after verification.')}''', '20 min'))
    parts.append(section('9. Bing, AI search and Analytics', f'''{AC}{steps(['<b>bing.com/webmasters</b> → <b>Import from Google Search Console</b>.'])}{AD}<ol class="steps" start="2"><li><b>SEO &amp; visits</b> → <b>Send all pages to Bing</b>.</li></ol>{AC}<ol class="steps" start="3"><li>Optional: <b>analytics.google.com</b> → create a Web property → copy <b>G-…</b> → Admin → <b>Settings → Google &amp; Meta</b>.</li></ol>{shot('27-seo', 'Admin → SEO & visits (example data): where visitors come from and which pages win them.')}{test('A green message says how many pages were sent.')}''', '10 min'))
    parts.append(section('10. Facebook and Instagram (optional)', f'''{AC}{steps(['<b>developers.facebook.com</b> → <b>My Apps</b> → <b>Create App</b> → add <b>Facebook Login</b>.', f'<b>Facebook Login → Settings → Valid OAuth Redirect URIs</b>: {copy(f"https://{SITE}/admin/meta-connect.php")}', '<b>App settings → Basic</b> → copy <b>App ID</b> and <b>App secret</b>.'])}{AD}<ol class="steps" start="4"><li><b>Social</b> → paste both → <b>Save</b> → <b>Connect with Facebook</b>.</li></ol>{shot('34-social', 'Admin → Social')}{test('<b>Social → Share now</b>.')}''', '20 min'))

    parts.append(f'<section class="brk divider"><span>Part 3</span><h1>Run it every day</h1><p>Ten minutes a day is enough.</p></section>')
    parts.append(section('Bookings', f'''<p>Every request from the website lands here, newest first. You also get an email.</p>{shot('22-bookings', 'Admin → Bookings (example data)')}{steps(['Click the customer name.', 'Press <b>WhatsApp</b> or <b>Call</b> at the top. The message is already typed.', 'Set the <b>Status</b>, the <b>Price quoted</b> and your <b>Notes</b> → <b>Save</b>.'])}{shot('23-booking', 'One booking. "Booked from page" and "First came from" show what brought the customer.')}'''))
    parts.append(section('Articles', f'''<p>The website writes on Tuesday and Friday from 07:00. Fridge, washing machine and general articles go live by themselves. <b>Stove, oven, microwave and geyser articles wait as drafts</b>, because wrong advice there can hurt someone.</p>{shot('24-articles', 'Admin → Articles')}{steps(['Open a <b>Draft</b> and read it.', 'Change anything that is not right → <b>Save article</b>.', 'Press <b>Publish</b> in the list.', 'In a hurry? <b>Write the next article</b> writes one now (up to a few minutes).'])}{shot('25-article-edit', 'Editing an article. "Google title" and "Google description" are what people see in the search results.')}'''))
    parts.append(section('Topic queue', f'''<p>What the website will write about next. Type what customers ask you on WhatsApp: those are the best topics.</p>{shot('26-topics', 'Admin → Topic queue. Add your own, or press Suggest 12 topics.')}<h2 class="in">When it writes</h2>{shot('31-settings-writer', 'Admin → Settings → Article writer')}'''))
    parts.append(section('Photos and reviews', f'''<p>Add two photos from each week's jobs. Hidden location data is removed from every photo.</p>{shot('28-photos', 'Admin → Photos')}<p>Send customers to <b>{SITE}/reviews</b>. Approve only real ones.</p>{shot('29-reviews', 'Admin → Reviews (example)')}'''))
    parts.append(section('Change your password', f'''{steps(['<b>Admin → My account</b>.', 'Type the current password, then the new one twice → <b>Change password</b>.', 'Write the new password on page 2 of this guide.'])}{shot('35-account', 'Admin → My account')}{note("Locked out? On your PC put a new password next to <b>admin_reset_password</b> in secrets.php, upload it to public_html/config and open any page once.")}'''))

    trouble = [('"We\'ll be right back"', 'Read the grey box on that page. Usually <b>db_pass</b> in secrets.php is wrong, or the user is not added to the database.'), ('"Setting up the database failed"', 'The database is not empty. Make a new one.'), ('Only the home page works', '<b>.htaccess</b> is missing. Deploy again.'), ('"This site needs PHP 8.1"', 'cPanel → <b>Select PHP Version</b> → 8.2.'), ('Red bar on the dashboard', 'The cron job is missing (section 6).'), ('No email for a booking', '<b>Settings → Email → Send test email</b>. The booking itself is safe in Admin → Bookings.'), ('An article was not written', '<b>Topic queue</b>: the reason is under the topic. Press <b>Write now</b>.'), ('A change on GitHub does not show', 'Press <b>Update from Remote</b> first, then <b>Deploy HEAD Commit</b>.')]
    parts.append(section('If something goes wrong', '<table class="t"><tr><th>You see</th><th>Do this</th></tr>' + ''.join(f'<tr><td>{a}</td><td>{b}</td></tr>' for a, b in trouble) + f'''</table>
      <h2 class="in">Every week</h2><ul class="tick"><li>Call back every booking marked <b>New</b>.</li><li>Read the drafts and press <b>Publish</b>.</li><li>Add two photos from the week's jobs.</li><li>Send your review link to each happy customer.</li><li>Look at <b>SEO &amp; visits</b> and add similar topics to the queue.</li></ul>'''))

    css = '''@page{size:A4;margin:16mm 15mm 17mm}*{box-sizing:border-box}body{font:10.4pt/1.5 "Plus Jakarta Sans",Arial,sans-serif;color:#0d1a33;margin:0;-webkit-print-color-adjust:exact}
    h1,h2,h4{font-family:"Bricolage Grotesque",Arial,sans-serif;font-stretch:84%;line-height:1.08;letter-spacing:-.015em;margin:0}section.brk{break-before:page}.divider+section.brk{break-before:auto}
    h2{font-size:21pt;font-weight:800;padding-bottom:6px;border-bottom:4px solid #ff7a1a;margin-bottom:12px;display:flex;justify-content:space-between;align-items:baseline;break-after:avoid}h2.in{font-size:16pt;margin-top:20px}h2 small{font:700 9.5pt "Plus Jakarta Sans";color:#a84300}
    .cover{height:262mm;background:#0a1c3d;color:#fff;margin:-2mm 0 0;border-radius:18px;padding:34px;display:flex;flex-direction:column}.brand{display:flex;gap:12px;align-items:center;font:800 15pt/1.05 "Bricolage Grotesque"}.brand svg{width:50px}.brand b{color:#ff7a1a;font-size:11pt}.cover h1{font-size:56pt;font-weight:800;margin:44px 0 10px}.cover p{font-size:14pt;color:#cbd7ec;margin:0 0 30px;max-width:120mm}.cover img{width:100%;border-radius:12px;border:5px solid #ff7a1a}.cfoot{margin-top:auto;display:flex;justify-content:space-between;align-items:center;font-size:10pt}.cfoot span{background:#ff7a1a;color:#0a1c3d;font-weight:800;padding:7px 12px;border-radius:8px}
    .divider{background:#12295a;color:#fff;border-radius:16px;padding:22px 26px;margin-bottom:22px}.divider>span{color:#ff9340;font-weight:800;font-size:11pt}.divider h1{font-size:28pt;font-weight:800;margin:3px 0 6px}.divider p{font-size:11pt;color:#cbd7ec;margin:0}.keys{margin-top:12px!important;font-size:9pt!important}
    table{width:100%;border-collapse:collapse;margin:8px 0 14px;break-inside:avoid}.t th,.t td{text-align:left;padding:7px 9px;border-bottom:1px solid #dde3ee;vertical-align:top;font-size:9.7pt;overflow-wrap:anywhere}.t th{background:#f2f4f8}.t td:first-child{width:34%;font-weight:600}
    .login{background:#fff1e6;border-radius:14px;overflow:hidden}.login th,.login td{text-align:left;padding:11px 16px;border-bottom:1px solid #ffd9bd;font-size:12pt}.login th{width:38%}.pw{font:800 17pt Consolas,monospace!important;letter-spacing:.03em}
    figure{margin:9px 0 12px;break-inside:avoid}figure img{width:84%;border:1px solid #c2ccdd;border-radius:9px;display:block}figcaption{font-size:8.8pt;color:#465573;margin-top:5px}figure.mid img{width:62%}.two figure img,.three figure img{width:100%}.two{display:grid;grid-template-columns:1fr 1fr;gap:12px}.three{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
    ol.steps{margin:7px 0 10px;padding-left:22px}ol.steps li{margin-bottom:4px;padding-left:3px}ol.steps li::marker{font-weight:800;color:#a84300}
    .b{display:inline-block;font-size:8pt;font-weight:800;padding:3px 9px;border-radius:6px;color:#fff;margin-top:4px}.cp{background:#c24e07}.ad{background:#0f7a3b}.ac{background:#1d4fb8}.pc{background:#465573}
    .note,.test{border-radius:9px;padding:9px 13px;margin:8px 0;break-inside:avoid}.note{background:#fff1e6;border-left:4px solid #ff7a1a}.test{background:#e7f7ed;border-left:4px solid #0f7a3b}.test b:first-child{color:#0f7a3b;margin-right:8px}
    code.copy{font:9.2pt Consolas,monospace;background:#f2f4f8;border:1px dashed #9aa8c2;border-radius:6px;padding:3px 7px;overflow-wrap:anywhere}.flow{display:grid;grid-template-columns:repeat(4,1fr);gap:9px;margin-bottom:12px}.flow div{background:#f2f4f8;border-radius:12px;padding:12px;font-size:9.2pt}.flow b{background:#ff7a1a;color:#0a1c3d;width:24px;height:24px;border-radius:7px;display:inline-grid;place-items:center}.flow h4{margin:7px 0 3px;font-size:12pt}
    ul.tick{list-style:none;padding:0;margin:6px 0}ul.tick li{padding:5px 0 5px 27px;position:relative}ul.tick li:before{content:"";position:absolute;left:0;top:8px;width:14px;height:14px;border:2px solid #ff7a1a;border-radius:4px}'''
    fonts = '<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wdth,wght@12..96,75..100,400..800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">'
    doc = f'<!doctype html><html lang="en-ZA"><head><meta charset="utf-8"><title>{NAME}: website guide</title>{fonts}<style>{css}</style></head><body>{"".join(parts)}</body></html>'
    OUT.parent.mkdir(exist_ok=True)
    with sync_playwright() as p:
        b = p.chromium.launch(channel='chrome')
        pg = b.new_page()
        pg.set_content(doc, wait_until='networkidle')
        pg.evaluate('document.fonts.ready')
        pg.pdf(path=str(OUT), format='A4', print_background=True, prefer_css_page_size=True, display_header_footer=True, header_template='<span></span>',
               footer_template=f'<div style="font:8px Arial;color:#66748f;width:100%;padding:0 15mm;display:flex;justify-content:space-between"><span>{NAME} · private</span><span><span class="pageNumber"></span> / <span class="totalPages"></span></span></div>')
        b.close()
    shutil.copy(OUT, ROOT / 'config' / 'guide.pdf')
    print('wrote', OUT, round(OUT.stat().st_size / 1024), 'KB')
    if missing:
        print('Screenshots still to add in docs/guide/ (png or jpg):', ', '.join(missing))


if '--pdf' not in sys.argv:
    take_shots()
build()

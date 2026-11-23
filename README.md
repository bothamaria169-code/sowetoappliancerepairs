# Soweto Appliance Repairs — sowetoappliancerepairs.co.za

A lead-generating website for a mobile appliance repair service. It takes bookings, answers questions, and writes its own articles to bring in free Google traffic.

**Stack:** PHP 8.1+ · MySQL/MariaDB (PDO) · PHPMailer · Groq AI · ImageKit · plain CSS/JS (SWIFTDNA) · cPanel Git deploy

| Area | Where |
|---|---|
| Home page with the fault tester | `index.php` |
| 5 repair pages (`/fridge-repairs` …) | `service.php` |
| 20 area pages (`/areas/soweto` …) | `area.php`, list in `areas.php` |
| 22 fault guides (`/faults/…`) | `fault.php`, list in `faults.php`, text in `includes/faults.php` |
| Error code lookup | `error-codes.php` |
| Booking form | `book.php` |
| Prices, Our work, Reviews, About, Contact | `prices.php`, `our-work.php`, `reviews.php`, `about.php`, `contact.php` |
| Articles | `blog.php`, `post.php` |
| Article writer | `includes/articles.php` (quality gate + safety editor) |
| Website chat | `ajax/chat.php` |
| Services, areas, error codes, FAQ text | `includes/content.php` |
| Design | `assets/css/site.css` (public), `style.css` + `admin.css` (admin) |
| Admin | `/login` → `/admin` |
| Scheduled jobs | `cron.php` |
| Database tables | `database/migrate.php` (creates everything on the first page load) |
| Sitemap, RSS, robots, llms.txt, IndexNow | `sitemap.php`, `feed.php`, `textfile.php` |
| Deploy | `.cpanel.yml` → `public_html` |

## How the traffic engine works

1. The **Topic queue** holds 40 real searches to start with. When fewer than 6 are left, the AI suggests more.
2. On **Tuesday and Friday from 07:00**, cron takes the next topic.
3. The AI writes from the site's own fault guides. A **quality gate** rejects invented prices, unknown error codes, unsafe advice, certificate claims, thin text and near-duplicates. A **second AI** then reads it as a safety editor.
4. Fridge, washing machine and general articles go live by themselves. **Stove, oven and geyser articles wait as drafts** until you press **Publish**, because mains electricity is involved. Change this in 🟩 **Settings → Article writer**.
5. New pages go into the sitemap and are sent to Bing through IndexNow. If Facebook is connected, they are shared there.

## Run it locally (XAMPP)

1. Start **MySQL** (and **Apache** if you want) in the XAMPP Control Panel.
2. Create a database called `sowetoappliance` in phpMyAdmin.
3. Keep `config/secrets.php` in place (it is not in GitHub).
4. Open http://localhost/sowetoappliancerepairs. Without Apache: `C:\xampp\php\php.exe -S localhost:8790 router.php` and open http://localhost:8790.
5. Sign in at `/login` with `admin@sowetoappliancerepairs.co.za` and the `admin_seed_password` in `config/secrets.php`.

> Emails do not send from XAMPP. They still show in **Admin → Sent log** as failed.
> Write one article by hand: **Admin → Articles → Write the next article**, or `php cron.php`.

## Deploy

🟧 cPanel → **Git™ Version Control** → **Manage** → **Pull or Deploy** → **Update from Remote** → **Deploy HEAD Commit**. Deploys never touch `config/secrets.php` or `uploads/media`.

First time? Follow [docs/SETUP-GUIDE.md](docs/SETUP-GUIDE.md).

## Troubleshooting

| You see | Do this |
|---|---|
| "We'll be right back" | Read the grey box on that page. Usually `db_pass` in `config/secrets.php` is wrong, or the user is not added to the database. |
| "Setting up the database failed" | Use a new, empty database. |
| Only the home page works, others are 404 | `.htaccess` is missing. Deploy again. |
| "This site needs PHP 8.1" | 🟧 **Select PHP Version** → **8.2**. |
| Red bar "Is everything still running?" | The cron job is not set up (guide, section 6). |
| "Could not write" in Topics | The message under the topic says why. Press **Write now** to try again. |
| A booking came in but no email | 🟩 **Settings → Email → Send test email**. |

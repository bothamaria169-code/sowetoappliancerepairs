# Soweto Appliance Repairs — setup guide

Follow the sections in order. Each one ends with a test.

🟦 **ACCOUNT** = another website (GitHub, Google, Groq, Meta) · 🟩 **ADMIN** = `sowetoappliancerepairs.co.za/admin` · 🟧 **CPANEL** = your hosting

## Every URL you'll paste

| Where | Field | Paste exactly |
|---|---|---|
| cPanel → Git™ Version Control | Clone URL | `https://github.com/bothamaria169-code/sowetoappliancerepairs.git` |
| cPanel → Git™ Version Control | Repository Path | `repositories/sowetoappliancerepairs` |
| cPanel → Git™ Version Control | Repository Name | `sowetoappliancerepairs` |
| cPanel → Cron Jobs | Command | `/usr/local/bin/php $HOME/public_html/cron.php >> $HOME/sar-cron.log 2>&1` |
| Google Search Console | Sitemap | `sitemap.xml` |
| Meta app → Facebook Login → Settings | Valid OAuth Redirect URIs | `https://sowetoappliancerepairs.co.za/admin/meta-connect.php` |
| Your browser | Admin sign-in | `https://sowetoappliancerepairs.co.za/login` |

---

## 1. Database (5 min) 🟧 CPANEL

1. cPanel → **MySQL® Databases**.
2. Check that the database `bdtryrjx_database` and the user `bdtryrjx_admin` are listed.
3. Under **Current Users** → `bdtryrjx_admin` → **Change Password** → **Password Generator** → copy it → **Change Password**. (Skip this if you know the password.)
4. Under **Add User To Database**: pick `bdtryrjx_admin` and `bdtryrjx_database` → **Add** → tick **ALL PRIVILEGES** → **Make Changes**.

> The database must be **empty**. If an old site used it, make a new one.

**Test:** `bdtryrjx_admin` shows next to `bdtryrjx_database` under **Current Databases**.

## 2. Secrets file (3 min)

1. On your computer open `C:\xampp\htdocs\sowetoappliancerepairs\config\secrets.php` in Notepad.
2. Replace `CHANGE_ME` next to `db_pass` with the password from section 1. Save.
3. Note the `admin_seed_password`. That is your first admin password.

> The Groq and ImageKit keys are already in this file. It is never in GitHub.

**Test:** the file has no `CHANGE_ME` left in it.

## 3. Put the site live (10 min) 🟧 CPANEL

1. cPanel → **Git™ Version Control** → **Create**.
2. Leave **Clone a Repository** on. Paste the three values from the table above → **Create**.
3. **Manage** → **Pull or Deploy** → **Update from Remote** → **Deploy HEAD Commit**.
4. **File Manager** → `public_html/config` → **Upload** → choose your `secrets.php`.
5. **Select PHP Version** (or **MultiPHP Manager**) → **8.2**. Extensions needed: `curl`, `gd`, `mbstring`, `pdo_mysql`, `exif`.

> **The repository is private?** cPanel cannot clone it with the plain address. Make it public on GitHub, or use a GitHub token in the Clone URL.
> **Error `"" is not a valid "branch"`?** cPanel → **Terminal** → `cd ~/repositories/sowetoappliancerepairs && git fetch origin && git checkout -B main origin/main`, then refresh **Pull or Deploy**.
> **Old `index.html` in `public_html`?** Delete or rename it.

**Test:** open `sowetoappliancerepairs.co.za`. Open `/fridge-repairs` and `/login`.

## 4. https padlock (5 min) 🟧 CPANEL

1. cPanel → **SSL/TLS Status** → tick both domains → **Run AutoSSL**. Wait until they are green.
2. Open `https://sowetoappliancerepairs.co.za`. Wait until it opens with a padlock and no warning.
3. **File Manager** → `public_html` → **+ File** → name it `https-on.txt` → **Create New File**. Leave it empty.

> This file switches https on. Deploys never remove it. If anything goes wrong, delete it.

**Test:** `http://www.sowetoappliancerepairs.co.za` jumps to `https://sowetoappliancerepairs.co.za`.

## 5. Email (5 min) 🟧 CPANEL + 🟩 ADMIN

1. 🟧 **Email Accounts** → check that `admin@sowetoappliancerepairs.co.za` exists (or **Create** it). Note the password.
2. 🟧 **Email Deliverability** → **Repair** if it is not **Valid**.
3. 🟩 **Settings → Email** → **Mailbox password** → **Save**.

**Test:** 🟩 **Settings → Email** → type your Gmail → **Send test email**.

## 6. Cron: articles write themselves (3 min) 🟧 CPANEL

1. cPanel → **Cron Jobs** → **Common Settings** → **Once Per Ten Minutes (*/10 * * * *)**.
2. **Command**: paste it from the table above → **Add New Cron Job**.

**Test:** after 10 minutes, 🟩 **Dashboard** shows a green tick next to "Is everything still running?".

## 7. Google Search Console (10 min) 🟦 ACCOUNT — the most important one

1. Open https://search.google.com/search-console → **Add property** → **URL prefix** → `https://sowetoappliancerepairs.co.za` → **Continue**.
2. Choose **HTML tag** → **Copy**.
3. 🟩 **Settings → Google & Meta** → **Google Search Console code** → paste → **Save**.
4. 🟦 Back in Search Console → **Verify**.
5. Left menu → **Sitemaps** → type `sitemap.xml` → **Submit**.
6. Top search bar: paste your home page, `/fridge-repairs`, `/washing-machine-repairs` and `/areas/soweto` one at a time → **Request Indexing**.

**Test:** Sitemaps shows **Success** (it can take a day).

## 8. Google Business Profile (20 min) 🟦 ACCOUNT — brings the map calls

1. https://business.google.com → **Add your business**.
2. Name: **Soweto Appliance Repairs** (exactly the same as the website).
3. Category: **Appliance repair service**.
4. "Do you want to add a location customers can visit?" → **No** (you are mobile).
5. Service areas: add the areas on your flyer.
6. Phone **060 432 2996**, website `https://sowetoappliancerepairs.co.za`.
7. Verify, then add photos and your services.
8. **Ask for reviews** → copy the link → 🟩 **Settings → Business** → **Google review link** and **Google Business Profile link** → **Save**.

> Use one business name everywhere. The banners use three (Soweto Appliance Repairs, Soweto Electrical Appliance Repairs, Joburg Soweto Repairs), and Google treats them as different businesses.

**Test:** search "Soweto Appliance Repairs" on your phone after verification.

## 9. Bing and AI search (5 min) 🟦 ACCOUNT

1. https://www.bing.com/webmasters → **Import from Google Search Console**.
2. 🟩 **SEO & visits** → **Send all pages to Bing**.

**Test:** a green message says how many pages were sent.

## 10. Google Analytics (5 min) 🟦 ACCOUNT, optional

1. https://analytics.google.com → **Admin** → **Create** → **Property** → **Web** → copy the **Measurement ID** (`G-…`).
2. 🟩 **Settings → Google & Meta** → **Google Analytics 4** → **Save**.

**Test:** Analytics → **Realtime** shows you when you open the site on your phone.

## 11. Facebook and Instagram (20 min) 🟦 ACCOUNT, optional

1. https://developers.facebook.com → **My Apps** → **Create App**.
2. Add **Facebook Login** → **Settings** → **Valid OAuth Redirect URIs** → paste it from the table → **Save changes**.
3. **App settings → Basic** → copy **App ID** and **App secret**.
4. 🟩 **Social** → paste both → **Save** → **Connect with Facebook** → tick your Page and Instagram.

**Test:** 🟩 **Social** → **Share now**.

## Every week (10 min)

1. 🟩 **Bookings**: call back everything marked **New**.
2. 🟩 **Articles**: read the drafts (stove, oven and geyser) and press **Publish**.
3. 🟩 **Photos**: add two photos from the week's jobs.
4. Send your review link to each happy customer.
5. 🟩 **SEO & visits**: see which pages bring people in, and add similar topics to the **Topic queue**.

# Landing page: Kostenloser Website-Check

Austrian German, no cookies, no third-party requests. Static pages plus a few PHP scripts, written for ALL-INKL (Apache, PHP 8.5).

## What is where
| Path | Purpose |
|---|---|
| `index.html` | Landing page: code box for letter recipients, request form, tiers, FAQ |
| `/check/CODE` → `bericht.php` | Personal report for a letter recipient, plus "how may we contact you" form |
| `/stopp` → `stopp.php` | Objection to further letters (Art. 21 DSGVO); deletes the report, adds to the block list |
| `anfrage.php` | Receives both forms, emails you, sends the receipt and the double opt-in email |
| `bestaetigen.php`, `abmelden.php` | Double opt-in confirmation (button press, not just the link) and email unsubscribe |
| `impressum.html`, `datenschutz.html`, `barrierefreiheit.html` | Built from `legal/*.html` with `python3 tools/build_legal.py` |
| `private/config.php` | Domain, mail addresses, phone, salt: **fill in first** |
| `private/befunde.json` | Explanation for each finding code from the letter's findings library |
| `private/reports/` | One JSON file per letter, made with `python3 tools/reports_from_csv.py mailmerge.csv` |
| `private/data/` | Created at runtime: requests, opt-ins with proof, block list, rate limits |
| `.htaccess` | HTTPS, /check and /stopp addresses, security headers (CSP, HSTS), caching |

`private/` is blocked from the web by its own `.htaccess`. Download `private/data/` regularly (it is your record of consents and objections) and keep a copy.

## Setup in KAS (ALL-INKL)
1. Create the new domain, PHP 8.5 (check it is 8.5.12 or later, the PHP2Shell fix).
2. SSL: Let's Encrypt on, "SSL erzwingen" on.
3. Mailboxes office@ and noreply@ on the new domain; DKIM on.
4. DNS (only needed if KAS does not set them automatically; check under "DNS-Einstellungen"):
   - SPF: `webpickerl.at` TXT `v=spf1 a mx include:spf.kasserver.com ~all`
   - DKIM: switch on in KAS for webpickerl.at; KAS publishes the key itself
   - DMARC: `_dmarc.webpickerl.at` TXT `v=DMARC1; p=none; rua=mailto:office@webpickerl.at` (after 4 weeks without problems, change `p=none` to `p=quarantine`)
5. Upload everything except `tools/`, `legal/` and this README by FTP/SFTP into the domain folder.
6. Edit `private/config.php`, then replace every remaining `[Platzhalter]` in the HTML (search for `[`).
7. Delete the example report `private/reports/K7QM-4XTB.json` before the first real mailing.

## Before going live
- Remove the yellow "Entwurf" banner (HTML comment marks it on each page).
- Lawyer or WKO Rechtsservice review: Impressum, Datenschutzerklärung, consent wording (`private/lib.php`, `CONSENT_EMAIL` and `CONSENT_CONTACT`), the report texts in `befunde.json`.
- Check the trade licence for web services and put it in the Impressum.
- Test once on the live domain: request form, opt-in mail and confirmation, /check/CODE, /stopp. Then run PageSpeed Insights, WAVE or axe, and securityheaders.com.

## For every mailing
1. Fill the mail-merge CSV (letter thread), with a random Audit_Code per letter.
2. Run `python3 tools/reports_from_csv.py the.csv` and upload `private/reports/` before posting the letters.
3. Check `private/data/sperrliste.jsonl` and remove those addresses from the CSV.

## Working in VS Code
1. Clone this repo and open the folder in VS Code.
2. Install the "SFTP" extension (Natizyskunk). Copy `.vscode/sftp.example.json` to `.vscode/sftp.json` and enter your FTP user from KAS (Tools → FTP-Zugänge). The extension asks for the password on first use. `sftp.json` is git-ignored.
3. Upload with "SFTP: Sync Local → Remote". The ignore list keeps `tools/`, `legal/`, the notes and `private/data/` off the server.
4. After editing `legal/*.html`, run `python3 tools/build_legal.py` before uploading.
5. Never delete `private/data/` on the server. Download it now and then as a backup (consent proofs, block list).

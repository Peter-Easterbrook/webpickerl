# Webpickerl landing page: start here

**Source of truth is now GitHub: https://github.com/Peter-Easterbrook/webpickerl (branch main). Edit there; /mnt/project-files/landing-page/ is a snapshot from 29 Oct.**

**Read this first.** Next job: the GISA details for the Impressum (see Next jobs 1), then the test upload. Open `README.md` in this folder only when deploying.

## The position
- The static page, legal pages and PHP backend are built and were tested locally on PHP 8.3. Peter's host runs 8.5.12.
- Domain webpickerl.at is live on Peter's ALL-INKL "Privat" plan. SSL was active with a valid certificate on 29 Oct, and "SSL erzwingen" is on. HSTS is off in KAS on purpose, because `.htaccess` sends it.
- KAS DNS already has A, MX, SPF, DKIM (`kas202610281957._domainkey`) and DMARC (`p=none`). No new records are needed, and adding records would need a plan upgrade.
- Impressum is filled in with Peter Easterbrook, OneStepWeb, Hornbostelgasse 5, 1060 Wien, +43 680 3123714, no UID-Nummer, Kleinunternehmer (§ 6 Abs. 1 Z 27 UStG, limit € 55.000 gross a year since 1 Jan 2025).
- On 30 Oct webpickerl.at still showed ALL-INKL's placeholder page: nothing of ours is on the server yet.
- Preview (private): https://claude.ai/artifact/DCmCphiSJfkyYsxchFDETb (version 3, older than the 30 Oct changes).

## Where we stopped (30 Oct 2026): details, then test upload
Done on 30 Oct, all on GitHub `main`:
- Cream light-theme background (`#fffff7`).
- Prices as "Richtpreis ab": Essentials € 600, Professional € 2.000, Complete € 4.800 (about 30 % below the old lower ends; Peter's reasoning: realistic prices, but customers can't pay them now). Note under the prices: Endpreise, no USt. as Kleinunternehmer. Also changed in `bericht.php`.
- Turnaround: "in der Regel innerhalb von 5 Werktagen" on the page, in the confirmation email (`befund_tage` in `private/config.php`) and in the accessibility statement. The 5 days are queue time; one check is about 1.5 hours of work.
- FAQ wording and six-month retention with a daily purge (`private/lib.php`), done by another session.
- Frontend: the sample report "runs" a check on load, a "noch N Tage" badge counts down to the end of PHP 8.2 security updates (31.12.2026), sections ease in on scroll (CSS `animation-timeline: view()`), pages cross-fade (View Transitions). All off under "reduce motion". Lighthouse 100/100/100 for performance, accessibility and best practices, mobile and desktop. Peter's own run showing 81 was not on our page (the domain still had the placeholder, or a local preview or browser extensions skewed it): re-run in Incognito after the upload.
- WCAG 2.1 AA self-check of the four static pages (axe-core light/dark, keyboard, 320 px, text spacing): fixed light-theme contrast of the green/orange verdicts, heading overflow at 320 px, word breaking. Accessibility statement written, dated 30 Oct 2026, with a "Wie wir geprüft haben" section.
- Footer year: `<span class="year">` in all three footer copies, filled in by `assets/main.js` (legal pages now load it too). No inline scripts: the CSP blocks them.

Upload safety, done: `admin.txt`, `CLAUDE.md`, `Colors2021.txt`, `tools/`, `legal/`, `private/data` and the notes are on the SFTP skip list, and `.htaccess` returns 404 for every `.md`/`.txt` except `robots.txt`. **Never upload `admin.txt`.** `.vscode/sftp.json` has the FTP user (`f0191684`); `private/config.php` and `private/salt.php` are filled in. The mailboxes office@ and noreply@webpickerl.at exist in KAS.

Peter wants all details in place before the first upload.

## Next jobs
1. From the GISA extract: the trade (`[Dienstleistungen in der automatischen Datenverarbeitung und Informationstechnik]`), the trade authority (`[Magistratisches Bezirksamt für den 6. Bezirk …]`) and the WKO Fachgruppe (`[UBIT]`). Edit `legal/impressum.html`, then run `python tools/build_legal.py`.
2. `[14 Tagen]` in `legal/datenschutz.html`: how long ALL-INKL keeps the access logs. Look it up in KAS (Logs).
3. `[November 2026]`: "Stand" date of the Datenschutzerklärung, can be the launch date.
4. Conclude the Auftragsverarbeitungsvertrag with ALL-INKL in KAS, if not done yet: the Datenschutzerklärung says one exists.
5. Optional: KAS "Verzeichnisschutz" so the site can be tested live before launch.
6. Test upload: "SFTP: Sync Local → Remote". Then check `/admin.txt` and `/CLAUDE.md` return 404; favicon, light/dark mode, `/check/K7QM-4XTB`, `/stopp`, the forms; Lighthouse in Incognito.
7. Re-run the accessibility checks on the live site, this time including the PHP pages (report, `/stopp`, opt-in, unsubscribe), which couldn't be tested locally. The statement says "all pages".
8. Lawyer or WKO review of the Impressum, Datenschutz and consent wording (`private/lib.php`: CONSENT_EMAIL, CONSENT_CONTACT). Then remove the draft banner (`index.html` and the template in `tools/build_legal.py`).
9. Go live: delete the example report `private/reports/K7QM-4XTB.json` on the server and test the live forms.

Find remaining placeholders with `grep -rn '\[' *.html legal/`.

Frontend ideas not built yet: a proof strip near the footer ("Diese Seite: Lighthouse 100 · 0 Cookies · 0 Drittanbieter · N KB", measure first), header that shrinks on scroll, `:user-invalid` form feedback with a sent animation, Popover API info bubbles for terms. Rejected: manual light/dark switch (needs browser storage), live mini-check of any URL (abuse risk).

## Parked
- The DMARC report address (`rua=`). Add it by editing the existing `_dmarc` record once mail is flowing.
- Statistics or analytics: none on purpose. The page promises no cookies and no third parties.

## Facts worth not recomputing
- Brand: Webpickerl (play on the Austrian car "Pickerl"; don't imitate the official §57a sticker). Business: OneStepWeb.
- Letter links: `webpickerl.at/check/CODE` (the code format is `XXXX-XXXX`, A-Z and 0-9) and `webpickerl.at/stopp`. The rewrites are in `.htaccess`.
- Reports: `python3 tools/reports_from_csv.py <mailmerge.csv>` writes `private/reports/CODE.json`. These expire 6 months after the letter date. Explanations are in `private/befunde.json`, matched to the letter's findings by the start of the sentence (F_ codes from `/mnt/project-files/upgrade-package/outreach/build_letter.py`).
- Settings: `private/config.php`. Runtime data (requests, opt-ins with proof, block list): `private/data/`.
- Fonts: Atkinson Hyperlegible Next (300, 300 italic, 400) and Bricolage Grotesque (400), self-hosted from npm `@fontsource` (OFL licences in `assets/fonts/`).
- Preview for Claude: `python3 tools/build_preview.py <dir>`, then copy `assets/` and the legal pages. Render a report sample by running the PHP dev server and saving `bericht.php?code=K7QM-4XTB`.

## Working cheaply
- Don't re-read the package .docx. Tier summaries are already in `index.html` and `bericht.php`.
- Edit `legal/*.html`, not the generated `impressum.html` / `datenschutz.html` / `barrierefreiheit.html`.
- Testing locally: PHP isn't installed on Peter's Windows machine, so serve the static pages with `python -m http.server 8765` as a background task. Lighthouse runs with `npx lighthouse` against the installed Chrome; for axe-core, install `puppeteer-core` and `axe-core` in a scratch folder (not the repo). Where PHP exists, start `php -S` as a background task. Don't `pkill`/`pgrep` by a pattern that appears in your own command line, because it kills the shell.
- jsdelivr is blocked by curl here, but `npm pack` works.

## How Peter works
- All German must be Austrian German, with formal "Sie".
- Word documents use Segoe UI with Narrow margins.
- Legal texts need a lawyer's review. Outreach goes by post only, never cold email (TKG 2021 § 174).

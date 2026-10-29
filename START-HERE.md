# Webpickerl landing page: start here

**Source of truth is now GitHub: https://github.com/Peter-Easterbrook/webpickerl (branch main). Edit there; /mnt/project-files/landing-page/ is a snapshot from 29 Oct.**

**Read this first.** Next job: agree on prices with Peter (planned for 29 Oct 2026), then put them in `index.html` (search for `€`). Open `README.md` in this folder only when deploying.

## The position
- The static page, legal pages and PHP backend are built and were tested locally on PHP 8.3. Peter's host runs 8.5.12.
- Domain webpickerl.at is live on Peter's ALL-INKL "Privat" plan. SSL was active with a valid certificate on 29 Oct, and "SSL erzwingen" is on. HSTS is off in KAS on purpose, because `.htaccess` sends it.
- KAS DNS already has A, MX, SPF, DKIM (`kas202610281957._domainkey`) and DMARC (`p=none`). No new records are needed, and adding records would need a plan upgrade.
- Impressum is filled in with Peter Easterbrook, OneStepWeb, Hornbostelgasse 5, Wien, +43 680 3123714, no UID-Nummer.
- Preview (private): https://claude.ai/artifact/DCmCphiSJfkyYsxchFDETb (version 3, includes an example report).

## Next jobs
1. Prices: replace the three `[€ …]` placeholders in `index.html` and `[20 % USt.]` (as a Kleinunternehmer, the note may become "keine USt.").
2. Peter confirms from his GISA extract / Gewerbeschein: postcode (assumed 1060), trade authority (assumed MBA for the 6th district), and whether he holds the IT trade licence. Edit `legal/impressum.html`, then run `python3 tools/build_legal.py`.
3. Kleinunternehmer line in `legal/impressum.html`: keep it or delete it.
4. Remaining placeholders: `[5]` working days, `[6 Monate]`, `[14 Tagen]`, `[November 2026]`, `[Datum]` in the accessibility statement. Find them with `grep -rn '\[' *.html legal/`.
5. In KAS, create the mailboxes office@ and noreply@webpickerl.at. Optionally, set up a "Verzeichnisschutz" password so the forms can be tested live before launch.
6. Lawyer or WKO review of the Impressum, Datenschutz and consent wording (`private/lib.php`: CONSENT_EMAIL, CONSENT_CONTACT). Then remove the draft banner (`index.html` and the template in `tools/build_legal.py`).
7. Upload (see README "Setup in KAS"), delete the example report `private/reports/K7QM-4XTB.json`, and test the live forms.

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
- Testing locally: start `php -S` as a background task. Don't `pkill`/`pgrep` by a pattern that appears in your own command line, because it kills the shell.
- jsdelivr is blocked by curl here, but `npm pack` works.

## How Peter works
- All German must be Austrian German, with formal "Sie".
- Word documents use Segoe UI with Narrow margins.
- Legal texts need a lawyer's review. Outreach goes by post only, never cold email (TKG 2021 § 174).

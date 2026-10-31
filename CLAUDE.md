# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Landing page for **Webpickerl** (business: OneStepWeb), a free website check offered to Austrian businesses by postal letter. Static HTML plus a few PHP scripts, hosted on ALL-INKL (Apache, PHP 8.5). There is no build system, package manager, linter or test suite.

`START-HERE.md` holds the current project status and next jobs; `README.md` covers deployment (KAS setup, DNS, upload, per-mailing steps). Read them before changing anything deployment- or content-related.

## Commands

On this Windows machine use `python` (not `python3`); PHP is not installed locally.

- `python tools/build_legal.py`: regenerates `impressum.html`, `datenschutz.html`, `barrierefreiheit.html`, `faelle.html` from `legal/*.html`. Run after every edit to `legal/` or to the shared header/footer template inside the script.
- `python tools/reports_from_csv.py <mailmerge.csv>`: writes `private/reports/<CODE>.json` per letter (CSV is `;`-delimited, columns `Audit_Code`, `Befund_1..3`, optional `Befund_N_Code`, `Briefdatum`).
- `python tools/build_icons.py`: regenerates `assets/favicon.svg`, `assets/apple-touch-icon.png`, `assets/icon-192.png`, `assets/icon-512.png`, the root `favicon.ico` and the outlined-text logos `assets/logo.svg` / `assets/logo-mono.svg` (needs Pillow, fontTools, brotli). Icon `<link>`s sit in the same three heads as the nav/footer.
- `python tools/build_preview.py <dir>`: skeleton-less copy of `index.html` for a Claude Artifact preview.
- Local testing (where PHP exists): `php -S localhost:8000` as a background task, then e.g. `bericht.php?code=K7QM-4XTB`. `.htaccess` rewrites (`/check/CODE`, `/stopp`) don't apply under `php -S`.
- Find remaining placeholders: `grep -rn '\[' *.html legal/`.

## Architecture

- **Generated files**: the root `impressum.html`, `datenschutz.html`, `barrierefreiheit.html`, `faelle.html` are build output. Edit `legal/*.html` (body fragments) instead. The header/footer for these lives in the `TEMPLATE` string in `tools/build_legal.py`, and is duplicated in `index.html` and in `page_start()`/`page_end()` in `private/lib.php`, so nav/footer changes must be made in all three.
- **PHP entry points** (`anfrage.php`, `bericht.php`, `stopp.php`, `bestaetigen.php`, `abmelden.php`) each `require private/lib.php`, which provides config access (`cfg()`), HTML escaping (`h()`), page rendering, rate limiting, JSONL/JSON storage under `private/data/`, mail sending and the consent wording constants (`CONSENT_EMAIL`, `CONSENT_CONTACT`).
- **Letter flow**: letter prints `webpickerl.at/check/CODE` (code format `XXXX-XXXX`, A-Z0-9) → `.htaccess` rewrites to `bericht.php?code=` → loads `private/reports/CODE.json` → findings explained via `private/befunde.json` (matched by the start of the finding sentence, keyed by `F_` codes). Reports expire 6 months after the letter date (`gueltig_bis`).
- **Forms**: `index.html` request form and the contact form on `bericht.php` both post to `anfrage.php` (`typ` = `check` | `kontakt`), with a honeypot field `firma_url`. `assets/main.js` does client-side validation. Email opt-in is double opt-in via `bestaetigen.php` (requires a button press, not just the link).
- **Storage**: no database. `private/data/` (git-ignored, created at runtime) holds requests, opt-in proofs, the block list (`sperrliste.jsonl`) and rate-limit state. It is the legal record of consents/objections: never delete it on the server.
- `private/` is blocked by its own `.htaccess`, and `.htaccess` at root 404s `/tools`, `/legal`, `/private`.

## Constraints

- **No cookies, no third-party requests, no analytics.** The page promises this. The CSP in `.htaccess` is `'self'`-only, so no inline scripts/styles and no CDNs; fonts are self-hosted in `assets/fonts/`.
- All user-facing text is **Austrian German with formal "Sie"** (e.g. "Jänner"); pages use `lang="de-AT"`.
- Placeholders in the HTML are written in `[eckigen Klammern]`; a yellow "Entwurf" draft banner (marked by an HTML comment) stays until legal review is done.
- Legal texts and consent wording need lawyer/WKO review; don't present edits to them as final.
- Outreach is by post only, never cold email (TKG 2021 § 174). Don't imitate the official §57a "Pickerl" sticker in branding.
- Deployment is SFTP from VS Code (`.vscode/sftp.example.json` → git-ignored `sftp.json`); `tools/`, `legal/`, notes and `private/data/` stay off the server.
